<?php

namespace Modules\Sacco\Console;

use Illuminate\Console\Command;
use Modules\Sacco\Services\AmlMonitoringService;

class ProcessAmlMonitoring extends Command
{
    protected $signature = 'sacco:process-aml-monitoring';
    protected $description = 'Run AML transaction monitoring checks';

    public function handle(AmlMonitoringService $service)
    {
        $count = $service->runDailyChecks();
        $this->info("Generated {$count} AML alerts.");
    }
}
