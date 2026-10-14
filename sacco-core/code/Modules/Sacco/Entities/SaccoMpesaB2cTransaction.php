<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;
use Modules\Savings\Entities\Savings;
use Modules\Wallet\Entities\Wallet;

class SaccoMpesaB2cTransaction extends Model
{
    protected $fillable = [
        'created_by_id', 'client_id', 'source_type', 'savings_id', 'wallet_id',
        'transaction_type', 'beneficiary_id', 'recipient_phone', 'recipient_name',
        'amount', 'reference', 'conversation_id', 'originator_conversation_id',
        'mpesa_receipt', 'status', 'response_message', 'narration',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function beneficiary()
    {
        return $this->belongsTo(SaccoBeneficiary::class, 'beneficiary_id');
    }

    public function savings()
    {
        return $this->belongsTo(Savings::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
