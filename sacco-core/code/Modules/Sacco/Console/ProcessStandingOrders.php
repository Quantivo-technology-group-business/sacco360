<?php

namespace Modules\Sacco\Console;

use Illuminate\Console\Command;
use Modules\Sacco\Services\StandingOrderService;

class ProcessStandingOrders extends Command
{
    protected $signature = 'sacco:process-standing-orders';
    protected $description = 'Process due SACCO standing orders';

    public function handle(StandingOrderService $service)
    {
        $count = $service->processDueOrders();
        $this->info("Processed {$count} standing orders.");
    }
}
