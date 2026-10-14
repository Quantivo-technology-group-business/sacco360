<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Client\Entities\Client;
use Modules\Sacco\Entities\SaccoCrbCheck;

class CrbIntegrationService
{
    // Score bands (Metropol / TransUnion Kenya style — 300-900)
    private const SCORE_BANDS = [
        [700, 900, 'A', 'Excellent'],
        [600, 699, 'B', 'Good'],
        [500, 599, 'C', 'Fair'],
        [400, 499, 'D', 'Poor'],
        [300, 399, 'E', 'Very Poor'],
    ];

    /**
     * Run a CRB check for a client and persist the result.
     *
     * @param  int         $clientId
     * @param  int|null    $checkedById   Staff user performing the check
     * @param  string      $checkType     full | score_only | listing_only
     * @param  bool        $consentObtained
     * @param  string|null $consentRef
     * @return SaccoCrbCheck
     */
    public function check(
        int $clientId,
        ?int $checkedById = null,
        string $checkType = 'full',
        bool $consentObtained = true,
        ?string $consentRef = null
    ): SaccoCrbCheck {
        $client = Client::with('identifications')->findOrFail($clientId);
        $idNumber = $this->resolveIdNumber($client);
        $provider = config('sacco.crb_provider', 'metropol');

        $record = SaccoCrbCheck::create([
            'client_id'         => $clientId,
            'checked_by_id'     => $checkedById,
            'id_number'         => $idNumber,
            'provider'          => $provider,
            'status'            => 'pending',
            'check_type'        => $checkType,
            'consent_obtained'  => $consentObtained,
            'consent_reference' => $consentRef,
            'consent_date'      => $consentObtained ? now() : null,
        ]);

        $payload = $this->buildPayload($client, $idNumber, $checkType);
        $record->raw_request = json_encode($payload);

        if (empty(config('sacco.crb_api_url'))) {
            $this->applySimulatedResult($record, $client);
        } else {
            $this->callCrbApi($record, $payload, $provider);
        }

        $record->save();

        $this->logIntegration('crb', 'credit_check', $clientId, $payload, json_decode($record->raw_response, true), $record->status);

        return $record;
    }

    /**
     * Return all past CRB checks for a client, newest first.
     */
    public function history(int $clientId, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return SaccoCrbCheck::where('client_id', $clientId)
            ->with('checkedBy')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Return the most recent valid (unexpired) CRB check for a client, if any.
     */
    public function latestValid(int $clientId): ?SaccoCrbCheck
    {
        return SaccoCrbCheck::where('client_id', $clientId)
            ->where('status', '!=', 'error')
            ->where(function ($q) {
                $q->whereNull('valid_until')
                  ->orWhere('valid_until', '>=', now()->toDateString());
            })
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Dashboard stats across all clients.
     */
    public function stats(): array
    {
        return [
            'total_checks'  => SaccoCrbCheck::count(),
            'clean'         => SaccoCrbCheck::where('status', 'clean')->count(),
            'listed'        => SaccoCrbCheck::where('status', 'listed')->count(),
            'errors'        => SaccoCrbCheck::where('status', 'error')->count(),
            'pending'       => SaccoCrbCheck::where('status', 'pending')->count(),
            'simulated'     => SaccoCrbCheck::where('status', 'simulated')->count(),
            'checks_today'  => SaccoCrbCheck::whereDate('created_at', today())->count(),
            'checks_month'  => SaccoCrbCheck::whereYear('created_at', date('Y'))
                                             ->whereMonth('created_at', date('m'))->count(),
            'avg_score'     => (int) SaccoCrbCheck::whereNotNull('credit_score')->avg('credit_score'),
        ];
    }

    // ─── Private helpers ───────────────────────────────────────────────────

    private function resolveIdNumber(Client $client): ?string
    {
        // Prefer IPRS-verified national ID first
        if ($client->identifications->isNotEmpty()) {
            return $client->identifications->first()->identification_value;
        }
        // Fallback: external_id / KRA PIN
        return $client->external_id ?: $client->kra_pin;
    }

    private function buildPayload(Client $client, ?string $idNumber, string $checkType): array
    {
        return [
            'national_id'   => $idNumber,
            'first_name'    => $client->first_name,
            'last_name'     => $client->last_name,
            'middle_name'   => $client->middle_name ?? '',
            'mobile'        => $client->mobile ?? $client->phone,
            'kra_pin'       => $client->kra_pin,
            'dob'           => $client->dob,
            'check_type'    => $checkType,
            'consent'       => true,
            'member_number' => $client->member_number,
        ];
    }

    private function callCrbApi(SaccoCrbCheck $record, array $payload, string $provider): void
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . config('sacco.crb_api_key'),
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ])
                ->post(rtrim(config('sacco.crb_api_url'), '/') . '/v1/credit-check', $payload);

