<?php

namespace Modules\Loan\Widgets;

use Arrilot\Widgets\AbstractWidget;
use Illuminate\Support\Facades\DB;
use Modules\Loan\Charts\LoanProductMixChart;

class LoanProductMix extends AbstractWidget
{
    protected $config = [];

    public function run()
    {
        $products = DB::table('loans')
            ->join('loan_products', 'loans.loan_product_id', '=', 'loan_products.id')
            ->where('loans.status', 'active')
            ->selectRaw('loan_products.name, coalesce(sum(loans.principal),0) as total_principal, count(loans.id) as loan_count')
            ->groupBy('loan_products.id', 'loan_products.name')
            ->orderByDesc('total_principal')
            ->get();

        $chart = new LoanProductMixChart();
        $labels = $products->pluck('name')->toArray();
        $data = $products->pluck('total_principal')->map(function ($v) {
            return round($v, 2);
        })->toArray();

        $chart->labels($labels);
        $chart->dataset(trans_choice('loan::general.loan', 2), 'pie', $data);

        return theme_view('loan::widgets.loan_product_mix', [
            'config' => $this->config,
            'products' => $products,
            'chart' => $chart,
        ]);
    }
}
