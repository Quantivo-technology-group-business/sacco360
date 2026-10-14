<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Modules\Sacco\Entities\SaccoAmlAlert;

class AmlMonitoringService
{
    public function runDailyChecks(): int
    {
        $alerts = 0;
        $alerts += $this->detectStructuring();
        $alerts += $this->detectPreLoanDeposits();
        $alerts += $this->detectLargeTransactions();
        $alerts += $this->detectRapidMovement();
        return $alerts;
    }

    public function updateSarStatus(int $alertId, string $status): void
    {
        $alert = SaccoAmlAlert::findOrFail($alertId);
        $alert->sar_status = $status;
        if ($status === 'reviewed') {
            $alert->status = 'investigating';
        }
        if ($status === 'reported') {
            $alert->status = 'closed';
        }
        $alert->save();
    }

    protected function detectStructuring(): int
    {
        $count = 0;
        $threshold = config('sacco.aml_structuring_threshold', 100000);
        $suspicious = DB::table('savings_transactions')
            ->where('submitted_on', '>=', date('Y-m-d', strtotime('-7 days')))
            ->where('credit', '>', 0)
            ->selectRaw('savings_id, submitted_on, COUNT(*) as txn_count, SUM(amount) as total')
            ->groupBy('savings_id', 'submitted_on')
            ->having('txn_count', '>=', 3)
            ->having('total', '>', $threshold)
            ->get();

        foreach ($suspicious as $row) {
            $savings = DB::table('savings')->where('id', $row->savings_id)->first();
            if (!$savings) {
                continue;
            }
            $exists = SaccoAmlAlert::where('client_id', $savings->client_id)
                ->where('alert_type', 'structuring')
                ->where('created_at', '>=', date('Y-m-d'))
                ->exists();
            if (!$exists) {
                SaccoAmlAlert::create([
                    'client_id' => $savings->client_id,
                    'alert_type' => 'structuring',
                    'severity' => 'high',
                    'description' => "Multiple deposits ({$row->txn_count}) totalling {$row->total} on same day - possible structuring",
                    'amount' => $row->total,
                    'reference_type' => 'savings',
                    'reference_id' => $row->savings_id,
                    'sar_status' => 'open',
                ]);
                $count++;
            }
        }
        return $count;
    }

    protected function detectPreLoanDeposits(): int
    {
        $count = 0;
        $recentLoans = DB::table('loans')
            ->where('created_at', '>=', date('Y-m-d', strtotime('-30 days')))
            ->whereIn('status', ['pending', 'submitted', 'approved'])
            ->get();

        foreach ($recentLoans as $loan) {
            $deposits = DB::table('savings_transactions')
                ->join('savings', 'savings.id', '=', 'savings_transactions.savings_id')
                ->where('savings.client_id', $loan->client_id)
                ->where('savings_transactions.submitted_on', '>=', date('Y-m-d', strtotime($loan->created_at . ' -14 days')))
                ->where('savings_transactions.submitted_on', '<=', date('Y-m-d', strtotime($loan->created_at)))
                ->where('savings_transactions.credit', '>', 0)
                ->selectRaw('COUNT(*) as cnt, SUM(savings_transactions.amount) as total')
                ->first();

            if ($deposits && $deposits->cnt >= 2 && $deposits->total > 50000) {
                $exists = SaccoAmlAlert::where('client_id', $loan->client_id)
                    ->where('alert_type', 'pre_loan_deposits')
                    ->where('reference_id', $loan->id)
                    ->exists();
                if (!$exists) {
                    SaccoAmlAlert::create([
                        'client_id' => $loan->client_id,
                        'alert_type' => 'pre_loan_deposits',
                        'severity' => 'medium',
                        'description' => 'Multiple deposits before loan application - possible loan fraud indicator',
                        'amount' => $deposits->total,
                        'reference_type' => 'loan',
                        'reference_id' => $loan->id,
                        'sar_status' => 'open',
                    ]);
                    $count++;
                }
            }
        }
        return $count;
    }

    protected function detectLargeTransactions(): int
    {
        $count = 0;
        $threshold = config('sacco.aml_large_transaction_threshold', 1000000);
        $large = DB::table('savings_transactions')
            ->where('submitted_on', '>=', date('Y-m-d', strtotime('-1 day')))
            ->where('amount', '>=', $threshold)
            ->get();

        foreach ($large as $txn) {
            $savings = DB::table('savings')->where('id', $txn->savings_id)->first();
            if (!$savings) {
                continue;
            }
            $exists = SaccoAmlAlert::where('reference_type', 'savings_transaction')
                ->where('reference_id', $txn->id)
                ->exists();
            if (!$exists) {
                SaccoAmlAlert::create([
                    'client_id' => $savings->client_id,
                    'alert_type' => 'large_transaction',
                    'severity' => 'high',
                    'description' => "Large transaction of {$txn->amount} detected (SASRA AML threshold)",
                    'amount' => $txn->amount,
                    'reference_type' => 'savings_transaction',
                    'reference_id' => $txn->id,
                    'sar_status' => 'open',
                ]);
                $count++;
            }
        }
        return $count;
    }

    protected function detectRapidMovement(): int
    {
        $count = 0;
        $clients = DB::table('savings_transactions')
            ->join('savings', 'savings.id', '=', 'savings_transactions.savings_id')
            ->where('savings_transactions.submitted_on', '>=', date('Y-m-d', strtotime('-3 days')))
            ->selectRaw('savings.client_id, COUNT(*) as txn_count, SUM(ABS(savings_transactions.amount)) as total')
            ->groupBy('savings.client_id')
            ->having('txn_count', '>=', 5)
            ->having('total', '>', 200000)
            ->get();

        foreach ($clients as $row) {
            $exists = SaccoAmlAlert::where('client_id', $row->client_id)
                ->where('alert_type', 'rapid_movement')
                ->where('created_at', '>=', date('Y-m-d', strtotime('-3 days')))
                ->exists();
            if (!$exists) {
                SaccoAmlAlert::create([
                    'client_id' => $row->client_id,
                    'alert_type' => 'rapid_movement',
                    'severity' => 'medium',
                    'description' => "Rapid fund movement: {$row->txn_count} transactions totalling {$row->total} in 3 days",
                    'amount' => $row->total,
                    'sar_status' => 'open',
                ]);
                $count++;
            }
        }
        return $count;
    }
}
