<?php

namespace Modules\Portal\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Client\Entities\Client;
use Modules\Client\Entities\ClientUser;

trait ResolvesApiPortalClient
{
    protected function getClientIdFromRequest(Request $request): ?int
    {
        $clientId = $request->header('X-Client-Id') ?? $request->input('client_id');
        if ($clientId) {
            return (int)$clientId;
        }

        $user = auth('api')->user();
        if (!$user) {
            return null;
        }

        return ClientUser::where('user_id', $user->id)->value('client_id');
    }

    /**
     * @return Client|JsonResponse
     */
    protected function requireClient(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $clientId = $this->getClientIdFromRequest($request);
        if (!$clientId) {
            return response()->json(['error' => 'No member profile linked'], 403);
        }

        if (!ClientUser::where('user_id', $user->id)->where('client_id', $clientId)->exists()) {
            return response()->json(['error' => 'Access denied to this member profile'], 403);
        }

        $client = Client::find($clientId);
        if (!$client) {
            return response()->json(['error' => 'Member not found'], 404);
        }

        return $client;
    }

    protected function requireSaccoModule(): ?JsonResponse
    {
        if (!class_exists(\Modules\Sacco\Services\WingService::class)) {
            return response()->json(['error' => 'SACCO module not available'], 404);
        }

        return null;
    }

    protected function requireKyc(int $clientId): ?JsonResponse
    {
        if (!function_exists('sacco_validate_kyc')) {
            return null;
        }

        $kyc = sacco_validate_kyc($clientId);
        if (!$kyc['allowed']) {
            return response()->json(['error' => $kyc['reason']], 403);
        }

        return null;
    }
}
