<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoInvestmentValuation extends Model
{
    protected $fillable = [
        'sacco_investment_id', 'valuation_date',
        'market_value', 'accrued_interest',
        'created_by_id', 'notes',
    ];

    protected $dates = ['valuation_date'];

    public function investment()
    {
        return $this->belongsTo(SaccoInvestment::class);
    }
}
