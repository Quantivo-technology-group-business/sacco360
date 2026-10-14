<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;
use Modules\Savings\Entities\Savings;

class SaccoAtmTransaction extends Model
{
    protected $fillable = [
        'terminal_id', 'card_number', 'member_number', 'client_id', 'savings_id',
        'transaction_type', 'amount', 'reference', 'status', 'response_message',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function savings()
    {
        return $this->belongsTo(Savings::class);
    }
}
