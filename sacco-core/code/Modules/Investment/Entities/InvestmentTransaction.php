<?php
namespace Modules\Investment\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\User\Entities\User;

class InvestmentTransaction extends Model
{
    protected $table = 'investment_transactions';
    protected $fillable = [
        'member_investment_id','created_by_id','transaction_type',
        'amount','date','reference','notes','journal_entry_id','transaction_number',
    ];

    public function investment() { return $this->belongsTo(MemberInvestment::class, 'member_investment_id'); }
    public function created_by() { return $this->belongsTo(User::class, 'created_by_id'); }

    public function getTypeLabelAttribute(): string
    {
        return [
            'investment'     => 'Investment (Principal In)',
            'top_up'         => 'Top-Up',
            'profit_accrual' => 'Profit Accrual',
            'profit_payment' => 'Profit Payment',
            'withdrawal'     => 'Early Withdrawal',
            'maturity_payout'=> 'Maturity Payout',
            'penalty'        => 'Early Withdrawal Penalty',
        ][$this->transaction_type] ?? ucfirst($this->transaction_type);
    }
}
