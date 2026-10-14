<?php

namespace Modules\Wallet\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Entities\PaymentDetail;
use Modules\Wallet\Entities\Wallet;
use Modules\Wallet\Entities\WalletTransaction;
use Modules\Wallet\Events\TransactionUpdated;

class WalletChannelService
{
    public function postWithdrawal(Wallet $wallet, float $amount, string $channel, string $reference, ?string $date = null): WalletTransaction
    {
        $date = $date ?? date('Y-m-d');
        if ($wallet->status !== 'active') {
            throw new \RuntimeException('Wallet is not active.');
        }
        if ($amount > (float)$wallet->balance) {
            throw new \RuntimeException('Insufficient wallet balance.');
        }

        return DB::transaction(function () use ($wallet, $amount, $channel, $reference, $date) {
            $paymentDetail = new PaymentDetail();
            $paymentDetail->created_by_id = Auth::id();
            $paymentDetail->payment_type_id = 3;
            $paymentDetail->transaction_type = $channel;
            $paymentDetail->receipt = $reference;
            $paymentDetail->save();

            $txn = new WalletTransaction();
            $txn->created_by_id = Auth::id();
            $txn->wallet_id = $wallet->id;
            $txn->branch_id = $wallet->branch_id;
            $txn->payment_detail_id = $paymentDetail->id;
            $txn->name = ucfirst(str_replace('_', ' ', $channel)) . ' Withdrawal';
            $txn->transaction_type = 'withdrawal';
            $txn->submitted_on = $date;
            $txn->created_on = date('Y-m-d');
            $txn->amount = $amount;
            $txn->debit = $amount;
            $txn->reversible = 1;
            $txn->save();

            $wallet->balance = (float)$wallet->balance - $amount;
            $wallet->save();

            event(new TransactionUpdated($wallet));

            return $txn;
        });
    }
}
