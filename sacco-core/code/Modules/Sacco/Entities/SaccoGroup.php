<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;
use Modules\Branch\Entities\Branch;

class SaccoGroup extends Model
{
    protected $fillable = [
        'created_by_id', 'branch_id', 'name', 'code', 'type',
        'registration_number', 'registration_date', 'max_members',
        'share_value', 'monthly_contribution', 'purpose',
        'chairperson_client_id', 'secretary_client_id', 'treasurer_client_id',
        'group_savings_id', 'status', 'rotation_model', 'late_payment_penalty',
    ];

    protected $casts = [
        'registration_date' => 'date',
        'late_payment_penalty' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function members()
    {
        return $this->hasMany(SaccoGroupMember::class);
    }

    public function activeMembers()
    {
        return $this->hasMany(SaccoGroupMember::class)->where('status', 'active');
    }

    public function contributions()
    {
        return $this->hasMany(SaccoGroupContribution::class);
    }

    public function merryGoRoundCycles()
    {
        return $this->hasMany(SaccoMerryGoRoundCycle::class);
    }

    public function chairperson()
    {
        return $this->belongsTo(Client::class, 'chairperson_client_id');
    }

    public function secretary()
    {
        return $this->belongsTo(Client::class, 'secretary_client_id');
    }

    public function treasurer()
    {
        return $this->belongsTo(Client::class, 'treasurer_client_id');
    }
}
