<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\Entities\ChartOfAccount;

class SaccoBudgetLine extends Model
{
    protected $fillable = [
        'sacco_budget_period_id', 'chart_of_account_id',
        'budget_amount', 'q1_amount', 'q2_amount', 'q3_amount', 'q4_amount',
        'created_by_id', 'notes',
    ];

    public function period()
    {
        return $this->belongsTo(SaccoBudgetPeriod::class, 'sacco_budget_period_id');
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }
}
