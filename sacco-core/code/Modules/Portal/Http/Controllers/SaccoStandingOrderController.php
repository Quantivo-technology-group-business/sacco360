<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Loan\Entities\Loan;
use Modules\Portal\Http\Controllers\Concerns\RequiresTransactionAuth;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Entities\SaccoStandingOrder;
use Modules\Savings\Entities\Savings;

class SaccoStandingOrderController extends Controller
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

        $orders = SaccoStandingOrder::where('client_id', $client->id)
            ->orderByDesc('id')
            ->paginate(20);

        return theme_view('portal::sacco.standing_order.index', compact('orders'));
    }

    public function create()
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $savings = Savings::where('client_id', $client->id)->where('status', 'active')->get();
        $loans = Loan::where('client_id', $client->id)->whereIn('status', ['active', 'disbursed'])->get();

        return theme_view('portal::sacco.standing_order.create', compact('savings', 'loans'));
    }

    public function store(Request $request)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $request->validate([
            'source_savings_id' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'frequency' => 'required|in:daily,weekly,monthly',
            'start_date' => 'required|date',
            'destination_loan_id' => 'nullable',
            'destination_savings_id' => 'nullable',
        ]);

        if (!$request->destination_loan_id && !$request->destination_savings_id) {
            return redirect('portal/sacco/standing_order/create')->with('error', 'Select a destination loan or savings account.');
        }

        Savings::where('id', $request->source_savings_id)->where('client_id', $client->id)->firstOrFail();
        if ($request->destination_loan_id) {
            Loan::where('id', $request->destination_loan_id)->where('client_id', $client->id)->firstOrFail();
        }
        if ($request->destination_savings_id) {
            Savings::where('id', $request->destination_savings_id)->where('client_id', $client->id)->firstOrFail();
        }

        if ($redirect = $this->verifyTransactionAuth($request, $client->id, (float) $request->amount, 'standing_order')) {
            return $redirect;
        }

        $order = new SaccoStandingOrder();
        $order->created_by_id = Auth::id();
        $order->client_id = $client->id;
        $order->fill($request->only(['source_savings_id', 'destination_loan_id', 'destination_savings_id', 'amount', 'frequency', 'start_date', 'end_date', 'description']));
        $order->next_run_date = $request->start_date;
        $order->status = 'active';
        $order->save();

        return redirect('portal/sacco/standing_order')->with('success', 'Standing order created successfully.');
    }

    public function cancel($id)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $order = SaccoStandingOrder::where('id', $id)->where('client_id', $client->id)->firstOrFail();
        $order->status = 'cancelled';
        $order->save();

        return redirect('portal/sacco/standing_order')->with('success', 'Standing order cancelled.');
    }

    public function getAccounts()
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        return response()->json([
            'savings' => Savings::where('client_id', $client->id)->where('status', 'active')->get(['id', 'account_number']),
            'loans' => Loan::where('client_id', $client->id)->whereIn('status', ['active', 'disbursed'])->get(['id', 'principal']),
        ]);
    }
}
