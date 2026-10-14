<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\User\Entities\User;

class SaccoTeller extends Model
{
    protected $fillable = [
        'user_id', 'branch_id', 'till_number', 'opening_balance',
        'current_balance', 'max_single_transaction', 'max_daily_withdrawal',
        'max_cash_on_hand', 'status', 'opened_at', 'closed_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sessions()
    {
        return $this->hasMany(SaccoTellerSession::class);
    }
}
