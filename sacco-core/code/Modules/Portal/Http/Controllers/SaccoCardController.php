<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Portal\Http\Controllers\Concerns\RequiresTransactionAuth;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Entities\SaccoDebitCard;
use Modules\Sacco\Services\CardIssuanceService;

class SaccoCardController extends Controller
{
    use ResolvesPortalClient;
    use RequiresTransactionAuth;

    public function index()
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $cards = SaccoDebitCard::where('client_id', $client->id)
            ->orderByDesc('id')
            ->get();

        return theme_view('portal::sacco.cards', compact('cards'));
    }

    public function block(Request $request, $id, CardIssuanceService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        if ($redirect = $this->requireKyc($client->id, 'portal/sacco/cards')) {
            return $redirect;
        }

        $otpService = new \Modules\Sacco\Services\TransactionOtpService();
        if (!$otpService->verifyPinOrOtp(
            $client->id,
            $request->input('transaction_pin'),
            $request->input('otp_code'),
            'card_block'
        )) {
            return redirect('portal/sacco/cards')->with('error', trans('sacco::general.invalid_transaction_auth'));
        }

        $card = SaccoDebitCard::where('client_id', $client->id)->findOrFail($id);
        if ($card->status === 'blocked') {
            return redirect('portal/sacco/cards')->with('error', 'Card is already blocked.');
        }

        $service->block($card);

        return redirect('portal/sacco/cards')->with('success', trans('sacco::general.card_blocked'));
    }
}
