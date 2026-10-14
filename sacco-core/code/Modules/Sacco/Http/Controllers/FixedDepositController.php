<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sacco\Entities\SaccoFixedDeposit;
use Modules\Sacco\Services\FixedDepositService;
use Modules\Savings\Entities\Savings;

class FixedDepositController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.fixed_deposits.index'])->only(['index', 'show']);
        $this->middleware(['permission:sacco.fixed_deposits.create'])->only(['create', 'store']);
    }

    public function index()
    {
        $deposits = SaccoFixedDeposit::orderByDesc('maturity_date')->paginate(20);
        return theme_view('sacco::fixed_deposit.index', compact('deposits'));
    }

    public function create(Request $request)
    {
        $savings = $request->savings_id ? Savings::with('savings_product')->find($request->savings_id) : null;
        return theme_view('sacco::fixed_deposit.create', compact('savings'));
    }

    public function store(Request $request, FixedDepositService $service)
    {
        $request->validate([
            'savings_id' => 'required',
            'tenure_months' => 'required|integer|min:1',
            'principal' => 'nullable|numeric|min:0',
        ]);

        $savings = Savings::with('savings_product')->findOrFail($request->savings_id);
        $fd = $service->create($savings, $request->all());
        return redirect('sacco/fixed_deposit')->with('success', 'Fixed deposit created. Matures on ' . $fd->maturity_date);
    }

    public function show($id)
    {
        $deposit = SaccoFixedDeposit::with('savings')->findOrFail($id);
        $projectedInterest = (new FixedDepositService())->calculateInterest($deposit);
        return theme_view('sacco::fixed_deposit.show', compact('deposit', 'projectedInterest'));
    }
}
