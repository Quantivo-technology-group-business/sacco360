<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Modules\Client\Entities\Client;
use Modules\Loan\Entities\Loan;
use Modules\Loan\Entities\LoanProduct;

class SalaryAdvanceService
{
    public function getEligibleAmount(int $clientId, int $loanProductId): array
    {
        $product = LoanProduct::findOrFail($loanProductId);
        $client = Client::findOrFail($clientId);

        if (!($product->is_salary_advance ?? 0)) {
            return ['eligible' => false, 'max_amount' => 0, 'violations' => ['Product is not configured as salary advance']];
        }

        $fosaBalance = (new WingService())->getSavingsBalanceByWing($clientId, WingService::WING_FOSA);
        $maxPercent = config('sacco.salary_advance_max_percent', 50);
        $maxAmount = $fosaBalance * ($maxPercent / 100);

        if ($product->max_principal && $maxAmount > $product->max_principal) {
            $maxAmount = $product->max_principal;
        }

        $violations = [];
        if (empty($client->employer) && empty($client->employer_id ?? null)) {
            $violations[] = 'Member must have employer details for salary advance';
        }

        $outstanding = Loan::where('client_id', $clientId)
            ->where('loan_product_id', $loanProductId)
            ->whereIn('status', ['active', 'disbursed', 'pending'])
            ->exists();
        if ($outstanding) {
            $violations[] = 'Member already has an outstanding salary advance';
        }

        return [
            'eligible' => empty($violations) && $maxAmount > 0,
            'max_amount' => (float)$maxAmount,
            'fosa_balance' => $fosaBalance,
            'employer' => $client->employer ?? $client->employer_id ?? null,
            'violations' => $violations,
        ];
    }

    public function validateApplication(Loan $loan): array
    {
        $product = $loan->loan_product;
        if (!($product->is_salary_advance ?? 0)) {
            return ['passed' => true, 'violations' => []];
        }

        $check = $this->getEligibleAmount($loan->client_id, $loan->loan_product_id);
        $violations = $check['violations'];

        if ($loan->principal > $check['max_amount']) {
            $violations[] = sprintf('Amount %.2f exceeds salary advance limit %.2f', $loan->principal, $check['max_amount']);
        }

        return ['passed' => empty($violations), 'violations' => $violations, 'check' => $check];
    }
}
