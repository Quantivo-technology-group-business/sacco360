<?php

namespace Modules\Sacco\Console;

use Illuminate\Console\Command;
use Modules\Sacco\Services\GovernanceService;

class ComplianceReminders extends Command
{
    protected $signature = 'sacco:compliance-reminders';
    protected $description = 'Send compliance filing and license renewal reminders';

    public function handle(GovernanceService $service)
    {
        $count = $service->processReminders();
        $this->info("Processed {$count} compliance reminders.");
    }
}
