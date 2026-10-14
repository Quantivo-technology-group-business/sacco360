<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoKycVerification extends Model
{
    protected $fillable = [
        'client_id', 'verified_by_id', 'provider', 'id_number',
        'first_name', 'last_name', 'match_status', 'response_data', 'notes',
    ];

    protected $casts = [
        'response_data' => 'array',
    ];
}
