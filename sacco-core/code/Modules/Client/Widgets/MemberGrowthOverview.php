<?php

namespace Modules\Client\Widgets;

use Arrilot\Widgets\AbstractWidget;
use Illuminate\Support\Carbon;
use Modules\Client\Charts\MemberGrowthChart;
use Modules\Client\Entities\Client;

class MemberGrowthOverview extends AbstractWidget
{
    protected $config = [];

    public function run()
    {
        $chart = new MemberGrowthChart();
        $labels = [];
        $data = [];
        $startDate = Carbon::today()->subMonthsNoOverflow(11)->startOfMonth();

        for ($i = 0; $i < 12; $i++) {
            $monthStart = $startDate->copy()->startOfMonth();
            $monthEnd = $startDate->copy()->endOfMonth();
            $labels[] = $startDate->format('M Y');
            $data[] = Client::whereBetween('joined_date', [
                $monthStart->format('Y-m-d'),
                $monthEnd->format('Y-m-d'),
            ])->count();
            $startDate->addMonthNoOverflow();
        }

        $chart->labels($labels);
        $chart->labelsRotation(-30);
        $chart->title('Member Growth');
        $chart->label('Count');
        $chart->dataset('New Members', 'area', $data)->color('#17a2b8');

        return theme_view('client::widgets.member_growth_overview', [
            'config' => $this->config,
            'chart' => $chart,
            'total_new' => array_sum($data),
        ]);
    }
}
