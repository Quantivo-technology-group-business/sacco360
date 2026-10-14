<?php

namespace Modules\Portal\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Client\Entities\Client;
use Modules\Client\Entities\ClientUser;

trait ResolvesPortalClient
{
    protected function getClient(): ?Client
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $clientId = session('client_id')
            ?? ClientUser::where('user_id', $user->id)->value('client_id');

        return $clientId ? Client::find($clientId) : null;
    }

    protected function requireClient()
    {
        $client = $this->getClient();
        if (!$client) {
            return redirect('portal')->with('error', 'No member profile linked.');
        }
        return $client;
    }

    protected function requireSaccoModule(): void
    {
        if (!class_exists(\Modules\Sacco\Services\WingService::class)) {
            abort(404);
        }
    }

    protected function requireKyc(int $clientId, string $redirectTo = 'portal/sacco/transfer'): ?RedirectResponse
    {
        if (!function_exists('sacco_validate_kyc')) {
            return null;
        }
        $kyc = sacco_validate_kyc($clientId);
        if (!$kyc['allowed']) {
            return redirect($redirectTo)->with('error', $kyc['reason']);
        }
        return null;
    }
}
