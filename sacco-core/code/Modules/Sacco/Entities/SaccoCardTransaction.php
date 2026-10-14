<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class SaccoCardTransaction extends Model
{
    protected $fillable = [
        'sacco_debit_card_id', 'client_id', 'savings_id',
        'transaction_reference', 'external_reference',
        'channel', 'transaction_type',
        'amount', 'currency', 'fee_amount',
        'merchant_name', 'merchant_category', 'merchant_location', 'terminal_id',
        'balance_before', 'balance_after',
        'status', 'decline_reason',
        'transaction_at', 'is_international',
    ];

    protected $dates = ['transaction_at'];

    public function card()
    {
        return $this->belongsTo(SaccoDebitCard::class, 'sacco_debit_card_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function dispute()
    {
        return $this->hasOne(SaccoCardDispute::class, 'sacco_card_transaction_id');
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->amount + (float) $this->fee_amount;
    }
}
