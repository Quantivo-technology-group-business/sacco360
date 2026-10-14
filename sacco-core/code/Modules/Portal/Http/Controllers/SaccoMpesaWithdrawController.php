<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Portal\Http\Controllers\Concerns\RequiresTransactionAuth;
use Modules\Portal\Http\Controllers\Concerns\ResolvesMpesaSourceAccounts;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Services\MpesaB2cService;

class SaccoMpesaWithdrawController extends Controller
{
    use ResolvesPortalClient;
    use ResolvesMpesaSourceAccounts;
    use RequiresTransactionAuth;

    public function create(MpesaB2cService $mpesaService)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $sources = $this->resolveMpesaSources($client->id);
        extract($sources);
        $memberPhone = $client->mobile ?: $client->phone;
        $simulation = $mpesaService->isSimulationMode();

        return theme_view('portal::sacco.mpesa.withdraw.create', compact(
            'fosa', 'fosaBalance', 'wallet', 'walletBalance', 'memberPhone', 'simulation'
        ));
    }

    public function store(Request $request, MpesaB2cService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        if ($redirect = $this->requireKyc($client->id, 'portal/sacco/mpesa/withdraw/create')) {
            return $redirect;
        }

        $memberPhone = $client->mobile ?: $client->phone;
        if (!$memberPhone) {
            return redirect('portal/sacco/mpesa/withdraw/create')->with('error', trans('sacco::general.no_member_phone'));
        }

        $request->validate([
            'source_type' => 'required|in:fosa,wallet',
            'amount' => 'required|numeric|min:1',
        ]);

        $sources = $this->resolveMpesaSources($client->id);
        if ($request->source_type === 'fosa' && !$sources['fosa']) {
            return redirect('portal/sacco/mpesa/withdraw/create')->with('error', trans('sacco::general.no_fosa_account'));
        }
        if ($request->source_type === 'wallet' && !$sources['wallet']) {
            return redirect('portal/sacco/mpesa/withdraw/create')->with('error', trans('sacco::general.no_wallet_account'));
        }

        if ($redirect = $this->verifyTransactionAuth($request, $client->id, (float) $request->amount, 'mpesa_withdraw')) {
            return $redirect;
        }

        try {
            $payload = array_merge($request->all(), [
                'client_id' => $client->id,
                'recipient_phone' => $memberPhone,
                'recipient_name' => trim($client->first_name . ' ' . $client->last_name),
                'savings_id' => $sources['fosa'] ? $sources['fosa']->id : null,
                'wallet_id' => $sources['wallet'] ? $sources['wallet']->id : null,
            ]);
            $txn = $service->withdrawToSelf($payload);
            $msg = in_array($txn->status, ['completed', 'submitted'], true)
                ? trans('sacco::general.mpesa_withdraw_success')
                : trans('sacco::general.mpesa_withdraw_failed') . ': ' . $txn->response_message;
            $type = in_array($txn->status, ['completed', 'submitted'], true) ? 'success' : 'error';
            return redirect('portal/sacco/mpesa/transactions')->with($type, $msg);
        } catch (\Exception $e) {
            return redirect('portal/sacco/mpesa/withdraw/create')->with('error', $e->getMessage());
        }
    }
}
