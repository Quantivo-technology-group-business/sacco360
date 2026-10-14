<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Modules\Loan\Entities\Loan;
use Modules\Loan\Entities\LoanTransaction;
use Modules\Savings\Entities\Savings;
use Modules\Savings\Entities\SavingsTransaction;

class SavingsStatementService
{
    public function getSavingsTransactions(int $savingsId, int $clientId, ?string $startDate, ?string $endDate)
    {
        $savings = Savings::where('id', $savingsId)->where('client_id', $clientId)->firstOrFail();

        $query = SavingsTransaction::where('savings_id', $savings->id)
            ->orderBy('submitted_on')
            ->orderBy('id');

        if ($startDate) {
            $query->where('submitted_on', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('submitted_on', '<=', $endDate);
        }

        return [
            'savings' => $savings,
            'transactions' => $query->get(),
            'opening_balance' => $this->openingBalance($savings->id, $startDate),
        ];
    }

    public function getLoanTransactions(int $loanId, int $clientId, ?string $startDate, ?string $endDate)
    {
        $loan = Loan::where('id', $loanId)->where('client_id', $clientId)->firstOrFail();

        $query = LoanTransaction::where('loan_id', $loan->id)
            ->orderBy('submitted_on')
            ->orderBy('id');

        if ($startDate) {
            $query->where('submitted_on', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('submitted_on', '<=', $endDate);
        }

        return [
            'loan' => $loan,
            'transactions' => $query->get(),
        ];
    }

    protected function openingBalance(int $savingsId, ?string $startDate): float
    {
        if (!$startDate) {
            return 0;
        }

        return (float) DB::table('savings_transactions')
            ->where('savings_id', $savingsId)
            ->where('submitted_on', '<', $startDate)
            ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) as balance')
            ->value('balance');
    }
}
