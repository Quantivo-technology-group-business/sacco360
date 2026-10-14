<?php

namespace Modules\Sacco\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Share\Entities\Share;
use Modules\Sacco\Services\ShareRegulatoryService;

class ProcessShareDeadlines extends Command
{
    protected $signature = 'sacco:process-share-deadlines';
    protected $description = 'Flag members who missed compulsory share payment deadlines';

    public function handle(ShareRegulatoryService $service): int
    {
        $flagged = 0;
        $shares = Share::with('share_product')
            ->whereIn('status', ['active', 'approved', 'pending'])
            ->get();

        foreach ($shares as $share) {
            $check = $service->validatePaymentDeadline($share);
            if ($check['passed']) {
                continue;
            }

            DB::table('clients')
                ->where('id', $share->client_id)
                ->update(['member_status_detail' => 'defaulting']);

            $flagged++;
            $this->warn("Client #{$share->client_id}: " . implode(' ', $check['violations']));
        }

        $this->info("Flagged {$flagged} members for compulsory share deadline violations.");
        return 0;
    }
}
