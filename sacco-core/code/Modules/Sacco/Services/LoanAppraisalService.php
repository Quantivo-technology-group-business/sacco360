<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Modules\Loan\Entities\Loan;
use Modules\Loan\Entities\LoanGuarantor;
use Modules\Loan\Entities\LoanProduct;

class LoanAppraisalService
{
    public function calculateMaxLoanAmount(int $clientId, int $loanProductId, ?int $loanId = null): array
    {
        $product = LoanProduct::findOrFail($loanProductId);
        $method = $product->appraisal_method ?? config('sacco.default_appraisal_method', 'combined');

        $wing = $product->office_wing ?? 'bosa';
        $savingsBalance = $this->getSavingsBalance($clientId, $wing);
        $shareValue = $this->getFreeShareValue($clientId);
        $guarantorCollateral = $this->getGuarantorCollateral($clientId, $loanId);

        $multiplierResult = $this->calculateMultiplier($product, $savingsBalance, $shareValue);
        $twoThirdResult = $this->calculateTwoThirdRule($savingsBalance, $shareValue, $guarantorCollateral);

        switch ($method) {
            case 'multiplier':
                $maxLoan = $multiplierResult['max_loan_amount'];
                $violations = $multiplierResult['violations'];
                $appraisalDetail = ['method' => 'multiplier', 'multiplier' => $multiplierResult];
                break;
            case 'two_third_rule':
                $maxLoan = $twoThirdResult['max_loan_amount'];
                $violations = $twoThirdResult['violations'];
                $appraisalDetail = ['method' => 'two_third_rule', 'two_third' => $twoThirdResult];
                break;
            default:
                $maxLoan = min($multiplierResult['max_loan_amount'], $twoThirdResult['max_loan_amount']);
                $violations = array_merge($multiplierResult['violations'], $twoThirdResult['violations']);
                $appraisalDetail = [
                    'method' => 'combined',
                    'multiplier' => $multiplierResult,
                    'two_third' => $twoThirdResult,
                ];
                break;
        }

        if ($product->max_principal && $maxLoan > $product->max_principal) {
            $maxLoan = $product->max_principal;
        }
        if ($product->min_principal && $maxLoan < $product->min_principal) {
            $violations[] = 'Eligible amount below product minimum principal';
        }

        $minGuarantors = (int)($product->min_guarantors ?? 0);
        $guarantorCount = $loanId
            ? LoanGuarantor::where('loan_id', $loanId)->count()
            : 0;

        if ($minGuarantors > 0 && $guarantorCount < $minGuarantors) {
            $violations[] = sprintf('Requires at least %d guarantor(s), currently %d', $minGuarantors, $guarantorCount);
        }

        return [
            'savings_balance' => (float)$savingsBalance,
            'share_value' => (float)$shareValue,
            'guarantor_collateral' => (float)$guarantorCollateral,
            'own_collateral' => (float)($savingsBalance + $shareValue),
            'total_collateral' => (float)($savingsBalance + $shareValue + $guarantorCollateral),
            'max_loan_amount' => (float)$maxLoan,
            'violations' => array_unique($violations),
            'eligible' => empty($violations) && $maxLoan > 0,
            'appraisal_detail' => $appraisalDetail,
            'guarantor_count' => $guarantorCount,
            'min_guarantors_required' => $minGuarantors,
        ];
    }

    protected function calculateMultiplier(LoanProduct $product, float $savingsBalance, float $shareValue): array
    {
        $multiplier = $product->loan_multiplier ?? config('sacco.loan_multiplier_default', 3);
        $baseAmount = max($savingsBalance, $shareValue);
        $maxLoan = $baseAmount * $multiplier;
        $violations = [];

        if ($product->max_principal && $maxLoan > $product->max_principal) {
            $maxLoan = $product->max_principal;
        }

        return [
            'multiplier' => (float)$multiplier,
            'base_amount' => (float)$baseAmount,
            'max_loan_amount' => (float)$maxLoan,
            'violations' => $violations,
        ];
    }

    protected function calculateTwoThirdRule(float $savingsBalance, float $shareValue, float $guarantorCollateral): array
    {
        $ratio = config('sacco.two_third_ratio', 0.6667);
        $totalCollateral = $savingsBalance + $shareValue + $guarantorCollateral;
        $maxLoan = $totalCollateral * $ratio;
        $violations = [];

        if ($totalCollateral <= 0) {
            $violations[] = 'No qualifying collateral for 2/3 rule appraisal';
        }

        $minGuarantorCoverage = config('sacco.guarantor_min_coverage_ratio', 0.3333);

        return [
            'two_third_ratio' => (float)$ratio,
            'total_collateral' => (float)$totalCollateral,
            'max_loan_amount' => (float)$maxLoan,
            'guarantor_coverage_required' => (float)$minGuarantorCoverage,
            'violations' => $violations,
        ];
    }

    public function validateLoanAmount(Loan $loan, float $amount): array
    {
        $appraisal = $this->calculateMaxLoanAmount($loan->client_id, $loan->loan_product_id, $loan->id);
        $violations = $appraisal['violations'];

        if ($amount > $appraisal['max_loan_amount']) {
            $violations[] = sprintf(
                'Requested amount %.2f exceeds maximum eligible %.2f',
                $amount,
                $appraisal['max_loan_amount']
            );
        }

        $guarantorCoverage = LoanGuarantor::where('loan_id', $loan->id)->sum('guaranteed_amount');
        $minCoverage = $amount * config('sacco.guarantor_min_coverage_ratio', 0.3333);
        if ($minCoverage > 0 && $guarantorCoverage < $minCoverage) {
            $violations[] = sprintf(
                'Guarantors must cover at least %.2f (1/3 of loan), current guarantee %.2f',
                $minCoverage,
                $guarantorCoverage
            );
        }

        return [
            'passed' => empty($violations),
            'violations' => $violations,
            'appraisal' => $appraisal,
        ];
    }

    protected function getSavingsBalance(int $clientId, string $wing = 'bosa'): float
    {
        if ($wing === 'any') {
            return (new WingService())->getSavingsBalanceByWing($clientId, WingService::WING_BOSA)
                + (new WingService())->getSavingsBalanceByWing($clientId, WingService::WING_FOSA);
        }
        return (new WingService())->getSavingsBalanceByWing($clientId, $wing);
    }

    protected function getFreeShareValue(int $clientId): float
    {
        return (new GuarantorLockService())->getFreeShareValue($clientId);
    }

    protected function getGuarantorCollateral(int $clientId, ?int $loanId): float
    {
        if (!$loanId) {
            return 0;
        }

        $guarantorIds = LoanGuarantor::where('loan_id', $loanId)->pluck('client_id');
        $total = 0;
        foreach ($guarantorIds as $gid) {
            $total += $this->getFreeShareValue((int)$gid);
        }
        return $total;
    }
}
