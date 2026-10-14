<?php

namespace Modules\Portal\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Sacco\Services\TransactionOtpService;

trait RequiresTransactionAuth
{
    protected function verifyTransactionAuth(Request $request, int $clientId, float $amount, string $purpose): ?RedirectResponse
    {
        $service = new TransactionOtpService();
        if (!$service->requiresAuth($amount)) {
            return null;
        }

        if (config('sacco.require_transaction_pin', true) && !$service->hasPin($clientId)) {
            return redirect('portal/client')->with('error', trans('sacco::general.transaction_pin_required'));
        }

        $pin = $request->input('transaction_pin');
        $otp = $request->input('otp_code');

        if (!$service->verifyPinOrOtp($clientId, $pin, $otp, $purpose)) {
            return redirect()->back()->withInput()->with('error', trans('sacco::general.invalid_transaction_auth'));
        }

        return null;
    }
}
