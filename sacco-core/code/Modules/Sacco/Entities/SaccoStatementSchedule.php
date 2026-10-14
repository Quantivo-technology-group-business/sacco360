<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class SaccoStatementSchedule extends Model
{
    protected $fillable = [
        'created_by_id', 'client_id', 'name', 'statement_type',
        'frequency', 'delivery_channel', 'send_day_of_month',
        'lookback_months', 'include_loans', 'include_savings',
        'include_shares', 'include_summary',
        'last_run_date', 'next_run_date', 'is_active',
    ];

    protected $dates = ['last_run_date', 'next_run_date'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function logs()
    {
        return $this->hasMany(SaccoStatementLog::class);
    }
}
