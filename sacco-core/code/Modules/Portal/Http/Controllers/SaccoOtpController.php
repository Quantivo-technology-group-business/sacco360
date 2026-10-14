<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Services\TransactionOtpService;

class SaccoOtpController extends Controller
{
    use ResolvesPortalClient;

    public function request(Request $request, TransactionOtpService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $request->validate([
            'purpose' => 'required|string|max:50',
            'reference' => 'nullable|string|max:100',
        ]);

        $result = $service->requestOtp($client->id, $request->purpose, $request->reference);

        $message = $result['sent']
            ? trans('sacco::general.otp_sent')
            : trans('sacco::general.otp_sent_dev');

        return redirect()->back()->with('success', $message);
    }
}
