<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;

class ProhibitedBusinessGuard
{
    public function validateMemberTransaction(int $clientId): array
    {
        $client = DB::table('clients')->where('id', $clientId)->first();
        if (!$client || $client->status !== 'active') {
            return ['allowed' => false, 'reason' => 'Transactions with non-members or inactive members are prohibited.'];
        }

        if (class_exists(KycService::class) && config('sacco.require_kyc_for_transactions', true)) {
            $kycCheck = (new KycService())->canTransact($clientId);
            if (!$kycCheck['allowed']) {
                return $kycCheck;
            }
        }

        return ['allowed' => true];
    }

    public function validateCollateralType(string $type): array
    {
        $normalized = strtolower($type);
        if ($normalized === 'shares' || str_contains($normalized, 'share')) {
            return ['allowed' => false, 'reason' => 'Shares cannot be used as loan collateral (SASRA requirement).'];
        }

        return ['allowed' => true];
    }

    public function validateJournalAccount(int $chartOfAccountId): array
    {
        $account = DB::table('chart_of_accounts')->where('id', $chartOfAccountId)->first();
        if (!$account) {
            return ['allowed' => true];
        }

        $prohibited = ['foreign_trade', 'trust_operations', 'enterprise_capital'];
        if (in_array($account->sasra_category ?? '', $prohibited, true)) {
            return ['allowed' => false, 'reason' => "Account '{$account->name}' is tagged as prohibited business."];
        }

        return ['allowed' => true];
    }

    public function validateLandPurchase(?int $boardResolutionId = null): array
    {
        if (!$boardResolutionId) {
            return ['allowed' => false, 'reason' => 'Land purchase requires board resolution approval.'];
        }

        $resolution = DB::table('sacco_board_resolutions')->where('id', $boardResolutionId)->where('status', 'approved')->first();
        if (!$resolution) {
            return ['allowed' => false, 'reason' => 'Valid board resolution required for land purchase.'];
        }

        return ['allowed' => true];
    }

    public function validateInsiderLoan(int $clientId, ?int $boardResolutionId = null): array
    {
        $isInsider = DB::table('users')->where('client_id', $clientId)->exists()
            || DB::table('sacco_fit_proper_records')->where('person_name', 'like', '%')->exists();

        if (!$isInsider) {
            return ['allowed' => true, 'is_insider' => false];
        }

        if (!$boardResolutionId) {
            return ['allowed' => false, 'is_insider' => true, 'reason' => 'Insider lending requires board resolution.'];
        }

        return ['allowed' => true, 'is_insider' => true];
    }

    public function validateMemberExposure(int $clientId, float $additionalExposure = 0): array
    {
        $prudential = new PrudentialRatioService();
        $coreCapital = $prudential->getCoreCapital(date('Y-m-d'));
        if ($coreCapital <= 0) {
            return ['allowed' => true];
        }

        $current = (float) DB::table('loans')
            ->join('loan_transactions', 'loan_transactions.loan_id', '=', 'loans.id')
            ->where('loans.client_id', $clientId)
            ->whereIn('loans.status', ['active', 'disbursed'])
            ->selectRaw('COALESCE(SUM(loan_transactions.debit) - SUM(loan_transactions.credit), 0) as exposure')
            ->value('exposure');

        $total = $current + $additionalExposure;
        $ratio = ($total / $coreCapital) * 100;
        $max = config('sacco.single_member_exposure_max', 10);

        if ($ratio > $max) {
            return [
                'allowed' => false,
                'reason' => "Member exposure ({$ratio}%) exceeds {$max}% of core capital limit.",
            ];
        }

        return ['allowed' => true, 'ratio' => round($ratio, 2)];
    }
}
