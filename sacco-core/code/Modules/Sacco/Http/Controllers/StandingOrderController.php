<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Client\Entities\Client;
use Modules\Loan\Entities\Loan;
use Modules\Sacco\Entities\SaccoStandingOrder;
use Modules\Savings\Entities\Savings;

class StandingOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.standing_orders.index'])->only(['index', 'show']);
        $this->middleware(['permission:sacco.standing_orders.create'])->only(['create', 'store']);
    }

    public function index()
    {
        $orders = SaccoStandingOrder::with('client')->orderByDesc('id')->paginate(20);
        return theme_view('sacco::standing_order.index', compact('orders'));
    }

    public function create()
    {
        $clients = Client::where('status', 'active')->get();
        return theme_view('sacco::standing_order.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required',
            'source_savings_id' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'frequency' => 'required|in:daily,weekly,monthly',
            'start_date' => 'required|date',
        ]);

        $order = new SaccoStandingOrder();
        $order->created_by_id = Auth::id();
        $order->fill($request->only(['client_id', 'source_savings_id', 'destination_loan_id', 'destination_savings_id', 'amount', 'frequency', 'start_date', 'end_date', 'description']));
        $order->next_run_date = $request->start_date;
        $order->status = 'active';
        $order->save();

        \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();
        return redirect('sacco/standing_order');
    }

    public function getClientAccounts(Request $request)
    {
        $clientId = $request->client_id;
        return response()->json([
            'savings' => Savings::where('client_id', $clientId)->where('status', 'active')->get(['id', 'account_number']),
            'loans' => Loan::where('client_id', $clientId)->whereIn('status', ['active', 'disbursed'])->get(['id', 'principal']),
        ]);
    }
}
