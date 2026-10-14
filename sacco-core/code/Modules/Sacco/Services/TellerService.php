<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoTeller;
use Modules\Sacco\Entities\SaccoTellerSession;

class TellerService
{
    public function openSession(int $branchId, float $openingBalance, ?string $tillNumber = null): SaccoTellerSession
    {
        $teller = SaccoTeller::firstOrCreate(
            ['user_id' => Auth::id(), 'branch_id' => $branchId],
            [
                'till_number' => $tillNumber,
                'status' => 'closed',
                'max_single_transaction' => config('sacco.teller_default_max_single', 100000),
                'max_daily_withdrawal' => config('sacco.teller_default_max_daily_withdrawal', 500000),
                'max_cash_on_hand' => config('sacco.teller_default_max_cash', 1000000),
            ]
        );

        $session = new SaccoTellerSession();
        $session->sacco_teller_id = $teller->id;
        $session->user_id = Auth::id();
        $session->opening_balance = $openingBalance;
        $session->daily_withdrawal_total = 0;
        $session->daily_deposit_total = 0;
        $session->opened_at = now();
        $session->status = 'open';
        $session->save();

        $teller->status = 'open';
        $teller->opening_balance = $openingBalance;
        $teller->current_balance = $openingBalance;
        $teller->opened_at = now();
        $teller->save();

        return $session;
    }

    public function closeSession(SaccoTellerSession $session, float $closingBalance, ?string $notes = null): SaccoTellerSession
    {
        $teller = SaccoTeller::find($session->sacco_teller_id);
        $session->closing_balance = $closingBalance;
        $session->expected_balance = $teller->current_balance;
        $session->variance = $closingBalance - $teller->current_balance;
        $session->closed_at = now();
        $session->status = abs($session->variance) < 0.01 ? 'balanced' : 'closed';
        $session->notes = $notes;
        $session->save();

        $teller->status = 'closed';
        $teller->current_balance = 0;
        $teller->closed_at = now();
        $teller->save();

        return $session;
    }

    public function validateTransaction(string $type, float $amount): array
    {
        $teller = SaccoTeller::where('user_id', Auth::id())->where('status', 'open')->first();
        if (!$teller) {
            return ['allowed' => true, 'violations' => [], 'teller' => null];
        }

        $session = SaccoTellerSession::where('sacco_teller_id', $teller->id)->where('status', 'open')->first();
        $violations = [];

        if ($teller->max_single_transaction && $amount > $teller->max_single_transaction) {
            $violations[] = sprintf('Amount exceeds teller single transaction limit of %.2f', $teller->max_single_transaction);
        }

        if ($type === 'withdrawal' && $teller->max_daily_withdrawal && $session) {
            $projected = ($session->daily_withdrawal_total ?? 0) + $amount;
            if ($projected > $teller->max_daily_withdrawal) {
                $violations[] = sprintf('Would exceed daily withdrawal limit of %.2f', $teller->max_daily_withdrawal);
            }
        }

        if ($type === 'withdrawal' && $teller->current_balance < $amount) {
            $violations[] = 'Insufficient cash in till for this withdrawal';
        }

        if ($type === 'deposit' && $teller->max_cash_on_hand) {
            $projected = $teller->current_balance + $amount;
            if ($projected > $teller->max_cash_on_hand) {
                $violations[] = sprintf('Would exceed maximum cash on hand limit of %.2f', $teller->max_cash_on_hand);
            }
        }

        return [
            'allowed' => empty($violations),
            'violations' => $violations,
            'teller' => $teller,
            'session' => $session,
        ];
    }

    public function recordTransaction(SaccoTeller $teller, float $amount, string $type): void
    {
        $session = SaccoTellerSession::where('sacco_teller_id', $teller->id)->where('status', 'open')->first();

        if ($type === 'deposit') {
            $teller->current_balance += $amount;
            if ($session) {
                $session->daily_deposit_total = ($session->daily_deposit_total ?? 0) + $amount;
                $session->save();
            }
        } else {
            $teller->current_balance -= $amount;
            if ($session) {
                $session->daily_withdrawal_total = ($session->daily_withdrawal_total ?? 0) + $amount;
                $session->save();
            }
        }
        $teller->save();
    }

    public function updateLimits(SaccoTeller $teller, array $limits): SaccoTeller
    {
        foreach (['max_single_transaction', 'max_daily_withdrawal', 'max_cash_on_hand'] as $field) {
            if (array_key_exists($field, $limits)) {
                $teller->$field = $limits[$field];
            }
        }
        $teller->save();
        return $teller;
    }

    public function getOpenTeller(): ?SaccoTeller
    {
        return SaccoTeller::where('user_id', Auth::id())->where('status', 'open')->first();
    }
}
