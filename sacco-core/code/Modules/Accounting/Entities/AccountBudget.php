<?php

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Branch\Entities\Branch;
use Modules\User\Entities\User;

class AccountBudget extends Model
{
    protected $table = 'account_budgets';
    protected $fillable = [
        'chart_of_account_id', 'branch_id', 'year', 'month',
        'amount', 'notes', 'created_by_id',
    ];

    public function chart_of_account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /** Month name accessor */
    public function getMonthNameAttribute()
    {
        if (!$this->month) return 'Annual';
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }
}
