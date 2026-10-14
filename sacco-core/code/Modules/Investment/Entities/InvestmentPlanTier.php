<?php
namespace Modules\Investment\Entities;

use Illuminate\Database\Eloquent\Model;

class InvestmentPlanTier extends Model
{
    protected $table = 'investment_plan_tiers';
    protected $fillable = ['investment_plan_id','min_amount','max_amount','return_rate','label'];

    public function plan()
    {
        return $this->belongsTo(InvestmentPlan::class, 'investment_plan_id');
    }
}
