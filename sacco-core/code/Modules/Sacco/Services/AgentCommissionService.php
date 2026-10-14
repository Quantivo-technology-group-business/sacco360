<?php

namespace Modules\Sacco\Services;

use Modules\Sacco\Entities\SaccoAgent;
use Modules\Sacco\Entities\SaccoAgentCommissionRate;
use Modules\Sacco\Entities\SaccoAgentTransaction;

class AgentCommissionService
{
    public function calculateCommission(string $transactionType, float $amount, ?SaccoAgent $agent = null): float
    {
        if ($agent && $agent->commission_rate > 0) {
            return round($amount * ($agent->commission_rate / 100), 2);
        }

        return SaccoAgentCommissionRate::getCommissionForTransaction($transactionType, $amount);
    }

    public function processTransactionCommission(SaccoAgent $agent, SaccoAgentTransaction $transaction): float
    {
        $commission = $this->calculateCommission(
            $transaction->transaction_type,
            (float)$transaction->amount,
            $agent
        );

        if ($commission > 0) {
            $description = $this->getCommissionDescription($transaction);
            $agent->addCommission($commission, $transaction->id, $description);

            $transaction->commission = $commission;
            $transaction->commission_rate = $agent->commission_rate > 0 
                ? $agent->commission_rate 
                : $this->getRateForTransaction($transaction->transaction_type, (float)$transaction->amount);
            $transaction->save();
        }

        return $commission;
    }

    protected function getCommissionDescription(SaccoAgentTransaction $transaction): string
    {
        $type = ucfirst(str_replace('_', ' ', $transaction->transaction_type));
        $amount = number_format($transaction->amount, 2);
        return "{$type} commission - Txn #{$transaction->reference} ({$amount})";
    }

    protected function getRateForTransaction(string $type, float $amount): float
    {
        $rate = SaccoAgentCommissionRate::active()
            ->forType($type)
            ->forAmount($amount)
            ->first();

        if (!$rate) {
            return 0;
        }

        return $rate->rate_type === 'percentage' ? (float)$rate->rate_value : 0;
    }

    public function getCommissionRates(): array
    {
        return SaccoAgentCommissionRate::active()
            ->orderBy('transaction_type')
            ->orderBy('min_amount')
            ->get()
            ->groupBy('transaction_type')
            ->toArray();
    }

    public function getAgentCommissionSummary(SaccoAgent $agent, ?string $period = 'month'): array
    {
        $query = $agent->commissionLedger();

        switch ($period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
            default:
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
        }

        $ledger = $query->get();

        return [
            'earned' => $ledger->where('type', 'earned')->sum('amount'),
            'withdrawn' => $ledger->where('type', 'withdrawn')->sum('amount'),
            'adjustments' => $ledger->where('type', 'adjustment')->sum('amount'),
            'bonuses' => $ledger->where('type', 'bonus')->sum('amount'),
            'balance' => (float)$agent->commission_balance,
            'entries' => $ledger->count(),
        ];
    }

    public function processCommissionWithdrawal(SaccoAgent $agent, float $amount, string $method = 'cash'): array
    {
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Invalid withdrawal amount'];
        }

        if ((float)$agent->commission_balance < $amount) {
            return ['success' => false, 'message' => 'Insufficient commission balance'];
        }

        $description = "Commission withdrawal via {$method}";
        $success = $agent->withdrawCommission($amount, $description);

        if ($success) {
            activity()->log("Agent {$agent->agent_code} withdrew commission: " . number_format($amount, 2));
        }

        return [
            'success' => $success,
            'message' => $success 
                ? 'Commission withdrawal of ' . number_format($amount, 2) . ' processed successfully'
                : 'Failed to process commission withdrawal',
            'new_balance' => (float)$agent->commission_balance,
        ];
    }
}
