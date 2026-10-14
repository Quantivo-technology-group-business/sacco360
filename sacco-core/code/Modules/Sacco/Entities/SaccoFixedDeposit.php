<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoFixedDeposit extends Model
{
    protected $fillable = [
        'savings_id', 'client_id', 'principal', 'interest_rate', 'tenure_months',
        'start_date', 'maturity_date', 'status', 'renewal_option', 'accrued_interest',
        'penalty_rate', 'lien_loan_id',
    ];

    public function savings()
    {
        return $this->belongsTo(\Modules\Savings\Entities\Savings::class, 'savings_id');
    }
}
