<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class SaccoTransactionOtp extends Model
{
    protected $table = 'sacco_transaction_otps';

    protected $fillable = [
        'client_id', 'purpose', 'reference', 'code_hash', 'expires_at', 'verified_at',
    ];

    protected $dates = ['expires_at', 'verified_at'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
