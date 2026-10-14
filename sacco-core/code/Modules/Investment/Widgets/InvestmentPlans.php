<?php
namespace Modules\Investment\Widgets;

use Arrilot\Widgets\AbstractWidget;
use Modules\Investment\Entities\InvestmentPlan;

class InvestmentPlans extends AbstractWidget
{
    protected $config = [];

    public function run()
    {
        $plans = InvestmentPlan::withCount(['investments' => fn($q) => $q->where('status','active')])
            ->where('active', 1)
            ->orderBy('return_rate','desc')
            ->get();

        return theme_view('investment::widgets.investment_plans', [
            'config' => $this->config,
            'plans'  => $plans,
        ]);
    }
}
