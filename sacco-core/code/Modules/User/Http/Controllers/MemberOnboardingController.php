<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Client\Entities\ClientIdentificationType;
use Modules\User\Services\MemberOnboardingService;

class MemberOnboardingController extends Controller
{
    protected MemberOnboardingService $onboarding;

    public function __construct(MemberOnboardingService $onboarding)
    {
        $this->middleware(['auth', 'registration.enabled']);
        $this->onboarding = $onboarding;
    }

    public function showIdentity()
    {
        $client = $this->requireOnboardingClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $identificationTypes = ClientIdentificationType::all();

        return theme_view('user::auth.onboarding.identity', compact('client', 'identificationTypes'));
    }

    public function storeIdentity(Request $request)
    {
        $client = $this->requireOnboardingClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $request->validate([
            'dob' => ['required', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
            'national_id' => ['required', 'string', 'max:100'],
            'client_identification_type_id' => ['nullable', 'integer'],
            'kra_pin' => ['nullable', 'string', 'max:20'],
        ]);

        $this->onboarding->saveIdentity($client, $request->only([
            'dob', 'address', 'national_id', 'client_identification_type_id', 'kra_pin',
        ]));

        return redirect('register/kyc')->with('success', trans_choice('user::general.onboarding_identity_saved', 1));
    }

    public function showKyc()
    {
        $client = $this->requireOnboardingClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $profile = $this->onboarding->getKycProfile($client->id);

        return theme_view('user::auth.onboarding.kyc', $profile);
    }

    public function storeKycDocument(Request $request)
    {
        $client = $this->requireOnboardingClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        if (!class_exists(\Modules\Sacco\Services\KycService::class)) {
            return redirect('register/kyc')->with('error', trans_choice('user::general.onboarding_kyc_unavailable', 1));
        }

        $request->validate([
            'document_type' => 'required|string|max:50',
            'document_number' => 'nullable|string|max:100',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        (new \Modules\Sacco\Services\KycService())->uploadDocument(
            $client->id,
            $request->only(['document_type', 'document_number']),
            $request->file('file'),
            Auth::id()
        );

        return redirect('register/kyc')->with('success', trans_choice('user::general.onboarding_document_submitted', 1));
    }

    public function complete()
    {
        $client = $this->requireOnboardingClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $this->onboarding->completeOnboarding($client);

        return redirect('portal/dashboard')->with('success', trans_choice('user::general.onboarding_complete', 1));
    }

    protected function requireOnboardingClient()
    {
        if (!Auth::user()->hasRole('client')) {
            return redirect('dashboard');
        }

        $client = $this->onboarding->getClientForUser(Auth::user());

        if (!$client) {
            Auth::logout();

            return redirect('login')->with('error', trans_choice('user::general.onboarding_no_client', 1));
        }

        if ($this->onboarding->isOnboardingComplete($client)) {
            return redirect('portal/dashboard');
        }

        if (!$this->onboarding->isDetailWorkflow()) {
            return redirect('portal/dashboard');
        }

        return $client;
    }
}
