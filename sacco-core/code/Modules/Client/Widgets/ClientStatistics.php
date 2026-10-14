<?php

namespace Modules\Client\Widgets;

use Arrilot\Widgets\AbstractWidget;
use Illuminate\Support\Carbon;
use Modules\Client\Entities\Client;

class ClientStatistics extends AbstractWidget
{
    protected $config = [];

    public function run()
    {
        $stats = [
            'total' => Client::count(),
            'active' => Client::where('status', 'active')->count(),
            'inactive' => Client::where('status', 'inactive')->count(),
            'pending' => Client::where('status', 'pending')->count(),
            'new_this_month' => Client::where('joined_date', '>=', Carbon::today()->startOfMonth())->count(),
            'new_this_year' => Client::where('joined_date', '>=', Carbon::today()->startOfYear())->count(),
        ];

        return theme_view('client::widgets.client_statistics', [
            'config' => $this->config,
            'stats' => $stats,
        ]);
    }
}
