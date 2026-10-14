<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sacco\Entities\SaccoPesalinkTransaction;
use Modules\Sacco\Services\PesalinkService;

class PesalinkController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.pesalink.index'])->only(['index', 'create']);
        $this->middleware(['permission:sacco.pesalink.transfer'])->only(['store', 'inbound']);
    }

    public function index()
    {
        $transactions = SaccoPesalinkTransaction::orderByDesc('id')->paginate(30);
        return theme_view('sacco::pesalink.index', compact('transactions'));
    }

    public function create()
    {
        return theme_view('sacco::pesalink.create');
    }

    public function store(Request $request, PesalinkService $service)
    {
        $request->validate([
            'savings_id' => 'required',
            'beneficiary_account' => 'required',
            'beneficiary_bank' => 'required',
            'beneficiary_name' => 'required',
            'amount' => 'required|numeric|min:1',
        ]);
        $txn = $service->sendFromFosa($request->all());
        $msg = $txn->status === 'completed' ? 'Pesalink transfer completed' : 'Transfer failed: ' . $txn->response_message;
        return redirect('sacco/pesalink')->with($txn->status === 'completed' ? 'success' : 'error', $msg);
    }

    public function inbound(Request $request, PesalinkService $service)
    {
        $txn = $service->receiveInbound($request->all());
        return response()->json(['status' => $txn->status, 'reference' => $txn->reference]);
    }
}
