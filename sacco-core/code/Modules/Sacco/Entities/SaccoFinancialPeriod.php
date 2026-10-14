<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoFinancialPeriod extends Model
{
    protected $fillable = [
        'name', 'start_date', 'end_date', 'status', 'closed_by_id', 'closed_at',
    ];
}
