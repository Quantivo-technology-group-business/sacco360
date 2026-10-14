<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoDormancyRule extends Model
{
    protected $fillable = [
        'name', 'description', 'inactivity_months', 'minimum_balance',
        'block_withdrawals', 'block_new_loans',
        'charge_dormancy_fee', 'dormancy_fee_amount', 'fee_frequency',
        'notify_member_on_flag', 'is_active',
    ];

    public function logs()
    {
        return $this->hasMany(SaccoDormancyLog::class);
    }
}
