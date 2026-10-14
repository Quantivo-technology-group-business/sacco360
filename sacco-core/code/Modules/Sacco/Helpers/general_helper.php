<?php

use Modules\Sacco\Entities\SaccoPendingApproval;
use Modules\Sacco\Services\LoanAppraisalService;
use Modules\Sacco\Services\MakerCheckerService;
use Modules\Sacco\Services\Member360Service;
use Modules\Sacco\Services\CreditCheckService;
use Modules\Sacco\Services\ShareRegulatoryService;

if (!function_exists('sacco_member_summary')) {
    function sacco_member_summary(int $clientId): array
    {
        return (new Member360Service())->getSummary($clientId);
    }
}

if (!function_exists('sacco_loan_appraisal')) {
    function sacco_loan_appraisal(int $clientId, int $loanProductId): array
    {
        return (new LoanAppraisalService())->calculateMaxLoanAmount($clientId, $loanProductId);
    }
}

if (!function_exists('sacco_run_credit_checks')) {
    function sacco_run_credit_checks(int $loanId): array
    {
        return (new CreditCheckService())->runChecks($loanId);
    }
}

if (!function_exists('sacco_requires_maker_checker')) {
    function sacco_requires_maker_checker(float $amount): bool
    {
        return (new MakerCheckerService())->requiresApproval($amount);
    }
}

if (!function_exists('sacco_queue_transaction_approval')) {
    function sacco_queue_transaction_approval(string $type, int $id, float $amount, ?string $notes = null): SaccoPendingApproval
    {
        return (new MakerCheckerService())->submitForApproval($type, $id, $amount, $notes);
    }
}

if (!function_exists('sacco_queue_approval')) {
    /**
     * Queue a maker-checker approval with an optional payload for deferred execution.
     *
     * @param array<string,mixed> $payload
     */
    function sacco_queue_approval(string $type, int $id, float $amount, ?string $notes = null, ?string $label = null, array $payload = []): SaccoPendingApproval
    {
        return (new MakerCheckerService())->submitForApproval($type, $id, $amount, $notes, $label, $payload);
    }
}

if (!function_exists('sacco_validate_share_purchase')) {
    function sacco_validate_share_purchase(int $clientId, float $additionalValue, $product = null): array
    {
        return (new ShareRegulatoryService())->validateAdditionalPurchase($clientId, $additionalValue, $product);
    }
}

if (!function_exists('sacco_check_earmarks')) {
    function sacco_check_earmarks(int $clientId, string $transactionType, float $amount = 0): array
    {
        if (!class_exists(\Modules\Sacco\Services\EarmarkService::class)) {
            return ['allowed' => true, 'violations' => [], 'earmarks' => []];
        }
        return (new \Modules\Sacco\Services\EarmarkService())->validateTransaction($clientId, $transactionType, $amount);
    }
}

if (!function_exists('sacco_validate_teller_transaction')) {
    function sacco_validate_teller_transaction(string $type, float $amount): array
    {
        if (!class_exists(\Modules\Sacco\Services\TellerService::class)) {
            return ['allowed' => true, 'violations' => []];
        }
        return (new \Modules\Sacco\Services\TellerService())->validateTransaction($type, $amount);
    }
}

if (!function_exists('sacco_record_teller_transaction')) {
    function sacco_record_teller_transaction(string $type, float $amount): void
    {
        if (!class_exists(\Modules\Sacco\Services\TellerService::class)) {
            return;
        }
        $teller = (new \Modules\Sacco\Services\TellerService())->getOpenTeller();
        if ($teller) {
            (new \Modules\Sacco\Services\TellerService())->recordTransaction($teller, $amount, $type);
        }
    }
}

if (!function_exists('sacco_assign_member_number')) {
    function sacco_assign_member_number($client): string
    {
        return (new \Modules\Sacco\Services\MemberNumberService())->assign($client);
    }
}

if (!function_exists('sacco_validate_member_transaction')) {
    function sacco_validate_member_transaction(int $clientId): array
    {
        return (new \Modules\Sacco\Services\ProhibitedBusinessGuard())->validateMemberTransaction($clientId);
    }
}

if (!function_exists('sacco_validate_kyc')) {
    function sacco_validate_kyc(int $clientId): array
    {
        if (!class_exists(\Modules\Sacco\Services\KycService::class)) {
            return ['allowed' => true];
        }

        return (new \Modules\Sacco\Services\KycService())->canTransact($clientId);
    }
}

if (!function_exists('sacco_validate_collateral')) {
    function sacco_validate_collateral(string $type): array
    {
        return (new \Modules\Sacco\Services\ProhibitedBusinessGuard())->validateCollateralType($type);
    }
}

if (!function_exists('sacco_validate_member_exposure')) {
    function sacco_validate_member_exposure(int $clientId, float $additional = 0): array
    {
        return (new \Modules\Sacco\Services\ProhibitedBusinessGuard())->validateMemberExposure($clientId, $additional);
    }
}

if (!function_exists('sacco_requires_maker_checker_for')) {
    function sacco_requires_maker_checker_for(float $amount, ?string $type = null): bool
    {
        return (new MakerCheckerService())->requiresApproval($amount, $type);
    }
}

if (!function_exists('sacco_compliance_log')) {
    function sacco_compliance_log(string $action, ?string $module = null, ?string $subjectType = null, ?int $subjectId = null, ?string $description = null, ?array $properties = null): void
    {
        (new \Modules\Sacco\Services\ComplianceAuditService())->log($action, $module, $subjectType, $subjectId, $description, $properties);
    }
}

if (!function_exists('sacco_prudential_ratios')) {
    function sacco_prudential_ratios(?string $asOfDate = null): array
    {
        return (new \Modules\Sacco\Services\PrudentialRatioService())->calculateAll($asOfDate ?? date('Y-m-d'));
    }
}

if (!function_exists('sacco_get_wing')) {
    function sacco_get_wing($savings): string
    {
        return (new \Modules\Sacco\Services\WingService())->getAccountWing($savings);
    }
}

if (!function_exists('sacco_validate_withdrawal')) {
    function sacco_validate_withdrawal($savings, float $amount, bool $exitSettlement = false): array
    {
        $wingService = new \Modules\Sacco\Services\WingService();
        $withdrawable = $wingService->isWithdrawable($savings, null, $exitSettlement);
        if (!$withdrawable['allowed']) {
            return $withdrawable;
        }
        $amountCheck = $wingService->validateWithdrawalAmount($savings, $amount);
        return [
            'allowed' => $amountCheck['allowed'],
            'violations' => array_merge($withdrawable['violations'], $amountCheck['violations']),
            'wing' => $withdrawable['wing'],
        ];
    }
}

if (!function_exists('sacco_get_fosa_account')) {
    function sacco_get_fosa_account(int $clientId)
    {
        return (new \Modules\Sacco\Services\WingService())->getFosaAccount($clientId);
    }
}

if (!function_exists('sacco_get_bosa_account')) {
    function sacco_get_bosa_account(int $clientId)
    {
        return (new \Modules\Sacco\Services\WingService())->getBosaAccount($clientId);
    }
}
