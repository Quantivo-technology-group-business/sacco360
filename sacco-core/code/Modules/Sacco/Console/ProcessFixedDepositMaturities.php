<?php

namespace Modules\Sacco\Console;

use Illuminate\Console\Command;
use Modules\Sacco\Services\FixedDepositService;

class ProcessFixedDepositMaturities extends Command
{
    protected $signature = 'sacco:process-fd-maturities {--date=}';
    protected $description = 'Process matured fixed deposits';

    public function handle(FixedDepositService $service): int
    {
        $date = $this->option('date') ?? date('Y-m-d');
        $count = $service->processMaturities($date);
        $this->info("Processed {$count} fixed deposit maturities.");
        return 0;
    }
}
