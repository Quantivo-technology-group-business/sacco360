<?php

namespace Modules\Sacco\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Modules\Client\Entities\Client;
use Modules\Sacco\Services\MemberNumberService;

class BackfillMemberNumbers extends Command
{
    protected $signature = 'sacco:backfill-member-numbers {--dry-run : Preview without saving}';
    protected $description = 'Assign member numbers to clients missing one';

    public function handle()
    {
        if (!Schema::hasColumn('clients', 'member_number')) {
            $this->error('Run migrations first: php artisan migrate --force');
            return 1;
        }

        $service = new MemberNumberService();
        $dryRun = (bool)$this->option('dry-run');
        $assigned = 0;

        Client::whereNull('member_number')->orWhere('member_number', '')->orderBy('id')->chunk(200, function ($clients) use ($service, $dryRun, &$assigned) {
            foreach ($clients as $client) {
                if (!empty($client->member_number)) {
                    continue;
                }
                $number = !empty($client->external_id)
                    ? $client->external_id
                    : config('sacco.member_number_prefix', 'M') . str_pad((string)$client->id, 6, '0', STR_PAD_LEFT);

                if ($dryRun) {
                    $this->line("Would assign {$number} to client #{$client->id} ({$client->first_name} {$client->last_name})");
                } else {
                    $service->assign($client);
                    $this->line("Assigned {$client->member_number} to client #{$client->id}");
                }
                $assigned++;
            }
        });

        $this->info($dryRun
            ? "Dry run complete. {$assigned} client(s) would receive member numbers."
            : "Done. Assigned member numbers to {$assigned} client(s).");

        return 0;
    }
}
