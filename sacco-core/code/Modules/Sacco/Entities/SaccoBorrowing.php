<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoBorrowing extends Model
{
    protected $table = 'sacco_borrowings';

    protected $fillable = [
        'created_by_id', 'lender_name', 'principal', 'outstanding', 'interest_rate',
        'disbursement_date', 'maturity_date', 'status', 'sasra_approved', 'notes',
    ];
}
