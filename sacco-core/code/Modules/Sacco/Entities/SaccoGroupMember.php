<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class SaccoGroupMember extends Model
{
    protected $fillable = [
        'sacco_group_id', 'client_id', 'role',
        'joined_date', 'exit_date', 'shares_held', 'status',
    ];

    protected $dates = ['joined_date', 'exit_date'];

    public function group()
    {
        return $this->belongsTo(SaccoGroup::class, 'sacco_group_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function contributions()
    {
        return $this->hasMany(SaccoGroupContribution::class);
    }
}
