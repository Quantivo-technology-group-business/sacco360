<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Branch\Entities\Branch;

class SaccoInvestment extends Model
{
    protected $fillable = [
        'created_by_id', 'branch_id', 'name', 'reference_number', 'type',
        'issuer', 'principal_amount', 'current_value', 'interest_rate',
        'investment_date', 'maturity_date', 'tenure_days',
        'expected_return', 'actual_return', 'chart_of_account_id',
        'status', 'notes',
    ];

    protected $dates = ['investment_date', 'maturity_date'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function valuations()
    {
        return $this->hasMany(SaccoInvestmentValuation::class);
    }

    public function latestValuation()
    {
        return $this->hasOne(SaccoInvestmentValuation::class)->latestOfMany('valuation_date');
    }

    public function getUnrealizedGainLossAttribute()
    {
        $current = $this->current_value ?? $this->principal_amount;
        return $current - $this->principal_amount;
    }
}
