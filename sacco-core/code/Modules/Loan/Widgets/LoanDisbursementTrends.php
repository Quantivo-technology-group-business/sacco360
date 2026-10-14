<?php

namespace Modules\Loan\Widgets;

use Arrilot\Widgets\AbstractWidget;
use Illuminate\Support\Carbon;
use Modules\Loan\Charts\LoanDisbursementTrendsChart;
use Modules\Loan\Entities\Loan;

class LoanDisbursementTrends extends AbstractWidget
{
    protected $config = [];

    public function run()
    {
        $chart = new LoanDisbursementTrendsChart();
        $labels = [];
        $amounts = [];
        $counts = [];
        $startDate = Carbon::today()->subMonthsNoOverflow(11)->startOfMonth();

        for ($i = 0; $i < 12; $i++) {
            $monthStart = $startDate->copy()->startOfMonth();
            $monthEnd = $startDate->copy()->endOfMonth();
            $query = Loan::whereBetween('disbursed_on_date', [
                $monthStart->format('Y-m-d'),
                $monthEnd->format('Y-m-d'),
            ])->where('status', '!=', 'withdrawn');

            $labels[] = $startDate->format('M Y');
            $amounts[] = round((clone $query)->sum('principal'), 2);
            $counts[] = (clone $query)->count();
            $startDate->addMonthNoOverflow();
        }

        $chart->labels($labels);
        $chart->labelsRotation(-30);
        $chart->title(trans_choice('loan::general.disbursement', 2) . ' ' . trans_choice('loan::general.trend', 2));
        $chart->label(trans_choice('loan::general.amount', 1));
        $chart->dataset(trans_choice('loan::general.amount', 1), 'column', $amounts)->color('#28a745');
        $chart->dataset(trans_choice('loan::general.count', 1), 'line', $counts)->color('#007bff');

        return theme_view('loan::widgets.loan_disbursement_trends', [
            'config' => $this->config,
            'chart' => $chart,
            'total_ytd' => array_sum($amounts),
            'count_ytd' => array_sum($counts),
        ]);
    }
}
