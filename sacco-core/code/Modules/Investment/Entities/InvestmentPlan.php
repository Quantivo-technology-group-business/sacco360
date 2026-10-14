<?php
namespace Modules\Investment\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\Entities\ChartOfAccount;

class InvestmentPlan extends Model
{
    protected $table = 'investment_plans';
    protected $fillable = [
        'name','code','description','plan_type','return_rate','return_rate_type',
        'interest_method','compounding_frequency','profit_posting_frequency',
        'min_duration_months','max_duration_months','min_amount','max_amount',
        'unit_price','total_units_available','allow_early_withdrawal',
        'early_withdrawal_penalty_rate','investment_control_account_id',
        'interest_expense_account_id','cash_account_id','profit_payable_account_id',
        'active','open_to_members','terms_conditions','created_by_id',
    ];

    public function tiers()
    {
        return $this->hasMany(InvestmentPlanTier::class, 'investment_plan_id');
    }

    public function investments()
    {
        return $this->hasMany(MemberInvestment::class, 'investment_plan_id');
    }

    public function investment_control_account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'investment_control_account_id');
    }

    public function interest_expense_account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'interest_expense_account_id');
    }

    public function cash_account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'cash_account_id');
    }

    public function profit_payable_account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'profit_payable_account_id');
    }

    /** Resolve the effective return rate for a given principal amount (handles tiered rates). */
    public function effectiveRate(float $amount): float
    {
        if ($this->return_rate_type === 'tiered') {
            $tier = $this->tiers()
                ->where('min_amount', '<=', $amount)
                ->where(function ($q) use ($amount) {
                    $q->whereNull('max_amount')->orWhere('max_amount', '>=', $amount);
                })
                ->orderByDesc('min_amount')
                ->first();
            return $tier ? (float)$tier->return_rate : (float)$this->return_rate;
        }
        return (float)$this->return_rate;
    }

    public function getPlanTypeLabelAttribute(): string
    {
        return [
            'fixed_deposit' => 'Fixed Deposit',
            'bond'          => 'Bond',
            'unit_trust'    => 'Unit Trust',
            'equity'        => 'Equity / Shares',
            'real_estate'   => 'Real Estate',
            'money_market'  => 'Money Market',
            'other'         => 'Other',
        ][$this->plan_type] ?? ucfirst($this->plan_type);
    }

    public function scopeActive($q) { return $q->where('active', 1); }
}
