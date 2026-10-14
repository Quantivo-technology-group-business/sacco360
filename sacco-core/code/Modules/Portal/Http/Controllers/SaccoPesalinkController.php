<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Portal\Http\Controllers\Concerns\RequiresTransactionAuth;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Entities\SaccoPesalinkTransaction;
use Modules\Sacco\Services\PesalinkService;
use Modules\Sacco\Services\WingService;

class SaccoPesalinkController extends Controller
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

        $transactions = SaccoPesalinkTransaction::where('client_id', $client->id)
            ->orderByDesc('id')
            ->paginate(20);
        $simulation = empty(config('sacco.pesalink_api_url'));

        return theme_view('portal::sacco.pesalink.index', compact('transactions', 'simulation'));
    }

    public function create()
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $wing = new WingService();
        $fosa = $wing->getFosaAccount($client->id);
        $balance = $fosa ? $wing->getAccountBalance($fosa->id) : 0;
        $simulation = empty(config('sacco.pesalink_api_url'));

        return theme_view('portal::sacco.pesalink.create', compact('fosa', 'balance', 'simulation'));
    }

    public function store(Request $request, PesalinkService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        if ($redirect = $this->requireKyc($client->id, 'portal/sacco/pesalink/create')) {
            return $redirect;
        }

        $wing = new WingService();
        $fosa = $wing->getFosaAccount($client->id);
        if (!$fosa) {
            return redirect('portal/sacco/pesalink/create')->with('error', 'No active FOSA account found.');
        }

        $request->validate([
            'beneficiary_account' => 'required',
            'beneficiary_bank' => 'required',
            'beneficiary_name' => 'required',
            'amount' => 'required|numeric|min:1',
        ]);

        if ($redirect = $this->verifyTransactionAuth($request, $client->id, (float) $request->amount, 'pesalink')) {
            return $redirect;
        }

        try {
            $txn = $service->sendFromFosa(array_merge($request->all(), ['savings_id' => $fosa->id]));
            $msg = $txn->status === 'completed' ? 'Pesalink transfer completed.' : 'Transfer failed: ' . $txn->response_message;
            return redirect('portal/sacco/pesalink')->with($txn->status === 'completed' ? 'success' : 'error', $msg);
        } catch (\Exception $e) {
            return redirect('portal/sacco/pesalink/create')->with('error', $e->getMessage());
        }
    }
}
