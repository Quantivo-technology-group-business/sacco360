<?php

namespace Modules\Sacco\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Sacco\Entities\SaccoDormancyRule;
use Modules\Sacco\Entities\SaccoDormancyLog;
use Modules\Client\Entities\Client;

class DormancyService
{
    /**
     * Run the dormancy engine against all active members.
     * Returns count of members flagged.
     */
    public function runDormancyCheck(): int
    {
        $rule = SaccoDormancyRule::where('is_active', 1)->first();

        if (!$rule) {
            return 0;
        }

        $cutoffDate = Carbon::now()->subMonths($rule->inactivity_months)->toDateString();

        // Find members with no savings/loan transactions since cutoff
        $dormantClients = Client::where('status', 1) // active clients
            ->where(function ($q) use ($cutoffDate) {
                // Has no savings transaction after cutoff
                $q->whereDoesntHave('savings', function ($sq) use ($cutoffDate) {
                    $sq->whereHas('savingsTransactions', function ($stq) use ($cutoffDate) {
                        $stq->where('date', '>=', $cutoffDate);
                    });
                });
            })
            ->whereNotIn('member_status_detail', ['dormant', 'exit', 'closed', 'deceased'])
            ->get();

        $flagged = 0;

        foreach ($dormantClients as $client) {
            // Check minimum balance criteria
            $savingsBalance = DB::table('savings')
                ->where('client_id', $client->id)
                ->sum('balance');

            if ($rule->minimum_balance > 0 && $savingsBalance > $rule->minimum_balance) {
                continue; // Balance above threshold, not dormant
            }

            // Update member status
            $client->member_status_detail = 'dormant';
            $client->save();

            // Log the event
            SaccoDormancyLog::create([
                'client_id'              => $client->id,
                'sacco_dormancy_rule_id' => $rule->id,
                'action'                 => 'flagged_dormant',
                'action_date'            => Carbon::today(),
                'actioned_by_id'         => null,
                'notes'                  => "No activity since {$cutoffDate}. Balance: {$savingsBalance}",
            ]);

            $flagged++;
        }

        return $flagged;
    }

    /**
     * Reactivate a dormant member.
     */
    public function reactivate(Client $client, int $actionedById, string $notes = ''): void
    {
        $client->member_status_detail = 'active';
        $client->save();

        SaccoDormancyLog::create([
            'client_id'              => $client->id,
            'sacco_dormancy_rule_id' => null,
            'action'                 => 'reactivated',
            'action_date'            => Carbon::today(),
            'actioned_by_id'         => $actionedById,
            'notes'                  => $notes ?: 'Member manually reactivated.',
        ]);
    }

    /**
     * Get all currently dormant members.
     */
    public function getDormantMembers(): \Illuminate\Database\Eloquent\Collection
    {
        return Client::where('member_status_detail', 'dormant')
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Get dormancy history for a client.
     */
    public function getClientHistory(int $clientId): \Illuminate\Database\Eloquent\Collection
    {
        return SaccoDormancyLog::where('client_id', $clientId)
            ->with('rule')
            ->orderByDesc('action_date')
            ->get();
    }
}
