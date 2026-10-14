<?php

namespace Modules\Sacco\Console;

use Illuminate\Console\Command;
use Modules\Sacco\Services\CaelsClassificationService;

class ClassifyLoans extends Command
{
    protected $signature = 'sacco:classify-loans {--date=}';
    protected $description = 'Classify all active loans per SASRA CAELS bands';

    public function handle(CaelsClassificationService $service)
    {
        $date = $this->option('date') ?? date('Y-m-d');
        $count = $service->classifyAllLoans($date);
        $this->info("Classified {$count} loans as of {$date}.");
    }
}
