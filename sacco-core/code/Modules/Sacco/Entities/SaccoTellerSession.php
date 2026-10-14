<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoTellerSession extends Model
{
    protected $fillable = [
        'sacco_teller_id', 'user_id', 'opening_balance', 'closing_balance',
        'expected_balance', 'variance', 'opened_at', 'closed_at', 'status', 'notes',
    ];
}
