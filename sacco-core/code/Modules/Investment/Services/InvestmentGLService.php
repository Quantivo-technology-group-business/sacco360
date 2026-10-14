<?php
namespace Modules\Investment\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Entities\JournalEntry;
use Modules\Investment\Entities\MemberInvestment;

class InvestmentGLService
{
    /**
     * Post GL entries when a member invests (principal received).
     * Dr  Cash / Bank Account
     * Cr  Investment Control (liability — funds held for member)
     */
    public function postInvestment(MemberInvestment $inv, string $date, float $amount): string
    {
        $plan = $inv->plan;
        if (!$plan->cash_account_id || !$plan->investment_control_account_id) return '';

        $txn = uniqid('INV-');

        $this->je($txn, $inv->branch_id, $inv->currency_id, $plan->cash_account_id, $date, $amount, null, 'Investment received — ' . $inv->account_number);
        $this->je($txn, $inv->branch_id, $inv->currency_id, $plan->investment_control_account_id, $date, null, $amount, 'Investment received — ' . $inv->account_number);

        return $txn;
    }

    /**
     * Post GL entries when profit is accrued (not yet paid).
     * Dr  Interest / Profit Expense
     * Cr  Profit Payable (liability)
     */
    public function postProfitAccrual(MemberInvestment $inv, string $date, float $amount): string
    {
        $plan = $inv->plan;
        if (!$plan->interest_expense_account_id || !$plan->profit_payable_account_id) return '';

        $txn = uniqid('INV-ACC-');

        $this->je($txn, $inv->branch_id, $inv->currency_id, $plan->interest_expense_account_id, $date, $amount, null, 'Profit accrual — ' . $inv->account_number);
        $this->je($txn, $inv->branch_id, $inv->currency_id, $plan->profit_payable_account_id,   $date, null, $amount, 'Profit accrual — ' . $inv->account_number);

        return $txn;
    }

    /**
     * Post GL entries when profit is paid out to member.
     * Dr  Profit Payable
     * Cr  Cash / Bank
     */
    public function postProfitPayment(MemberInvestment $inv, string $date, float $amount): string
    {
        $plan = $inv->plan;
        if (!$plan->profit_payable_account_id || !$plan->cash_account_id) return '';

        $txn = uniqid('INV-PAY-');

        $this->je($txn, $inv->branch_id, $inv->currency_id, $plan->profit_payable_account_id, $date, $amount, null, 'Profit paid — ' . $inv->account_number);
        $this->je($txn, $inv->branch_id, $inv->currency_id, $plan->cash_account_id,           $date, null, $amount, 'Profit paid — ' . $inv->account_number);

        return $txn;
    }

    /**
     * Post GL entries for maturity payout (principal + remaining profit).
     * Dr  Investment Control   (principal)
     * Dr  Profit Payable       (remaining unpaid profit)
     * Cr  Cash / Bank          (total payout)
     */
    public function postMaturityPayout(MemberInvestment $inv, string $date, float $principal, float $remainingProfit): string
    {
        $plan  = $inv->plan;
        $total = $principal + $remainingProfit;
        if (!$plan->cash_account_id) return '';

        $txn = uniqid('INV-MAT-');

        if ($plan->investment_control_account_id) {
            $this->je($txn, $inv->branch_id, $inv->currency_id, $plan->investment_control_account_id, $date, $principal, null, 'Maturity payout principal — ' . $inv->account_number);
        }
        if ($remainingProfit > 0 && $plan->profit_payable_account_id) {
            $this->je($txn, $inv->branch_id, $inv->currency_id, $plan->profit_payable_account_id, $date, $remainingProfit, null, 'Maturity payout profit — ' . $inv->account_number);
        }
        if ($remainingProfit > 0 && !$plan->profit_payable_account_id && $plan->interest_expense_account_id) {
            // Direct expense if no payable account
            $this->je($txn, $inv->branch_id, $inv->currency_id, $plan->interest_expense_account_id, $date, $remainingProfit, null, 'Maturity profit expense — ' . $inv->account_number);
        }
        $this->je($txn, $inv->branch_id, $inv->currency_id, $plan->cash_account_id, $date, null, $total, 'Maturity cash out — ' . $inv->account_number);

        return $txn;
    }

    /**
     * Post GL for early withdrawal (principal minus penalty).
     * Dr  Investment Control  (full principal)
     * Cr  Cash / Bank         (net payout)
     * Cr  Penalty Income      (penalty — re-credit to interest_expense as income reversal)
     */
    public function postEarlyWithdrawal(MemberInvestment $inv, string $date, float $principal, float $penalty): string
    {
        $plan    = $inv->plan;
        $netPay  = $principal - $penalty;
        if (!$plan->cash_account_id) return '';

        $txn = uniqid('INV-WD-');

        if ($plan->investment_control_account_id) {
            $this->je($txn, $inv->branch_id, $inv->currency_id, $plan->investment_control_account_id, $date, $principal, null, 'Early withdrawal — ' . $inv->account_number);
        }
        $this->je($txn, $inv->branch_id, $inv->currency_id, $plan->cash_account_id, $date, null, $netPay, 'Early withdrawal payout — ' . $inv->account_number);
        if ($penalty > 0 && $plan->interest_expense_account_id) {
            $this->je($txn, $inv->branch_id, $inv->currency_id, $plan->interest_expense_account_id, $date, null, $penalty, 'Early withdrawal penalty income — ' . $inv->account_number);
        }

        return $txn;
    }

    // ── Internal helper: create a single journal entry line ───────────────────
    private function je(string $txn, ?int $branchId, ?int $currencyId, int $accountId, string $date, ?float $debit, ?float $credit, string $notes): void
    {
        [$year, $month] = explode('-', $date);
        $e                      = new JournalEntry();
        $e->created_by_id       = Auth::id() ?? 1;
        $e->transaction_number  = $txn;
        $e->branch_id           = $branchId;
        $e->currency_id         = $currencyId ?? 1;
        $e->chart_of_account_id = $accountId;
        $e->transaction_type    = 'investment';
        $e->date                = $date;
        $e->month               = $month;
        $e->year                = $year;
        $e->debit               = $debit;
        $e->credit              = $credit;
        $e->notes               = $notes;
        $e->manual_entry        = 0;
        $e->reversible          = 1;
        $e->save();
    }
}
