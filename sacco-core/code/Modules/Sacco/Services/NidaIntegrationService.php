<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Client\Entities\Client;
use Modules\Sacco\Entities\SaccoKycDocument;
use Modules\Sacco\Entities\SaccoKycVerification;

class NidaIntegrationService
{
    public function validateId(string $idNumber, ?string $firstName = null, ?string $lastName = null): array
    {
        return $this->callApi($idNumber, $firstName, $lastName);
    }

    public function verifyMember(
        int $clientId,
        string $idNumber,
        ?string $firstName = null,
        ?string $lastName = null,
        ?int $verifiedById = null
    ): array {
        $result = $this->callApi($idNumber, $firstName, $lastName);
        $matchStatus = $this->determineMatchStatus($result, $firstName, $lastName);

        SaccoKycVerification::create([
            'client_id' => $clientId,
            'verified_by_id' => $verifiedById,
            'provider' => empty(config('sacco.nida_api_url')) ? 'iprs' : 'nida',
            'id_number' => $idNumber,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'match_status' => $matchStatus,
            'response_data' => $result,
            'notes' => $result['message'] ?? null,
        ]);

        if (in_array($matchStatus, ['matched', 'simulated'], true)) {
            Client::where('id', $clientId)->update(['iprs_verified_at' => now()]);

            $existing = SaccoKycDocument::where('client_id', $clientId)
                ->where('document_type', 'national_id')
                ->where('status', 'pending')
                ->first();
            if ($existing) {
                $existing->update([
                    'status' => 'approved',
                    'reviewed_at' => now(),
                    'document_number' => $idNumber,
                ]);
            }

            return [
                'success' => true,
                'match_status' => $matchStatus,
                'message' => $result['message'] ?? 'IPRS/NIDA verification successful.',
                'data' => $result,
            ];
        }

        return [
            'success' => false,
            'match_status' => $matchStatus,
            'message' => $result['message'] ?? 'IPRS/NIDA verification failed.',
            'data' => $result,
        ];
    }

    protected function callApi(string $idNumber, ?string $firstName, ?string $lastName): array
    {
        $payload = [
            'national_id' => $idNumber,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ];

        if (empty(config('sacco.nida_api_url'))) {
            $simulated = (bool)preg_match('/^\d{7,8}$/', $idNumber);
            return [
                'valid' => $simulated ? true : null,
                'status' => 'simulated',
                'message' => $simulated
                    ? 'IPRS simulated match (configure SACCO_NIDA_API_URL for live verification).'
                    : 'IPRS simulated: invalid ID format. Use 7–8 digit Kenyan national ID.',
                'first_name' => $firstName,
                'last_name' => $lastName,
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('sacco.nida_api_key'),
            ])->timeout(30)->post(config('sacco.nida_api_url') . '/verify', $payload);

            $data = $response->json() ?? ['valid' => false, 'message' => 'Invalid response'];
            if (Schema::hasTable('sacco_integration_logs')) {
                DB::table('sacco_integration_logs')->insert([
                    'provider' => 'nida',
                    'action' => 'id_validation',
                    'request_payload' => json_encode($payload),
                    'response_payload' => json_encode($data),
                    'status' => $response->successful() ? 'success' : 'failed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $data;
        } catch (\Exception $e) {
            return ['valid' => false, 'status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function determineMatchStatus(array $result, ?string $firstName, ?string $lastName): string
    {
        if (($result['status'] ?? '') === 'simulated') {
            return !empty($result['valid']) ? 'simulated' : 'mismatched';
        }
        if (($result['status'] ?? '') === 'error') {
            return 'error';
        }
        if (empty($result['valid'])) {
            return ($result['status'] ?? '') === 'not_found' ? 'not_found' : 'mismatched';
        }

        $apiFirst = strtolower(trim($result['first_name'] ?? ''));
        $apiLast = strtolower(trim($result['last_name'] ?? ''));
        if ($apiFirst && $firstName && !str_contains($apiFirst, strtolower(trim($firstName)))) {
            return 'mismatched';
        }
        if ($apiLast && $lastName && !str_contains($apiLast, strtolower(trim($lastName)))) {
            return 'mismatched';
        }

        return 'matched';
    }
}
