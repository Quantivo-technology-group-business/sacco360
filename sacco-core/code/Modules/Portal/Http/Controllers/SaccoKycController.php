<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Services\KycService;

class SaccoKycController extends Controller
{
    use ResolvesPortalClient;

    public function index(KycService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $profile = $service->getMemberProfile($client->id);

        return theme_view('portal::sacco.kyc', $profile);
    }

    public function storeDocument(Request $request, KycService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $request->validate([
            'document_type' => 'required|string|max:50',
            'document_number' => 'nullable|string|max:100',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $service->uploadDocument($client->id, $request->only([
            'document_type', 'document_number',
        ]), $request->file('file'), auth()->id());

        return redirect('portal/sacco/kyc')->with('success', 'Document submitted for review.');
    }
}
