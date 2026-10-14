<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class SaccoGroupContribution extends Model
{
    protected $fillable = [
        'sacco_group_id', 'sacco_group_member_id', 'client_id',
        'amount', 'contribution_date', 'period',
        'created_by_id', 'status', 'notes',
        'payment_method', 'payment_reference', 'phone_number',
        'approved_by_id', 'approved_at', 'rejection_reason',
        'checkout_request_id', 'merchant_request_id', 'mpesa_receipt_number',
    ];

    protected $dates = ['contribution_date', 'approved_at'];

    public function group()
    {
        return $this->belongsTo(SaccoGroup::class, 'sacco_group_id');
    }

    public function groupMember()
    {
        return $this->belongsTo(SaccoGroupMember::class, 'sacco_group_member_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(\Modules\User\Entities\User::class, 'approved_by_id');
    }

    /**
     * Awaiting officer confirmation of receipt.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Counted toward group totals and member standing.
     */
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }
}
