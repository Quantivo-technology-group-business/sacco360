<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Modules\Client\Entities\Client;
use Modules\Loan\Entities\LoanGuarantor;
use Modules\Share\Entities\Share;

class Member360Service
{
    public function getSummary(int $clientId): array
    {
        $client = Client::with(['identifications', 'identifications.identification_type'])->findOrFail($clientId);

        $savingsBalance = DB::table('savings')
            ->leftJoin('savings_transactions', 'savings_transactions.savings_id', '=', 'savings.id')
            ->where('savings.client_id', $clientId)
            ->where('savings.status', 'active')
            ->selectRaw('COALESCE(SUM(savings_transactions.credit) - SUM(savings_transactions.debit), 0) as balance')
            ->value('balance') ?? 0;

        $shareData = DB::table('shares')
            ->leftJoin('share_products', 'share_products.id', '=', 'shares.share_product_id')
            ->where('shares.client_id', $clientId)
            ->where('shares.status', 'active')
            ->selectRaw('COALESCE(SUM(shares.total_shares), 0) as total_shares')
            ->selectRaw('COALESCE(SUM(shares.locked_shares), 0) as locked_shares')
            ->selectRaw('COALESCE(SUM(shares.total_shares * share_products.nominal_price), 0) as share_value')
            ->first();

        $loanData = DB::table('loans')
            ->leftJoin('loan_transactions', 'loan_transactions.loan_id', '=', 'loans.id')
            ->where('loans.client_id', $clientId)
            ->whereIn('loans.status', ['active', 'approved', 'disbursed'])
            ->selectRaw('COUNT(DISTINCT loans.id) as active_loans')
            ->selectRaw('COALESCE(SUM(loan_transactions.debit) - SUM(loan_transactions.credit), 0) as loan_balance')
            ->first();

        $walletBalance = DB::table('wallets')
            ->leftJoin('wallet_transactions', 'wallet_transactions.wallet_id', '=', 'wallets.id')
            ->where('wallets.client_id', $clientId)
            ->where('wallets.status', 'active')
            ->selectRaw('COALESCE(SUM(wallet_transactions.credit) - SUM(wallet_transactions.debit), 0) as balance')
            ->value('balance') ?? 0;

        $guarantorExposure = LoanGuarantor::where('client_id', $clientId)->sum('guaranteed_amount');

        $guaranteesGiven = LoanGuarantor::whereIn('loan_id',
            DB::table('loans')->where('client_id', $clientId)->pluck('id')
        )->count();

        $arrearsAmount = $this->getArrearsAmount($clientId);

        $kycService = class_exists(KycService::class) ? new KycService() : null;
        $kycVerified = $kycService ? $kycService->isVerified($clientId) : (bool)($client->kyc_verified ?? false);
        $kycStatus = $kycService ? $kycService->resolveStatus($client) : ($kycVerified ? 'verified' : ($client->identifications->count() > 0 ? 'pending_review' : 'incomplete'));
        $hasIdentification = $client->identifications->count() > 0;

        $earmarks = class_exists(EarmarkService::class)
            ? (new EarmarkService())->getActiveForClient($clientId)
            : [];

        $memberNumber = $client->member_number ?? $client->external_id ?? null;
        if (empty($memberNumber) && class_exists(MemberNumberService::class)) {
            $memberNumber = (new MemberNumberService())->assign($client);
        }

        $wingService = class_exists(WingService::class) ? new WingService() : null;
        $bosaBalance = $wingService ? $wingService->getSavingsBalanceByWing($clientId, WingService::WING_BOSA) : 0;
        $fosaBalance = $wingService ? $wingService->getSavingsBalanceByWing($clientId, WingService::WING_FOSA) : 0;
        $crossSell = class_exists(CrossSellService::class) ? (new CrossSellService())->suggestForMember($clientId) : [];

        return [
            'client' => $client,
            'member_number' => $memberNumber,
            'active_earmarks' => $earmarks,
            'has_blocking_earmark' => collect($earmarks)->contains(function ($e) {
                return $e['blocks_transactions'] ?? false;
            }),
            'savings_balance' => (float)$savingsBalance,
            'bosa_balance' => (float)$bosaBalance,
            'fosa_balance' => (float)$fosaBalance,
            'cross_sell_suggestions' => $crossSell,
            'share_total' => (int)($shareData->total_shares ?? 0),
            'share_locked' => (int)($shareData->locked_shares ?? 0),
            'share_value' => (float)($shareData->share_value ?? 0),
            'share_free' => (int)(($shareData->total_shares ?? 0) - ($shareData->locked_shares ?? 0)),
            'loan_balance' => max(0, (float)($loanData->loan_balance ?? 0)),
            'active_loans' => (int)($loanData->active_loans ?? 0),
            'wallet_balance' => (float)$walletBalance,
            'guarantor_exposure' => (float)$guarantorExposure,
            'guarantees_given_count' => $guaranteesGiven,
            'arrears_amount' => $arrearsAmount,
            'kyc_verified' => $kycVerified,
            'kyc_status' => $kycStatus,
            'iprs_verified' => !empty($client->iprs_verified_at),
            'kyc_expiry_date' => $client->kyc_expiry_date ?? null,
            'total_assets' => (float)$savingsBalance + (float)($shareData->share_value ?? 0) + (float)$walletBalance,
            'net_position' => (float)$savingsBalance + (float)($shareData->share_value ?? 0) + (float)$walletBalance - max(0, (float)($loanData->loan_balance ?? 0)),
        ];
    }

