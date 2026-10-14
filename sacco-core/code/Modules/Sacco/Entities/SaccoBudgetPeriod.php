<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoBudgetPeriod extends Model
{
    protected $fillable = [
        'name', 'fiscal_year', 'start_date', 'end_date',
        'status', 'approved_by_id', 'approved_date',
        'created_by_id', 'notes',
    ];

    protected $dates = ['start_date', 'end_date', 'approved_date'];

    public function lines()
    {
        return $this->hasMany(SaccoBudgetLine::class);
    }

    public function getTotalBudgetAttribute()
    {
        return $this->lines()->sum('budget_amount');
    }
}