            $data = $response->json() ?? [];
            $record->raw_response = json_encode($data);

            if (!$response->successful()) {
                $record->status = 'error';
                $record->error_message = $data['message'] ?? ('HTTP ' . $response->status());
                return;
            }

            $this->mapResponseToRecord($record, $data, $provider);

        } catch (\Exception $e) {
            $record->status = 'error';
            $record->error_message = $e->getMessage();
            $record->raw_response = json_encode(['error' => $e->getMessage()]);
            Log::error('CRB API error', ['client_id' => $record->client_id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Map standardised API response to record fields.
     * Supports Metropol and TransUnion Kenya response formats.
     */
    private function mapResponseToRecord(SaccoCrbCheck $record, array $data, string $provider): void
    {
        if ($provider === 'transunion') {
            $score      = $data['score'] ?? $data['creditScore'] ?? null;
            $isListed   = !empty($data['hasNegative']) || !empty($data['listed']);
            $listings   = (int)($data['negativeCount'] ?? 0);
            $npl        = (int)($data['nplCount']      ?? 0);
            $ref        = $data['referenceNo']          ?? null;
            $summary    = $data['summary']              ?? null;
        } else {
            // Metropol (default / most common in Kenya)
            $score      = $data['credit_score']   ?? $data['score']        ?? null;
            $isListed   = !empty($data['is_listed']) || !empty($data['negative_listing']);
            $listings   = (int)($data['negative_listings'] ?? $data['listing_count'] ?? 0);
            $npl        = (int)($data['npl_count']          ?? 0);
            $ref        = $data['reference_number']         ?? $data['ref']   ?? null;
            $summary    = $data['summary']                  ?? $data['narration'] ?? null;
        }

        $record->credit_score      = $score;
        $record->score_band        = $score ? $this->scoreBand($score) : null;
        $record->is_listed         = $isListed;
        $record->negative_listings = $listings;
        $record->npl_count         = $npl;
        $record->reference_number  = $ref;
        $record->summary           = $summary;
        $record->valid_until       = now()->addDays(30)->toDateString();
        $record->status            = $isListed || $listings > 0 ? 'listed' : 'clean';
        $record->recommendation    = $this->deriveRecommendation($score, $isListed, $listings);
    }

    /**
     * Simulated result when no API is configured — realistic but clearly marked.
     */
    private function applySimulatedResult(SaccoCrbCheck $record, Client $client): void
    {
        // Deterministic but varied seed based on client ID so each member gets a stable simulated score
        srand($client->id * 37 + 13);
        $score = rand(400, 820);
        srand(); // restore randomness

        $isListed   = $score < 450;
        $listings   = $isListed ? rand(1, 3) : 0;
        $npl        = $isListed ? rand(0, 2) : 0;

        $record->status             = 'simulated';
        $record->credit_score       = $score;
        $record->score_band         = $this->scoreBand($score);
        $record->is_listed          = $isListed;
        $record->negative_listings  = $listings;
        $record->npl_count          = $npl;
        $record->recommendation     = $this->deriveRecommendation($score, $isListed, $listings);
        $record->valid_until        = now()->addDays(30)->toDateString();
        $record->summary            = 'Simulated result — configure SACCO_CRB_API_URL and SACCO_CRB_API_KEY for live checks.';
        $record->raw_response       = json_encode(['simulated' => true, 'score' => $score]);
    }

    private function scoreBand(int $score): string
    {
        foreach (self::SCORE_BANDS as [$min, $max, $band, $label]) {
            if ($score >= $min && $score <= $max) {
                return $band . ' — ' . $label;
            }
        }
        return 'E — Very Poor';
    }

    private function deriveRecommendation(?int $score, bool $isListed, int $listings): string
    {
        if ($isListed && $listings >= 2) return 'decline';
        if ($isListed) return 'review';
        if ($score === null) return 'review';
        if ($score >= 600) return 'approve';
        if ($score >= 450) return 'review';
        return 'decline';
    }

    protected function logIntegration(string $provider, string $action, ?int $clientId, $request, $response, string $status): void
    {
        try {
            DB::table('sacco_integration_logs')->insert([
                'provider'         => $provider,
                'action'           => $action,
                'client_id'        => $clientId,
                'request_payload'  => json_encode($request),
                'response_payload' => json_encode($response),
                'status'           => $status,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('CRB integration log failed: ' . $e->getMessage());
        }
    }
}
