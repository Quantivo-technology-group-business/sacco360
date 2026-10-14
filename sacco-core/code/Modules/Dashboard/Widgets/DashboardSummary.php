<?php

namespace Modules\Dashboard\Widgets;

use Arrilot\Widgets\AbstractWidget;
use Illuminate\Support\Carbon;
use Modules\Branch\Entities\Branch;
use Modules\Client\Entities\Client;
use Modules\Loan\Entities\Loan;
use Modules\Loan\Entities\LoanTransaction;
use Modules\Loan\Services\LoanDashboardMetrics;
use Modules\Savings\Entities\Savings;

class DashboardSummary extends AbstractWidget
{
    protected $config = [];

    public function run()
    {
        $par = LoanDashboardMetrics::portfolioAtRisk();
        $summary = [
            'branches' => Branch::count(),
            'clients' => Client::where('status', 'active')->count(),
            'active_loans' => Loan::where('status', 'active')->count(),
            'disbursed' => Loan::where('status', 'active')->sum('principal'),
            'outstanding' => $par['total_outstanding'],
            'par_30' => $par['par_percentages'][30],
            'savings_balance' => Savings::sum('balance_derived'),
            'savings_accounts' => Savings::where('status', 'active')->count(),
            'collections_today' => LoanTransaction::where('reversed', 0)
                ->whereIn('loan_transaction_type_id', [2, 5, 8])
                ->whereDate('submitted_on', Carbon::today())
                ->sum('amount'),
            'collections_month' => LoanTransaction::where('reversed', 0)
                ->whereIn('loan_transaction_type_id', [2, 5, 8])
                ->whereBetween('submitted_on', [Carbon::today()->startOfMonth(), Carbon::today()->endOfMonth()])
                ->sum('amount'),
        ];

        return theme_view('dashboard::widgets.dashboard_summary', [
            'config' => $this->config,
            'summary' => $summary,
        ]);
    }
}
