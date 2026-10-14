<?php
namespace Modules\Investment\Widgets;

use Arrilot\Widgets\AbstractWidget;
use Modules\Investment\Entities\MemberInvestment;
use Modules\Investment\Entities\InvestmentPlan;

class InvestmentSummary extends AbstractWidget
{
    protected $config = [];

    public function run()
    {
        $stats = [
            'active_count'          => MemberInvestment::where('status','active')->count(),
            'total_invested'        => MemberInvestment::whereIn('status',['active','matured'])->sum('principal_amount'),
            'total_profit_accrued'  => MemberInvestment::whereIn('status',['active','matured'])->sum('total_profit_accrued'),
            'total_profit_paid'     => MemberInvestment::whereIn('status',['active','matured'])->sum('total_profit_paid'),
            'profit_due'            => MemberInvestment::where('status','active')
                                           ->selectRaw('SUM(total_profit_accrued - total_profit_paid) as due')
                                           ->value('due') ?? 0,
            'plans_count'           => InvestmentPlan::where('active',1)->count(),
            'pending_count'         => MemberInvestment::where('status','pending')->count(),
            'maturing_soon'         => MemberInvestment::where('status','active')
                                           ->whereNotNull('maturity_date')
                                           ->whereDate('maturity_date','<=',now()->addDays(30))
                                           ->count(),
        ];

        return theme_view('investment::widgets.investment_summary', [
            'config' => $this->config,
            'stats'  => $stats,
        ]);
    }
}