    protected function getArrearsAmount(int $clientId): float
    {
        return (float)DB::table('loan_repayment_schedules')
            ->join('loans', 'loans.id', '=', 'loan_repayment_schedules.loan_id')
            ->where('loans.client_id', $clientId)
            ->whereIn('loans.status', ['active', 'disbursed'])
            ->where('loan_repayment_schedules.due_date', '<', date('Y-m-d'))
            ->whereRaw('(loan_repayment_schedules.principal + loan_repayment_schedules.interest + loan_repayment_schedules.fees + loan_repayment_schedules.penalties - loan_repayment_schedules.principal_repaid_derived - loan_repayment_schedules.interest_repaid_derived - loan_repayment_schedules.fees_repaid_derived - loan_repayment_schedules.penalties_repaid_derived) > 0')
            ->selectRaw('SUM(loan_repayment_schedules.principal + loan_repayment_schedules.interest + loan_repayment_schedules.fees + loan_repayment_schedules.penalties - loan_repayment_schedules.principal_repaid_derived - loan_repayment_schedules.interest_repaid_derived - loan_repayment_schedules.fees_repaid_derived - loan_repayment_schedules.penalties_repaid_derived) as arrears')
            ->value('arrears') ?? 0;
    }

    public function getActivityTimeline(int $clientId, int $limit = 20): array
    {
        return DB::table('activity_log')
            ->where('subject_type', 'like', '%Client%')
            ->where('subject_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Efficient bulk 360 summaries for a paginated member list.
     */
    public function getBulkSummaries(array $clientIds): array
    {
        if (empty($clientIds)) {
            return [];
        }

        $savings = DB::table('savings')
            ->leftJoin('savings_transactions', 'savings_transactions.savings_id', '=', 'savings.id')
            ->whereIn('savings.client_id', $clientIds)
            ->where('savings.status', 'active')
            ->groupBy('savings.client_id')
            ->selectRaw('savings.client_id, COALESCE(SUM(savings_transactions.credit) - SUM(savings_transactions.debit), 0) as balance')
            ->pluck('balance', 'client_id');

        $shares = DB::table('shares')
            ->leftJoin('share_products', 'share_products.id', '=', 'shares.share_product_id')
            ->whereIn('shares.client_id', $clientIds)
            ->where('shares.status', 'active')
            ->groupBy('shares.client_id')
            ->selectRaw('shares.client_id')
            ->selectRaw('COALESCE(SUM(shares.total_shares), 0) as total_shares')
            ->selectRaw('COALESCE(SUM(shares.total_shares * share_products.nominal_price), 0) as share_value')
            ->get()
            ->keyBy('client_id');

        $loans = DB::table('loans')
            ->leftJoin('loan_transactions', 'loan_transactions.loan_id', '=', 'loans.id')
            ->whereIn('loans.client_id', $clientIds)
            ->whereIn('loans.status', ['active', 'approved', 'disbursed'])
            ->groupBy('loans.client_id')
            ->selectRaw('loans.client_id')
            ->selectRaw('COUNT(DISTINCT loans.id) as active_loans')
            ->selectRaw('COALESCE(SUM(loan_transactions.debit) - SUM(loan_transactions.credit), 0) as loan_balance')
            ->get()
            ->keyBy('client_id');

        $wallets = DB::table('wallets')
            ->leftJoin('wallet_transactions', 'wallet_transactions.wallet_id', '=', 'wallets.id')
            ->whereIn('wallets.client_id', $clientIds)
            ->where('wallets.status', 'active')
            ->groupBy('wallets.client_id')
            ->selectRaw('wallets.client_id, COALESCE(SUM(wallet_transactions.credit) - SUM(wallet_transactions.debit), 0) as balance')
            ->pluck('balance', 'client_id');

        $arrears = DB::table('loan_repayment_schedules')
            ->join('loans', 'loans.id', '=', 'loan_repayment_schedules.loan_id')
            ->whereIn('loans.client_id', $clientIds)
            ->whereIn('loans.status', ['active', 'disbursed'])
            ->where('loan_repayment_schedules.due_date', '<', date('Y-m-d'))
            ->whereRaw('(loan_repayment_schedules.principal + loan_repayment_schedules.interest + loan_repayment_schedules.fees + loan_repayment_schedules.penalties - loan_repayment_schedules.principal_repaid_derived - loan_repayment_schedules.interest_repaid_derived - loan_repayment_schedules.fees_repaid_derived - loan_repayment_schedules.penalties_repaid_derived) > 0')
            ->groupBy('loans.client_id')
            ->selectRaw('loans.client_id, SUM(loan_repayment_schedules.principal + loan_repayment_schedules.interest + loan_repayment_schedules.fees + loan_repayment_schedules.penalties - loan_repayment_schedules.principal_repaid_derived - loan_repayment_schedules.interest_repaid_derived - loan_repayment_schedules.fees_repaid_derived - loan_repayment_schedules.penalties_repaid_derived) as arrears')
            ->pluck('arrears', 'client_id');

        $clients = Client::whereIn('id', $clientIds)
            ->select('id', 'member_number', 'external_id', 'kyc_verified', 'iprs_verified_at', 'status')
            ->get()
            ->keyBy('id');

        $summaries = [];
        foreach ($clientIds as $clientId) {
            $client = $clients->get($clientId);
            if (!$client) {
                continue;
            }
            $shareRow = $shares->get($clientId);
            $loanRow = $loans->get($clientId);
            $savingsBalance = (float)($savings[$clientId] ?? 0);
            $shareValue = (float)($shareRow->share_value ?? 0);
            $walletBalance = (float)($wallets[$clientId] ?? 0);
            $loanBalance = max(0, (float)($loanRow->loan_balance ?? 0));

            $summaries[$clientId] = [
                'member_number' => $client->member_number ?? $client->external_id ?? null,
                'savings_balance' => $savingsBalance,
                'share_value' => $shareValue,
                'share_total' => (int)($shareRow->total_shares ?? 0),
                'loan_balance' => $loanBalance,
                'active_loans' => (int)($loanRow->active_loans ?? 0),
                'wallet_balance' => $walletBalance,
                'arrears_amount' => (float)($arrears[$clientId] ?? 0),
                'kyc_status' => !empty($client->kyc_verified) ? 'verified' : (!empty($client->iprs_verified_at) ? 'pending_review' : 'incomplete'),
                'total_assets' => $savingsBalance + $shareValue + $walletBalance,
                'net_position' => $savingsBalance + $shareValue + $walletBalance - $loanBalance,
            ];
        }

        return $summaries;
    }

    public function getPortfolioTotals(array $clientIds): array
    {
        $summaries = $this->getBulkSummaries($clientIds);

        return [
            'members' => count($summaries),
            'total_savings' => array_sum(array_column($summaries, 'savings_balance')),
            'total_shares' => array_sum(array_column($summaries, 'share_value')),
            'total_loans' => array_sum(array_column($summaries, 'loan_balance')),
            'total_arrears' => array_sum(array_column($summaries, 'arrears_amount')),
            'net_position' => array_sum(array_column($summaries, 'net_position')),
        ];
    }
}
