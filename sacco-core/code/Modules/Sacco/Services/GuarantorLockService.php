<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Modules\Loan\Entities\Loan;
use Modules\Loan\Entities\LoanGuarantor;
use Modules\Share\Entities\Share;

class GuarantorLockService
{
    public function lockOnDisbursement(Loan $loan): void
    {
        if (!$loan->lock_guarantee_funds) {
            return;
        }

        $guarantors = LoanGuarantor::where('loan_id', $loan->id)->get();
        foreach ($guarantors as $guarantor) {
            $this->lockGuarantorShares($guarantor);
        }
    }

    public function unlockOnClosure(Loan $loan): void
    {
        $guarantors = LoanGuarantor::where('loan_id', $loan->id)->get();
        foreach ($guarantors as $guarantor) {
            $this->unlockGuarantorShares($guarantor);
        }
    }

    public function lockGuarantorShares(LoanGuarantor $guarantor): void
    {
        $share = null;
        if ($guarantor->share_id) {
            $share = Share::with('share_product')->find($guarantor->share_id);
        } else {
            $share = Share::with('share_product')
                ->where('client_id', $guarantor->client_id)
                ->where('status', 'active')
                ->first();
            if ($share) {
                $guarantor->share_id = $share->id;
            }
        }

        if (!$share || !$share->share_product) {
            return;
        }

        $sharesNeeded = (int)ceil($guarantor->guaranteed_amount / ($share->share_product->nominal_price ?: 1));
        $freeShares = ($share->total_shares ?? 0) - ($share->locked_shares ?? 0);

        if ($sharesNeeded > $freeShares) {
            throw new \RuntimeException('Guarantor does not have sufficient free shares to guarantee this loan');
        }

        $totalGuaranteed = LoanGuarantor::where('client_id', $guarantor->client_id)
            ->where('id', '!=', $guarantor->id)
            ->whereHas('loan', function ($q) {
                $q->whereIn('status', ['active', 'disbursed', 'approved']);
            })
            ->sum('guaranteed_amount');

        $shareValue = $share->total_shares * $share->share_product->nominal_price;
        if (($totalGuaranteed + $guarantor->guaranteed_amount) > $shareValue) {
            throw new \RuntimeException('Guarantor would exceed total share value guarantee limit');
        }

        $share->locked_shares = ($share->locked_shares ?? 0) + $sharesNeeded;
        $share->save();
        $guarantor->locked_shares = $sharesNeeded;
        $guarantor->save();
    }

    public function unlockGuarantorShares(LoanGuarantor $guarantor): void
    {
        if (!$guarantor->share_id || !$guarantor->locked_shares) {
            return;
        }
        $share = Share::find($guarantor->share_id);
        if ($share) {
            $share->locked_shares = max(0, ($share->locked_shares ?? 0) - $guarantor->locked_shares);
            $share->save();
        }
        $guarantor->locked_shares = 0;
        $guarantor->save();
    }

    public function getFreeShareValue(int $clientId): float
    {
        return (float)DB::table('shares')
            ->join('share_products', 'share_products.id', '=', 'shares.share_product_id')
            ->where('shares.client_id', $clientId)
            ->where('shares.status', 'active')
            ->selectRaw('COALESCE(SUM((shares.total_shares - COALESCE(shares.locked_shares, 0)) * share_products.nominal_price), 0) as value')
            ->value('value');
    }
}
