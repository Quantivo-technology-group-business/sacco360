<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Entities\JournalEntry;

class LoanAccrualService
{
    public function processDailyAccruals(?string $asOfDate = null): int
    {
        $asOfDate = $asOfDate ?? date('Y-m-d');
        $count = 0;

        $loans = DB::table('loans')
            ->join('loan_products', 'loan_products.id', '=', 'loans.loan_product_id')
            ->whereIn('loans.status', ['active', 'disbursed'])
            ->whereIn('loan_products.accounting_rule', ['accrual_periodic', 'accrual_upfront'])
            ->select('loans.*', 'loan_products.accounting_rule', 'loan_products.npa_suspend_accrued_income', 'loan_products.interest_receivable_chart_of_account_id', 'loan_products.interest_income_chart_of_account_id')
            ->get();

        foreach ($loans as $loan) {
            if ($this->shouldSkipAccrual($loan)) {
                $this->recordSuspendedAccrual($loan->id, $asOfDate);
                continue;
            }

            $dailyInterest = $this->calculateDailyInterest($loan, $asOfDate);
            if ($dailyInterest <= 0) {
                continue;
            }

            if ($this->alreadyAccrued($loan->id, $asOfDate)) {
                continue;
            }

            $this->postAccrualJournal($loan, $dailyInterest, $asOfDate);
            $this->recordAccrual($loan->id, $asOfDate, $dailyInterest);
            $count++;
        }

        return $count;
    }

    protected function shouldSkipAccrual($loan): bool
    {
        if (!empty($loan->interest_suspended)) {
            return true;
        }

        if ($loan->npa_suspend_accrued_income && !empty($loan->classification_status)) {
            $service = new CaelsClassificationService();
            if ($service->shouldSuspendInterest($loan->classification_status)) {
                return true;
            }
        }

        return false;
    }

    protected function calculateDailyInterest($loan, string $asOfDate): float
    {
        $outstanding = (float) DB::table('loan_transactions')
            ->where('loan_id', $loan->id)
            ->where('submitted_on', '<=', $asOfDate)
            ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as total')
            ->value('total');

        if ($outstanding <= 0 || $loan->interest_rate <= 0) {
            return 0;
        }

        $annualRate = $loan->interest_rate / 100;
        return round($outstanding * ($annualRate / 365), 2);
    }

    protected function alreadyAccrued(int $loanId, string $date): bool
    {
        if (!Schema::hasTable('loan_interest_accruals')) {
            return false;
        }

        return DB::table('loan_interest_accruals')
            ->where('loan_id', $loanId)
            ->where('accrual_date', $date)
            ->exists();
    }

    protected function postAccrualJournal($loan, float $amount, string $date): void
    {
        $receivableId = $loan->interest_receivable_chart_of_account_id;
        $incomeId = $loan->interest_income_chart_of_account_id;

        if (!$receivableId || !$incomeId) {
            $receivableId = $this->getGlAccountId(1300);
            $incomeId = $this->getGlAccountId(4100);
        }

        if (!$receivableId || !$incomeId) {
            return;
        }

        $txnNumber = 'LA' . $loan->id . '-' . str_replace('-', '', $date);
        $userId = Auth::id() ?? 1;
        $dateParts = explode('-', $date);

        $debit = new JournalEntry();
        $debit->created_by_id = $userId;
        $debit->transaction_number = $txnNumber;
        $debit->branch_id = $loan->branch_id;
        $debit->currency_id = $loan->currency_id;
        $debit->chart_of_account_id = $receivableId;
        $debit->transaction_type = 'loan_interest_accrual';
        $debit->date = $date;
        $debit->month = $dateParts[1];
        $debit->year = $dateParts[0];
        $debit->debit = $amount;
        $debit->reference = $loan->id;
        $debit->save();

        $credit = new JournalEntry();
        $credit->created_by_id = $userId;
        $credit->transaction_number = $txnNumber;
        $credit->branch_id = $loan->branch_id;
        $credit->currency_id = $loan->currency_id;
        $credit->chart_of_account_id = $incomeId;
        $credit->transaction_type = 'loan_interest_accrual';
        $credit->date = $date;
        $credit->month = $dateParts[1];
        $credit->year = $dateParts[0];
        $credit->credit = $amount;
        $credit->reference = $loan->id;
        $credit->save();
    }

    protected function recordAccrual(int $loanId, string $date, float $amount): void
    {
        if (!Schema::hasTable('loan_interest_accruals')) {
            return;
        }

        DB::table('loan_interest_accruals')->insert([
            'loan_id' => $loanId,
            'accrual_date' => $date,
            'amount' => $amount,
            'status' => 'accrued',
            'journal_reference' => 'LA' . $loanId . '-' . str_replace('-', '', $date),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function recordSuspendedAccrual(int $loanId, string $date): void
    {
        if (!Schema::hasTable('loan_interest_accruals') || $this->alreadyAccrued($loanId, $date)) {
            return;
        }

        DB::table('loan_interest_accruals')->insert([
            'loan_id' => $loanId,
            'accrual_date' => $date,
            'amount' => 0,
            'status' => 'suspended',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function getGlAccountId(int $glCode): ?int
    {
        return DB::table('chart_of_accounts')->where('gl_code', $glCode)->value('id');
    }
}
