<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class SaccoCardPinLog extends Model
{
    protected $fillable = [
        'sacco_debit_card_id', 'client_id',
        'action', 'actioned_by_id', 'channel', 'ip_address',
    ];

    public function card()
    {
        return $this->belongsTo(SaccoDebitCard::class, 'sacco_debit_card_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
