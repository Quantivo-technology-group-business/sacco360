<?php

namespace Modules\Share\Listeners;

class UpdateShareTotals
{
    public function handle($event): void
    {
        $share = $event->share->load(['transactions', 'share_product']);
        $nominal = $share->nominal_price ?? ($share->share_product->nominal_price ?? 0);
        $balance = 0.0;

        foreach ($share->transactions->where('reversed', 0) as $transaction) {
            $balance += (float)$transaction->credit - (float)$transaction->debit;
            $transaction->balance = $balance;
            $transaction->save();
        }

        if ($nominal > 0 && $share->status === 'active') {
            $share->total_shares = (int)floor($balance / $nominal);
        }

        $share->save();
    }
}
