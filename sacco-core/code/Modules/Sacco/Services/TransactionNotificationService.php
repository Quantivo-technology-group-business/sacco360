<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Client\Entities\Client;

class TransactionNotificationService
{
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

        try {
            if (DB::getSchemaBuilder()->hasTable('communication_campaign_logs')) {
                DB::table('communication_campaign_logs')->insert([
                    'created_at' => now(),
                    'updated_at' => now(),
                    'client_id' => $client->id,
                    'communication_campaign_id' => null,
                    'campaign_type' => 'sms',
                    'description' => $message,
                    'send_to' => $client->mobile,
                    'campaign_name' => 'SACCO Transaction Alert',
                    'status' => 'pending',
                ]);
            }
        } catch (\Throwable $e) {
            Log::info('SACCO SMS notification: ' . $message);
        }
    }
}
