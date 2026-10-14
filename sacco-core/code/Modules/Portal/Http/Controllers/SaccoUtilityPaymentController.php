<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Portal\Http\Controllers\Concerns\RequiresTransactionAuth;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Services\UtilityPaymentService;

class SaccoUtilityPaymentController extends Controller
{
    use ResolvesPortalClient;
    use RequiresTransactionAuth;

    public function index(UtilityPaymentService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $payments = $service->listPaymentsForClient($client->id);

        return theme_view('portal::sacco.utility.index', compact('payments'));
    }

    public function create(UtilityPaymentService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $providers = $service->listProviders();

        return theme_view('portal::sacco.utility.create', compact('providers'));
    }

    public function store(Request $request, UtilityPaymentService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        if ($redirect = $this->requireKyc($client->id, 'portal/sacco/utility/create')) {
            return $redirect;
        }

        $request->validate([
            'provider_id' => 'required|integer',
            'account_number' => 'required|string|max:50',
            'amount' => 'required|numeric|min:1',
        ]);

        if ($redirect = $this->verifyTransactionAuth($request, $client->id, (float) $request->amount, 'utility')) {
            return $redirect;
        }

        try {
            $service->payFromFosa(
                $client->id,
                (int) $request->provider_id,
                $request->account_number,
                (float) $request->amount,
                'portal'
            );
            return redirect('portal/sacco/utility')->with('success', trans('core::general.successfully_saved'));
        } catch (\Throwable $e) {
            return redirect('portal/sacco/utility/create')->with('error', $e->getMessage());
        }
    }
}
