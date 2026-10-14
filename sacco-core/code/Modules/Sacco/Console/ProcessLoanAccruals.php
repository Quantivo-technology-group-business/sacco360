<?php

namespace Modules\Sacco\Console;

use Illuminate\Console\Command;
use Modules\Sacco\Services\LoanAccrualService;

class ProcessLoanAccruals extends Command
{
    protected $signature = 'sacco:process-loan-accruals {--date=}';
    protected $description = 'Process daily loan interest accruals with NPA suspension';

    public function handle(LoanAccrualService $service)
    {
        $date = $this->option('date') ?? date('Y-m-d');
        $count = $service->processDailyAccruals($date);
        $this->info("Processed {$count} loan accruals for {$date}.");
    }
}
