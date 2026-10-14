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
    ];

    protected $dates = ['contribution_date'];

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
}
