<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Client\Entities\Client;
use Modules\Loan\Entities\Loan;
use Modules\Sacco\Entities\SaccoTransfer;
use Modules\ActivityLog\Services\AuditLogger;
use Modules\Sacco\Services\TransferService;
use Modules\Savings\Entities\Savings;
use Modules\Share\Entities\Share;

class TransferController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.transfers.index'])->only(['index', 'show']);
        $this->middleware(['permission:sacco.transfers.create'])->only(['create', 'store']);
    }

    public function index()
    {
        $transfers = SaccoTransfer::with('client')->orderByDesc('id')->paginate(20);
        return theme_view('sacco::transfer.index', compact('transfers'));
    }

    public function create(Request $request)
    {
        $clientId = $request->client_id;
        $clients = Client::where('status', 'active')->get();
        $savings = $clientId ? Savings::where('client_id', $clientId)->where('status', 'active')->get() : collect();
        $loans = $clientId ? Loan::where('client_id', $clientId)->whereIn('status', ['active', 'disbursed'])->get() : collect();
        $shares = $clientId ? Share::where('client_id', $clientId)->where('status', 'active')->get() : collect();
        return theme_view('sacco::transfer.create', compact('clients', 'savings', 'loans', 'shares', 'clientId'));
    }

    public function store(Request $request, TransferService $service)
    {
        $request->validate([
            'client_id' => 'required',
            'transfer_type' => 'required|in:savings_to_loan,savings_to_savings,share_to_savings',
            'source_id' => 'required',
            'destination_id' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'transfer_date' => 'required|date',
        ]);

        try {
            $transfer = $service->execute($request->all());
            AuditLogger::log(
                'BOSA/FOSA Transfer Created',
                $transfer,
                ['id' => $transfer->id, 'type' => $request->transfer_type, 'amount' => $request->amount, 'client_id' => $request->client_id],
                'sacco',
                'transfer'
            );
            \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();
            return redirect('sacco/transfer/' . $transfer->id . '/show');
        } catch (\Exception $e) {
            \flash($e->getMessage())->error()->important();
            return back()->withInput();
        }
    }

    public function show($id)
    {
        $transfer = SaccoTransfer::with('client')->findOrFail($id);
        return theme_view('sacco::transfer.show', compact('transfer'));
    }
}
