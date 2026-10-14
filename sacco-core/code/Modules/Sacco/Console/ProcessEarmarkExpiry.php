<?php

namespace Modules\Sacco\Console;

use Illuminate\Console\Command;
use Modules\Sacco\Services\EarmarkService;

class ProcessEarmarkExpiry extends Command
{
    protected $signature = 'sacco:expire-earmarks';
    protected $description = 'Expire member earmarks past their expiry date';

    public function handle()
    {
        $count = (new EarmarkService())->expireDueEarmarks();
        $this->info("Expired {$count} earmark(s).");
    }
}
