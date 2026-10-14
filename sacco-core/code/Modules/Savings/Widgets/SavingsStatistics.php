<?php

namespace Modules\Savings\Widgets;

use Arrilot\Widgets\AbstractWidget;
use Modules\Savings\Entities\Savings;

class SavingsStatistics extends AbstractWidget
{
    protected $config = [];

    public function run()
    {
        $stats = [
            'total_accounts' => Savings::count(),
            'active_accounts' => Savings::where('status', 'active')->count(),
            'total_balance' => Savings::sum('balance_derived'),
            'total_deposits' => Savings::sum('total_deposits_derived'),
            'total_withdrawals' => Savings::sum('total_withdrawals_derived'),
            'total_interest' => Savings::sum('total_interest_posted_derived'),
        ];

        return theme_view('savings::widgets.savings_statistics', [
            'config' => $this->config,
            'stats' => $stats,
        ]);
    }
}
