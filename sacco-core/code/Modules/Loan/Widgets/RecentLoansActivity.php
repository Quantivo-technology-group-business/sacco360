<?php

namespace Modules\Loan\Widgets;

use Arrilot\Widgets\AbstractWidget;
use Modules\Loan\Entities\Loan;

class RecentLoansActivity extends AbstractWidget
{
    protected $config = [];

    public function run()
    {
        $recentLoans = Loan::with('client', 'loan_product', 'branch')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return theme_view('loan::widgets.recent_loans_activity', [
            'config' => $this->config,
            'recentLoans' => $recentLoans,
        ]);
    }
}
