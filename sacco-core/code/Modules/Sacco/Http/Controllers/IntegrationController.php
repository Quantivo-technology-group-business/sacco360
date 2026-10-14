<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Client\Entities\Client;
use Modules\Sacco\Services\CrbIntegrationService;
use Modules\Sacco\Services\KycService;
use Modules\Sacco\Services\NidaIntegrationService;
use Modules\Sacco\Services\RtgsService;
use Modules\Sacco\Services\ESignatureService;

class IntegrationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.integrations.index']);
    }

    public function index()
    {
        return theme_view('sacco::integration.index');
    }

    public function crbCheck(Request $request, CrbIntegrationService $service)
    {
        $request->validate(['client_id' => 'required']);
        $result = $service->checkClient($request->client_id);
        return back()->with('crb_result', $result);
    }

    public function nidaValidate(Request $request, NidaIntegrationService $service)
    {
        $request->validate(['id_number' => 'required']);
        $result = $service->validateId($request->id_number, $request->first_name, $request->last_name);
        return back()->with('nida_result', $result);
    }

    public function rtgsTransfer(Request $request, RtgsService $service)
    {
        $request->validate([
            'beneficiary_account' => 'required',
            'beneficiary_bank' => 'required',
            'beneficiary_name' => 'required',
            'amount' => 'required|numeric|min:0.01',
        ]);
        $result = $service->initiateTransfer($request->all());
        return back()->with('rtgs_result', $result);
    }

    public function captureSignature(Request $request, ESignatureService $service)
    {
        $request->validate(['client_id' => 'required', 'signature' => 'required']);
        $client = Client::findOrFail($request->client_id);
        $service->capture($client, $request->signature);
        \flash('Signature captured')->success()->important();
        return redirect('client/' . $client->id . '/show');
    }

    public function verifyClientNida(Request $request, KycService $kycService)
    {
        $request->validate(['client_id' => 'required']);
        $client = Client::with('identifications')->findOrFail($request->client_id);
        $result = $kycService->verifyIprs($client->id, $request->id_number, auth()->id());

        if (!empty($result['success'])) {
            \flash($result['message'] ?? 'IPRS/NIDA verification successful')->success()->important();
        } else {
            \flash($result['message'] ?? 'IPRS/NIDA verification could not be completed')->warning()->important();
        }

        return redirect('sacco/kyc/' . $client->id . '/show')->with('nida_result', $result);
    }
}
