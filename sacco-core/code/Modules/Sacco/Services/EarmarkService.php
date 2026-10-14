<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoMemberEarmark;

class EarmarkService
{
    public function getActiveForClient(int $clientId, bool $tellerOnly = false): array
    {
        $query = SaccoMemberEarmark::where('client_id', $clientId)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', date('Y-m-d'));
            });

        if ($tellerOnly) {
            $query->where('show_at_teller', 1);
        }

        return $query->orderByDesc('severity')->get()->map(function ($e) {
            return [
                'id' => $e->id,
                'severity' => $e->severity,
                'restriction_type' => $e->restriction_type,
                'title' => $e->title,
                'message' => $e->message,
                'blocks_transactions' => in_array($e->restriction_type, [
                    'block_withdrawal', 'block_deposit', 'block_all_transactions', 'block_loans',
                ]),
            ];
        })->toArray();
    }

    public function create(array $data): SaccoMemberEarmark
    {
        $earmark = new SaccoMemberEarmark();
        $earmark->created_by_id = Auth::id();
        $earmark->client_id = $data['client_id'];
        $earmark->severity = $data['severity'] ?? 'warning';
        $earmark->restriction_type = $data['restriction_type'] ?? 'alert';
        $earmark->title = $data['title'];
        $earmark->message = $data['message'];
        $earmark->show_at_teller = $data['show_at_teller'] ?? 1;
        $earmark->expires_at = $data['expires_at'] ?? null;
        $earmark->status = 'active';
        $earmark->save();
        return $earmark;
    }

    public function revoke(SaccoMemberEarmark $earmark): void
    {
        $earmark->status = 'revoked';
        $earmark->save();
    }

    public function validateTransaction(int $clientId, string $transactionType, float $amount = 0): array
    {
        $earmarks = $this->getActiveForClient($clientId);
        $violations = [];

        foreach ($earmarks as $earmark) {
            $blocked = false;
            switch ($earmark['restriction_type']) {
                case 'block_all_transactions':
                    $blocked = true;
                    break;
                case 'block_withdrawal':
                    $blocked = in_array($transactionType, ['withdrawal', 'atm_withdrawal']);
                    break;
                case 'block_deposit':
                    $blocked = in_array($transactionType, ['deposit', 'atm_deposit']);
                    break;
                case 'block_loans':
                    $blocked = $transactionType === 'loan';
                    break;
            }

            if ($blocked) {
                $violations[] = $earmark['title'] . ': ' . $earmark['message'];
            }
        }

        return [
            'allowed' => empty($violations),
            'violations' => $violations,
            'earmarks' => $earmarks,
        ];
    }

    public function expireDueEarmarks(): int
    {
        return SaccoMemberEarmark::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', date('Y-m-d'))
            ->update(['status' => 'expired']);
    }
}
