<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class SaccoMerryGoRoundCycle extends Model
{
    protected $fillable = [
        'sacco_group_id', 'cycle_number', 'cycle_name',
        'beneficiary_client_id', 'pot_amount', 'cycle_date',
        'status', 'created_by_id', 'notes',
        'bidding_discount', 'penalties_deducted', 'net_payout_amount',
        'disbursed_at', 'disbursement_reference'
    ];

    protected $casts = [
        'cycle_date' => 'date',
        'disbursed_at' => 'datetime',
        'bidding_discount' => 'decimal:2',
        'penalties_deducted' => 'decimal:2',
        'net_payout_amount' => 'decimal:2',
    ];

    public function group()
    {
        return $this->belongsTo(SaccoGroup::class, 'sacco_group_id');
    }

    public function beneficiary()
    {
        return $this->belongsTo(Client::class, 'beneficiary_client_id');
    }
}
