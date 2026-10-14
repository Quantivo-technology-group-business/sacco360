<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoCheque extends Model
{
    protected $fillable = [
        'created_by_id', 'client_id', 'savings_transaction_id', 'cheque_number',
        'bank_name', 'micr_code', 'amount', 'cheque_date', 'presented_date',
        'cleared_date', 'image_path', 'status', 'notes',
    ];
}
