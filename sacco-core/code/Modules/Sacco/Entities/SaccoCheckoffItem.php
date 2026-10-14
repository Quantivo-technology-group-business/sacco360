<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoCheckoffItem extends Model
{
    protected $fillable = [
        'sacco_checkoff_batch_id', 'employee_id', 'employee_name', 'loan_account_number',
        'loan_id', 'client_id', 'amount', 'status', 'error_message',
    ];

    public function batch()
    {
        return $this->belongsTo(SaccoCheckoffBatch::class, 'sacco_checkoff_batch_id');
    }
}
