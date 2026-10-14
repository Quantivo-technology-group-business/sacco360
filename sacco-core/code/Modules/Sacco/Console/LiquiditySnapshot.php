<?php

namespace Modules\Sacco\Console;

use Illuminate\Console\Command;
use Modules\Sacco\Entities\SaccoLiquiditySnapshot;
use Modules\Sacco\Services\PrudentialRatioService;

class LiquiditySnapshot extends Command
{
    protected $signature = 'sacco:liquidity-snapshot {--date=}';
    protected $description = 'Capture weekly liquidity ratio snapshot';

    public function handle(PrudentialRatioService $service)
    {
        $date = $this->option('date') ?? date('Y-m-d');
        $ratios = $service->calculateAll($date);
        $summary = $ratios['summary'];

        SaccoLiquiditySnapshot::updateOrCreate(
            ['snapshot_date' => $date],
            [
                'liquid_assets' => $summary['liquid_assets'],
                'total_deposits' => $summary['total_deposits'],
                'liquidity_ratio' => $ratios['liquidity_ratio']['value'],
                'status' => $ratios['liquidity_ratio']['status'],
            ]
        );

        $this->info("Liquidity snapshot saved: {$ratios['liquidity_ratio']['value']}% ({$ratios['liquidity_ratio']['status']})");
    }
}
