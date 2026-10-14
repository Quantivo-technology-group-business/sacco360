<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class RtgsService
{
    public function initiateTransfer(array $data): array
    {
        $payload = [
            'beneficiary_account' => $data['beneficiary_account'],
            'beneficiary_bank' => $data['beneficiary_bank'],
            'beneficiary_name' => $data['beneficiary_name'],
            'amount' => $data['amount'],
            'reference' => $data['reference'] ?? 'RTGS-' . time(),
            'narration' => $data['narration'] ?? 'SACCO transfer',
        ];

        if (empty(config('sacco.rtgs_api_url'))) {
            return [
                'status' => 'simulated',
                'reference' => $payload['reference'],
                'message' => 'RTGS API not configured. Set SACCO_RTGS_API_URL.',
            ];
        }

        try {
            $response = Http::post(config('sacco.rtgs_api_url') . '/transfer', $payload);
            $result = $response->json();
            DB::table('sacco_integration_logs')->insert([
                'provider' => 'rtgs',
                'action' => 'transfer',
                'request_payload' => json_encode($payload),
                'response_payload' => json_encode($result),
                'status' => $response->successful() ? 'success' : 'failed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return $result ?? ['status' => 'error'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
