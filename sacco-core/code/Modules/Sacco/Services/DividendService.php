<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Sacco\Entities\SaccoDividend;
use Modules\Sacco\Entities\SaccoDividendAllocation;
use Modules\Share\Entities\Share;
use Modules\Savings\Entities\Savings;
use Modules\Savings\Entities\SavingsTransaction;
use Modules\Core\Entities\PaymentDetail;

class DividendService
{
    public function declare(SaccoDividend $dividend): SaccoDividend
    {
        $shares = Share::where('share_product_id', $dividend->share_product_id)
            ->where('status', 'active')
            ->get();

        $totalPool = 0;
        foreach ($shares as $share) {
            if ($share->total_shares <= 0) {
                continue;
            }
            if (!$share->allow_dividends_for_inactive_clients && $share->status !== 'active') {
                continue;
            }
            $amount = $share->total_shares * $dividend->dividend_per_share;
            SaccoDividendAllocation::create([
                'sacco_dividend_id' => $dividend->id,
                'share_id' => $share->id,
                'client_id' => $share->client_id,
                'shares_held' => $share->total_shares,
                'amount' => $amount,
                'status' => 'pending',
            ]);
            $totalPool += $amount;
        }

        $dividend->total_pool = $totalPool;
        $dividend->status = 'declared';
        $dividend->save();
        return $dividend;
    }

    public function process(SaccoDividend $dividend): void
    {
        DB::transaction(function () use ($dividend) {
            $allocations = SaccoDividendAllocation::where('sacco_dividend_id', $dividend->id)
                ->where('status', 'pending')
                ->get();

            foreach ($allocations as $allocation) {
                if ($dividend->payout_method === 'reinvest') {
                    $share = Share::with('share_product')->find($allocation->share_id);
                    if ($share && $share->share_product) {
                        $sharesToAdd = (int)floor($allocation->amount / ($share->share_product->nominal_price ?: 1));
                        $share->total_shares += $sharesToAdd;
                        $share->save();
                    }
                    $allocation->status = 'reinvested';
                } else {
                    $savings = (new WingService())->getBosaAccount($allocation->client_id);
                    if (!$savings) {
                        $savings = Savings::where('client_id', $allocation->client_id)
                            ->where('status', 'active')
                            ->first();
                    }
                    if ($savings) {
                        $paymentDetail = new PaymentDetail();
                        $paymentDetail->created_by_id = Auth::id();
                        $paymentDetail->payment_type_id = 1;
                        $paymentDetail->transaction_type = 'dividend';
                        $paymentDetail->save();

                        $txn = new SavingsTransaction();
                        $txn->created_by_id = Auth::id();
                        $txn->savings_id = $savings->id;
                        $txn->branch_id = $savings->branch_id;
                        $txn->payment_detail_id = $paymentDetail->id;
                        $txn->name = 'Dividend - ' . $dividend->name;
                        $txn->savings_transaction_type_id = 9;
                        $txn->submitted_on = $dividend->payment_date ?? date('Y-m-d');
                        $txn->created_on = date('Y-m-d');
                        $txn->amount = $allocation->amount;
                        $txn->credit = $allocation->amount;
                        $txn->save();
                    }
                    $allocation->status = 'paid';
                }
                $allocation->save();
            }

            $dividend->status = 'processed';
            $dividend->save();
        });
    }
}
