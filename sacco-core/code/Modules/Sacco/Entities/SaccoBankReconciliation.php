<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\User\Entities\User;

class SaccoBankReconciliation extends Model
{
    protected $fillable = [
        'created_by_id', 'chart_of_account_id', 'branch_id', 'statement_date',
        'statement_balance', 'book_balance', 'reconciled_balance', 'variance',
        'status', 'notes',
    ];

    public function items()
    {
        return $this->hasMany(SaccoBankReconciliationItem::class);
    }

    public function chart_of_account()
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
