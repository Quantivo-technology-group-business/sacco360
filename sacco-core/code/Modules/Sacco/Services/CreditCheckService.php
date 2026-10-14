<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Modules\Loan\Entities\Loan;
use Modules\Loan\Entities\LoanCreditCheck;
use Modules\Loan\Entities\LoanLinkedCreditCheck;
use Modules\Loan\Entities\LoanProductLinkedCreditCheck;

class CreditCheckService
{
    public function runChecks(int $loanId): array
    {
        $loan = Loan::with('loan_product')->findOrFail($loanId);
        $productChecks = LoanProductLinkedCreditCheck::where('loan_product_id', $loan->loan_product_id)->get();
        $results = [];
        $passed = true;

        foreach ($productChecks as $link) {
            $check = LoanCreditCheck::find($link->loan_credit_check_id);
            if (!$check) {
                continue;
            }
            $result = $this->executeCheck($check, $loan);
            $results[] = $result;
            if ($result['status'] === 'fail' && $check->security_level === 'block') {
                $passed = false;
            }
            LoanLinkedCreditCheck::updateOrCreate(
                ['loan_id' => $loan->id, 'loan_credit_check_id' => $check->id],
                ['status' => $result['status'], 'message' => $result['message']]
            );
        }

        return ['passed' => $passed, 'checks' => $results];
    }

    protected function executeCheck(LoanCreditCheck $check, Loan $loan): array
    {
        $name = strtolower($check->name);

        if (strpos($name, 'written-off') !== false) {
            $count = DB::table('loans')->where('client_id', $loan->client_id)->where('status', 'written_off')->count();
            return $this->formatResult($check, $count === 0, 'Client has written-off loans');
        }

        if (strpos($name, 'same product') !== false) {
            $count = DB::table('loans')
                ->where('client_id', $loan->client_id)
                ->where('loan_product_id', $loan->loan_product_id)
                ->whereIn('status', ['active', 'disbursed', 'approved'])
                ->where('id', '!=', $loan->id)
                ->count();
            return $this->formatResult($check, $count === 0, 'Client has active loan for same product');
        }

        if (strpos($name, 'arrears') !== false) {
            $arrears = (new Member360Service())->getSummary($loan->client_id)['arrears_amount'];
            return $this->formatResult($check, $arrears <= 0, 'Client has arrears on existing loans');
        }

        if (strpos($name, 'outstanding') !== false) {
            $balance = DB::table('loan_transactions')
                ->where('loan_id', '!=', $loan->id)
                ->whereIn('loan_id', function ($q) use ($loan) {
                    $q->select('id')->from('loans')->where('client_id', $loan->client_id)->whereIn('status', ['active', 'disbursed']);
                })
                ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as bal')
                ->value('bal');
            return $this->formatResult($check, $balance <= 0, 'Client has outstanding loan balance');
        }

        if (strpos($name, 'collateral') !== false) {
            $collateralValue = (float)DB::table('loan_collateral')
                ->where('loan_id', $loan->id)
                ->where('status', 'active')
                ->sum('value');
            $ok = $collateralValue >= (float)$loan->principal;
            return $this->formatResult(
                $check,
                $ok,
                sprintf('Collateral value %.2f is less than loan principal %.2f', $collateralValue, $loan->principal)
            );
        }

        return ['status' => 'pass', 'message' => $check->general_success_msg, 'check' => $check->name];
    }

    protected function formatResult(LoanCreditCheck $check, bool $ok, string $failMsg): array
    {
        return [
            'check' => $check->name,
            'status' => $ok ? 'pass' : ($check->security_level === 'block' ? 'fail' : 'warn'),
            'message' => $ok ? $check->general_success_msg : $failMsg,
        ];
    }
}
