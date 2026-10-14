<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoWelfareProduct extends Model
{
    protected $fillable = [
        'name', 'code', 'description', 'type',
        'member_contribution', 'max_claim_amount',
        'qualifying_months', 'waiting_period_days',
        'requires_documents', 'is_active',
    ];

    public function claims()
    {
        return $this->hasMany(SaccoWelfareClaim::class);
    }

    public function contributions()
    {
        return $this->hasMany(SaccoWelfareContribution::class);
    }
}
