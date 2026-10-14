<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sacco\Entities\SaccoAtmTransaction;
use Modules\Sacco\Services\AtmService;

class AtmController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa'])->only(['index']);
    }

    public function process(Request $request, AtmService $service)
    {
        $request->validate([
            'terminal_id' => 'required|string',
            'transaction_type' => 'required|in:balance_inquiry,withdrawal,deposit,mini_statement',
        ]);

        $result = $service->process($request->all());
        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function index()
    {
        $transactions = SaccoAtmTransaction::orderByDesc('id')->paginate(50);
        return theme_view('sacco::atm.index', compact('transactions'));
    }
}
