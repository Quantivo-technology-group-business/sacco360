<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoPesalinkTransaction extends Model
{
    protected $fillable = [
        'created_by_id', 'client_id', 'savings_id', 'direction',
        'beneficiary_account', 'beneficiary_bank', 'beneficiary_name',
        'amount', 'reference', 'ips_reference', 'status', 'response_message',
    ];
}
