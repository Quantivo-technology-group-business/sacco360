<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoExitSettlement extends Model
{
    protected $fillable = [
        'created_by_id', 'approved_by_id', 'client_id', 'exit_reason', 'exit_date',
        'bosa_balance', 'share_value', 'loan_offset', 'net_payout', 'payout_account',
        'nominee_name', 'status', 'notes',
    ];
}
