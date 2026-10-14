<?php

namespace Modules\Loan\Widgets;

use Arrilot\Widgets\AbstractWidget;
use Modules\Loan\Charts\LoanPortfolioAtRiskChart;
use Modules\Loan\Services\LoanDashboardMetrics;

class LoanPortfolioAtRisk extends AbstractWidget
{
    protected $config = [];

    public function run()
    {
        $par = LoanDashboardMetrics::portfolioAtRisk();
        $chart = new LoanPortfolioAtRiskChart();
        $labels = ['PAR 1', 'PAR 7', 'PAR 30', 'PAR 90'];
        $data = [
            $par['par_percentages'][1],
            $par['par_percentages'][7],
            $par['par_percentages'][30],
            $par['par_percentages'][90],
        ];
        $chart->labels($labels);
        $chart->title(trans_choice('loan::general.portfolio_at_risk', 1));
        $chart->label('%');
        $chart->dataset('PAR %', 'column', $data)->color('#dc3545');

        return theme_view('loan::widgets.loan_portfolio_at_risk', [
            'config' => $this->config,
            'par' => $par,
            'chart' => $chart,
        ]);
    }
}
