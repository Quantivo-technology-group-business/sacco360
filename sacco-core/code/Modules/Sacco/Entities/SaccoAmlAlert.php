<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoAmlAlert extends Model
{
    protected $fillable = [
        'client_id', 'alert_type', 'severity', 'description', 'amount',
        'reference_type', 'reference_id', 'status', 'reviewed_by_id', 'reviewed_at',
    ];
}
