<?php

namespace Modules\Loan\Widgets;

use Arrilot\Widgets\AbstractWidget;
use Modules\Loan\Charts\LoanArrearsAgingChart;
use Modules\Loan\Services\LoanDashboardMetrics;

class LoanArrearsAging extends AbstractWidget
{
    protected $config = [];

    public function run()
    {
        $aging = LoanDashboardMetrics::arrearsAging();
        $chart = new LoanArrearsAgingChart();
        $labels = ['1-30 Days', '31-60 Days', '61-90 Days', '90+ Days'];
        $data = [
            round($aging['1_30'], 2),
            round($aging['31_60'], 2),
            round($aging['61_90'], 2),
            round($aging['90_plus'], 2),
        ];
        $chart->labels($labels);
        $chart->title(trans_choice('loan::general.arrears', 1) . ' ' . trans_choice('loan::general.aging', 1));
        $chart->label(trans_choice('loan::general.amount', 1));
        $chart->dataset(trans_choice('loan::general.arrears', 2), 'bar', $data)
            ->color(['#ffc107', '#fd7e14', '#dc3545', '#721c24']);

        return theme_view('loan::widgets.loan_arrears_aging', [
            'config' => $this->config,
            'aging' => $aging,
            'total_arrears' => array_sum($data),
            'chart' => $chart,
        ]);
    }
}
