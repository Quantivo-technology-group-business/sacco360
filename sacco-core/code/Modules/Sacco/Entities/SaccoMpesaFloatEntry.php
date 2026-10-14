<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoMpesaFloatEntry extends Model
{
    protected $fillable = [
        'sacco_mpesa_float_account_id', 'created_by_id',
        'entry_type', 'amount', 'balance_after',
        'entry_date', 'reference', 'notes',
    ];

    protected $dates = ['entry_date'];

    public function account()
    {
        return $this->belongsTo(SaccoMpesaFloatAccount::class, 'sacco_mpesa_float_account_id');
    }
}
