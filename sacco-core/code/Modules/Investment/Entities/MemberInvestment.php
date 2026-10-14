<?php
namespace Modules\Investment\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;
use Modules\Branch\Entities\Branch;
use Modules\Core\Entities\Currency;
use Modules\User\Entities\User;

class MemberInvestment extends Model
{
    protected $table = 'member_investments';
    protected $fillable = [
        'account_number','client_id','investment_plan_id','branch_id','currency_id',
        'created_by_id','principal_amount','units_held','unit_price_at_purchase',
        'effective_rate','start_date','maturity_date','duration_months',
        'total_profit_accrued','total_profit_paid','last_profit_posting_date',
        'next_profit_posting_date','status','closed_date','payout_amount','notes',
    ];

    protected $casts = [
        'start_date'               => 'date',
        'maturity_date'            => 'date',
        'last_profit_posting_date' => 'date',
        'next_profit_posting_date' => 'date',
        'closed_date'              => 'date',
    ];

    public function client()       { return $this->belongsTo(Client::class); }
    public function plan()         { return $this->belongsTo(InvestmentPlan::class, 'investment_plan_id'); }
    public function branch()       { return $this->belongsTo(Branch::class); }
    public function currency()     { return $this->belongsTo(Currency::class); }
    public function created_by()   { return $this->belongsTo(User::class, 'created_by_id'); }
    public function transactions() { return $this->hasMany(InvestmentTransaction::class, 'member_investment_id'); }

    /** Outstanding profit not yet paid */
    public function getProfitDueAttribute(): float
    {
        return (float)$this->total_profit_accrued - (float)$this->total_profit_paid;
    }

    /** Current total value = principal + accrued profit */
    public function getCurrentValueAttribute(): float
    {
        return (float)$this->principal_amount + (float)$this->total_profit_accrued;
    }

    public function getStatusBadgeAttribute(): string
    {
        return [
            'pending'   => '<span class="badge badge-warning">Pending</span>',
            'active'    => '<span class="badge badge-success">Active</span>',
            'matured'   => '<span class="badge badge-info">Matured</span>',
            'closed'    => '<span class="badge badge-secondary">Closed</span>',
            'withdrawn' => '<span class="badge badge-danger">Withdrawn</span>',
        ][$this->status] ?? $this->status;
    }

    public function scopeActive($q) { return $q->where('status', 'active'); }
}
