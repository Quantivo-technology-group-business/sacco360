<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Client\Entities\Client;
use Modules\Communication\Entities\SmsGateway;
use Modules\Setting\Entities\Setting;

/**
 * Transaction SMS alerts for deposits, withdrawals, transfers, M-Pesa, PesaLink
 * and teller transactions.
 *
 * This used to write a row into `communication_campaign_logs` with
 * `status = 'pending'` and `communication_campaign_id = null`, and stop there.
 * The only consumer of that table, the `campaigns:process` command, selects
 * `CommunicationCampaign::where('trigger_type','schedule')` — it never queries
 * the log table for orphans, and nothing else in the codebase selects
 * `status = 'pending'` from it. So no member ever received a transaction alert;
 * the messages simply accumulated in the database forever.
 *
 * It now sends through the same `send_sms()` helper the campaign processor uses,
 * and logs a truthful status.
 *
 * Sending is synchronous and deliberate about it. The queue is not a viable
 * alternative here: `QUEUE_CONNECTION=database` is configured but no worker is
 * scheduled or otherwise running, so dispatching a job would park the message in
 * the `jobs` table indefinitely and reproduce the original bug in a new form.
 *
 * Because the call is inline on money paths, three constraints are enforced:
 *
 * - **Tight timeouts.** 2s to connect, 6s total, against the 5s/15s batch
 *   defaults in `send_sms()`. M-Pesa C2B callbacks are retried by Safaricom when
 *   they take too long, so this must not stall behind a slow SMS provider.
 * - **No locks held.** Where the caller is inside an open `DB::transaction()` —
 *   `TransferService::execute()` is, twice over, as are `PesalinkService` and
 *   `MpesaB2cService` — the send is deferred to `DB::afterCommit()` so an HTTP
 *   round trip never holds row locks on savings, share or loan accounts.
 * - **No escape.** Every failure mode is swallowed and logged. An SMS provider
 *   being down, unconfigured, or absent must never fail a transaction.
 */
class TransactionNotificationService
{
    /**
     * Seconds allowed for the TCP/TLS handshake to the SMS gateway.
     */
    protected const CONNECT_TIMEOUT = 2;

    /**
     * Seconds allowed for the whole gateway request.
     *
     * Kept well under the batch default in send_sms(): M-Pesa C2B callbacks are
     * retried by Safaricom when they take too long, so this path must not be
     * able to stall behind a slow SMS provider.
     */
    protected const REQUEST_TIMEOUT = 6;

    public function notify(Client $client, string $type, float $amount, ?string $reference = null): void
    {
        if (!config('sacco.sms_transaction_alerts', true) || empty($client->mobile)) {
            return;
        }

        $message = sprintf(
            'SACCO Alert: %s of %.2f processed. Ref: %s',
            ucfirst($type),
            $amount,
            $reference ?? 'N/A'
        );

        // Several callers invoke this from inside an open DB::transaction —
        // TransferService::execute() does so twice, and PesalinkService and
        // MpesaB2cService once each. A synchronous HTTP call to an SMS gateway
        // in that position would hold row locks on savings, share and loan
        // accounts for as long as the gateway takes to answer. Defer to the
        // commit so the locks are released first.
        //
        // db.transactions is bound as a singleton by DatabaseServiceProvider in
        // Laravel 8, so afterCommit() will not hit its "Transactions Manager has
        // not been set" RuntimeException. It is still guarded, because a
        // notification must never be the reason a transfer fails.
        if (DB::transactionLevel() > 0 && $this->canDefer()) {
            DB::afterCommit(function () use ($client, $message) {
                $this->sendAndRecord($client, $message);
            });

            return;
        }

        $this->sendAndRecord($client, $message);
    }

    /**
     * Send the alert and write its audit row. Swallows every failure.
     */
    protected function sendAndRecord(Client $client, string $message): void
    {
        $sent = false;
        $failure = null;

        try {
            $sent = $this->dispatch($client->mobile, $message);

            if (!$sent) {
                $failure = 'No usable SMS gateway is configured.';
            }
        } catch (\Throwable $e) {
            // Never propagate: callers are payment and teller transactions.
            $failure = $e->getMessage();
            Log::warning('Transaction SMS alert failed.', [
                'client_id' => $client->id,
                'error'     => $failure,
            ]);
        }

        $this->record($client, $message, $sent ? 'sent' : 'failed');

        if (!$sent) {
            // Retain the previous fallback behaviour so the message text is still
            // recoverable from the application log when delivery was impossible.
            Log::info('SACCO SMS notification not delivered: ' . $message . ($failure ? ' (' . $failure . ')' : ''));
        }
    }

    /**
     * Whether DB::afterCommit() is usable in this application.
     *
     * It throws a RuntimeException when no transactions manager is bound, so the
     * binding is checked rather than assumed.
     */
    protected function canDefer(): bool
    {
        try {
            return app()->bound('db.transactions');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Hand the message to the configured SMS gateway.
     *
     * @return bool true only if the gateway accepted the request
     */
    protected function dispatch(string $to, string $message): bool
    {
        // The Communication module owns this helper and may be disabled, in
        // which case the function is never defined.
        if (!function_exists('send_sms')) {
            return false;
        }

        $gatewayId = $this->activeGatewayId();

        if ($gatewayId === null) {
            return false;
        }

        return (bool) send_sms($to, $message, $gatewayId, self::CONNECT_TIMEOUT, self::REQUEST_TIMEOUT);
    }

    /**
     * Resolve the active SMS gateway, tolerating an unconfigured install.
     *
     * `send_sms()` looks the setting up itself when no gateway id is passed, so
     * resolving it here is what lets us pass explicit timeouts — and it lets us
     * decline quietly instead of letting a missing setting row surface as an
     * error from inside a payment callback.
     */
    protected function activeGatewayId(): ?int
    {
        try {
            $setting = Setting::where('setting_key', 'communication.active_sms_gateway')->first();

            if (!$setting || $setting->setting_value === null || $setting->setting_value === '') {
                return null;
            }

            return SmsGateway::where('id', $setting->setting_value)->exists()
                ? (int) $setting->setting_value
                : null;
        } catch (\Throwable $e) {
            // Setting or gateway tables may not exist yet (fresh install, or the
            // Communication module is not installed).
            return null;
        }
    }

    /**
     * Write the audit row with a status that reflects what actually happened.
     *
     * Previously every row was written as 'pending', which is how the failure
     * stayed invisible: the log looked like a queue waiting to be drained rather
     * than a record of messages that were never going to be sent.
     */
    protected function record(Client $client, string $message, string $status): void
    {
        try {
            if (!DB::getSchemaBuilder()->hasTable('communication_campaign_logs')) {
                return;
            }

            DB::table('communication_campaign_logs')->insert([
                'created_at' => now(),
                'updated_at' => now(),
                'client_id' => $client->id,
                'communication_campaign_id' => null,
                'campaign_type' => 'sms',
                'description' => $message,
                'send_to' => $client->mobile,
                'campaign_name' => 'SACCO Transaction Alert',
                'status' => $status,
            ]);
        } catch (\Throwable $e) {
            // A logging failure must not break the transaction either.
            Log::warning('Could not write transaction SMS log.', ['error' => $e->getMessage()]);
        }
    }
}
