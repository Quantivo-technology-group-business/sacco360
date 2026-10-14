<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoRotationBid extends Model
{
    protected $table = 'sacco_rotation_bids';

    protected $fillable = [
        'sacco_group_id',
        'cycle_number',
        'client_id',
        'bid_amount',
        'status',
    ];

    protected $casts = [
        'bid_amount' => 'decimal:2',
    ];
}
