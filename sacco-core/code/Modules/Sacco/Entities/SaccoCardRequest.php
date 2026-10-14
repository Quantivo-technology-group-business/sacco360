<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class SaccoCardRequest extends Model
{
    protected $fillable = [
        'client_id', 'sacco_debit_card_id', 'savings_id',
        'created_by_id', 'processed_by_id',
        'request_type', 'replacement_reason', 'replaced_card_id',
        'preferred_name', 'delivery_address', 'delivery_method',
        'card_network', 'card_type',
        'card_fee', 'fee_waived', 'notes',
        'status', 'requested_date', 'processed_date',
    ];

    protected $dates = ['requested_date', 'processed_date'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function card()
    {
        return $this->belongsTo(SaccoDebitCard::class, 'sacco_debit_card_id');
    }

    public function replacedCard()
    {
        return $this->belongsTo(SaccoDebitCard::class, 'replaced_card_id');
    }
}
