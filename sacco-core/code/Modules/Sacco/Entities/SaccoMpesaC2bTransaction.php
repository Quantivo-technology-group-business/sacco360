<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoMpesaC2bTransaction extends Model
{
    protected $fillable = [
        'transaction_type', 'trans_id', 'trans_time', 'trans_amount', 'business_short_code',
        'bill_ref_number', 'msisdn', 'client_id', 'savings_id', 'savings_transaction_id',
        'status', 'response_message',
    ];
}
