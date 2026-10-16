<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

/**
 * Close out the transaction-alert backlog that was never going to be sent.
 *
 * `TransactionNotificationService::notify()` used to insert a row into
 * `communication_campaign_logs` with `status = 'pending'` and
 * `communication_campaign_id = null`, then stop. The only consumer of that
 * table, `campaigns:process`, selects scheduled campaigns and never looks for
 * orphan log rows, so every one of those alerts sat undelivered forever.
 *
 * The service now sends for real (see Part G12), which leaves the historical
 * backlog in an ambiguous state: rows marked 'pending' that nothing will ever
 * pick up. Leaving them is what hid the bug in the first place — the log read
 * like a queue waiting to be drained.
 *
 * They are marked 'failed' rather than sent. Dispatching them now would text
 * members about transactions from weeks or months ago, which is worse than
 * saying nothing. The count is logged so the size of the historical gap is
 * recoverable after the fact.
 */
class MarkUndeliverableTransactionAlertLogs extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('communication_campaign_logs')) {
            return;
        }

        $orphans = DB::table('communication_campaign_logs')
            ->whereNull('communication_campaign_id')
            ->where('campaign_type', 'sms')
            ->where('campaign_name', 'SACCO Transaction Alert')
            ->where('status', 'pending');

        $count = (clone $orphans)->count();

        if ($count === 0) {
            return;
        }

        $orphans->update([
            'status' => 'failed',
            'updated_at' => now(),
        ]);

        Log::info("Marked {$count} undeliverable SACCO transaction alert(s) as failed. "
            . 'These were written by the orphan-log bug and were never dispatched; '
            . 'they are not retro-sent to avoid texting members about stale transactions.');
    }

    public function down()
    {
        // Not reversible: the original 'pending' rows cannot be distinguished
        // from rows this migration touched once they read 'failed'. That is
        // acceptable, because 'pending' was never an accurate description — no
        // consumer for those rows ever existed.
    }
}
