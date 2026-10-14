<?php

namespace Modules\Dashboard\Widgets;

use Arrilot\Widgets\AbstractWidget;
use Illuminate\Support\Carbon;
use Modules\Dashboard\Charts\FinancialOverviewChart;
use Modules\Expense\Entities\Expense;
use Modules\Income\Entities\Income;

class FinancialOverview extends AbstractWidget
{
    protected $config = [];

    public function run()
    {
        $chart = new FinancialOverviewChart();
        $labels = [];
        $incomeData = [];
        $expenseData = [];
        $startDate = Carbon::today()->subMonthsNoOverflow(11)->startOfMonth();

        $monthIncome = 0;
        $monthExpense = 0;

        for ($i = 0; $i < 12; $i++) {
            $monthStart = $startDate->copy()->startOfMonth();
            $monthEnd = $startDate->copy()->endOfMonth();
            $labels[] = $startDate->format('M Y');

            $income = Income::whereBetween('date', [
                $monthStart->format('Y-m-d'),
                $monthEnd->format('Y-m-d'),
            ])->sum('amount');
            $expense = Expense::whereBetween('date', [
                $monthStart->format('Y-m-d'),
                $monthEnd->format('Y-m-d'),
            ])->sum('amount');

            $incomeData[] = round($income, 2);
            $expenseData[] = round($expense, 2);

            if ($startDate->isSameMonth(Carbon::today())) {
                $monthIncome = $income;
                $monthExpense = $expense;
            }

            $startDate->addMonthNoOverflow();
        }

        $chart->labels($labels);
        $chart->labelsRotation(-30);
        $chart->title('Income vs Expense');
        $chart->label('Amount');
        $chart->dataset('Income', 'column', $incomeData)->color('#28a745');
        $chart->dataset('Expense', 'column', $expenseData)->color('#dc3545');

        return theme_view('dashboard::widgets.financial_overview', [
            'config' => $this->config,
            'chart' => $chart,
            'month_income' => $monthIncome,
            'month_expense' => $monthExpense,
            'month_net' => $monthIncome - $monthExpense,
            'ytd_income' => array_sum($incomeData),
            'ytd_expense' => array_sum($expenseData),
        ]);
    }
}
