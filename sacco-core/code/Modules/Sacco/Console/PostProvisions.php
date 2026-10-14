<?php

namespace Modules\Sacco\Console;

use Illuminate\Console\Command;
use Modules\Sacco\Services\ProvisionPostingService;

class PostProvisions extends Command
{
    protected $signature = 'sacco:post-provisions {--date=}';
    protected $description = 'Post monthly loan loss provisions to GL';

    public function handle(ProvisionPostingService $service)
    {
        $date = $this->option('date') ?? date('Y-m-t');
        $run = $service->createProvisionRun($date);
        if (function_exists('sacco_queue_transaction_approval')) {
            sacco_queue_transaction_approval('provision_run', $run->id, (float) $run->adjustment_amount, 'Monthly provision posting');
            $this->info("Provision run {$run->id} queued for checker approval (amount: {$run->adjustment_amount}).");
        } else {
            $service->postProvision($run);
            $this->info("Posted provision adjustment of {$run->adjustment_amount} for period ending {$date}.");
        }
    }
}
