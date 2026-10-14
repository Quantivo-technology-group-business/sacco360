<?php

namespace Modules\Sacco\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Modules\Sacco\Entities\SaccoStatementSchedule;
use Modules\Sacco\Entities\SaccoStatementLog;
use Modules\Client\Entities\Client;

class EStatementService
{
    /**
     * Run all due statement schedules.
     * Called by a scheduled job/command.
     */
    public function runDueSchedules(): int
    {
        $today = Carbon::today();
        $sent  = 0;

        $schedules = SaccoStatementSchedule::where('is_active', 1)
            ->where(function ($q) use ($today) {
                $q->whereNull('next_run_date')
                  ->orWhere('next_run_date', '<=', $today);
            })
            ->get();

        foreach ($schedules as $schedule) {
            // If schedule is for all members, get all active clients
            if ($schedule->client_id) {
                $clients = Client::where('id', $schedule->client_id)->get();
            } else {
                $clients = Client::where('status', 1)
                    ->whereNotIn('member_status_detail', ['exit', 'closed', 'deceased'])
                    ->get();
            }

            [$periodFrom, $periodTo] = $this->calculatePeriod($schedule);

            foreach ($clients as $client) {
                $this->generateAndSend($schedule, $client, $periodFrom, $periodTo);
                $sent++;
            }

            // Update next run date
            $schedule->last_run_date = $today;
            $schedule->next_run_date = $this->calculateNextRunDate($schedule);
            $schedule->save();
        }

        return $sent;
    }

    /**
     * Generate and send a statement for a specific member on demand.
     */
    public function sendOnDemand(Client $client, string $statementType, Carbon $from, Carbon $to, int $triggeredById): SaccoStatementLog
    {
        $log = new SaccoStatementLog();
        $log->client_id         = $client->id;
        $log->statement_type    = $statementType;
        $log->period_from       = $from;
        $log->period_to         = $to;
        $log->delivery_channel  = 'email';
        $log->recipient_email   = $client->email;
        $log->triggered_by_id   = $triggeredById;
        $log->status            = 'queued';
        $log->save();

        try {
            $data = $this->buildStatementData($client, $statementType, $from, $to);
            // In production, dispatch a Mail or PDF generation job here.
            // For now we mark as sent to simulate.
            $log->status  = 'sent';
            $log->sent_at = Carbon::now();
            $log->save();
        } catch (\Exception $e) {
            $log->status        = 'failed';
            $log->error_message = $e->getMessage();
            $log->save();
        }

        return $log;
    }

    /**
     * Build statement data for a member.
     */
    public function buildStatementData(Client $client, string $type, Carbon $from, Carbon $to): array
    {
        $data = [
            'client'     => $client,
            'period_from'=> $from,
            'period_to'  => $to,
            'type'       => $type,
        ];

        if (in_array($type, ['full', 'savings'])) {
            $data['savings'] = DB::table('savings')
                ->where('client_id', $client->id)
                ->get()
                ->map(function ($account) use ($from, $to) {
                    $account->transactions = DB::table('savings_transactions')
                        ->where('savings_id', $account->id)
                        ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                        ->orderBy('date')
                        ->get();
                    return $account;
                });
        }

        if (in_array($type, ['full', 'loans'])) {
            $data['loans'] = DB::table('loans')
                ->where('client_id', $client->id)
                ->whereIn('status', ['disbursed', 'partial', 'complete'])
                ->get()
                ->map(function ($loan) use ($from, $to) {
                    $loan->repayments = DB::table('loan_repayments')
                        ->where('loan_id', $loan->id)
                        ->whereBetween('created_at', [$from, $to])
                        ->orderBy('created_at')
                        ->get();
                    return $loan;
                });
        }

        if (in_array($type, ['full', 'shares'])) {
            $data['shares'] = DB::table('shares')
                ->where('client_id', $client->id)
                ->get();
        }

        return $data;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function generateAndSend(SaccoStatementSchedule $schedule, Client $client, Carbon $from, Carbon $to): void
    {
        $log = SaccoStatementLog::create([
            'sacco_statement_schedule_id' => $schedule->id,
            'client_id'                   => $client->id,
            'statement_type'              => $schedule->statement_type,
            'period_from'                 => $from,
            'period_to'                   => $to,
            'delivery_channel'            => $schedule->delivery_channel,
            'recipient_email'             => $client->email,
            'recipient_phone'             => $client->mobile,
            'status'                      => 'queued',
        ]);

        try {
            // In production: dispatch PDF + email job
            $log->status  = 'sent';
            $log->sent_at = Carbon::now();
            $log->save();
        } catch (\Exception $e) {
            $log->status        = 'failed';
            $log->error_message = $e->getMessage();
            $log->save();
        }
    }

    private function calculatePeriod(SaccoStatementSchedule $schedule): array
    {
        $to   = Carbon::today()->subDay();
        $from = $to->copy()->subMonths($schedule->lookback_months)->addDay();
        return [$from, $to];
    }

    private function calculateNextRunDate(SaccoStatementSchedule $schedule): Carbon
    {
        $day = min($schedule->send_day_of_month, 28);
        $next = Carbon::today();

        switch ($schedule->frequency) {
            case 'quarterly':
                $next = $next->addMonths(3)->startOfMonth()->addDays($day - 1);
                break;
            case 'annually':
                $next = $next->addYear()->startOfMonth()->addDays($day - 1);
                break;
            default: // monthly
                $next = $next->addMonth()->startOfMonth()->addDays($day - 1);
                break;
        }

        return $next;
    }
}
