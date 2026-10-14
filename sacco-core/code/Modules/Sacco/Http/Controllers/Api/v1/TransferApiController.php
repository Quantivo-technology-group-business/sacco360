<?php

namespace Modules\Sacco\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Client\Entities\Client;
use Modules\Loan\Entities\Loan;
use Modules\Sacco\Entities\SaccoTransfer;
use Modules\Sacco\Services\TransferService;
use Modules\Savings\Entities\Savings;
use Modules\Share\Entities\Share;

class TransferApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware(['permission:sacco.transfers.index'])->only(['index', 'show', 'create']);
        $this->middleware(['permission:sacco.transfers.create'])->only(['store']);
    }

    public function index(Request $request)
    {
        $limit = $request->limit ?: 20;
        $transfers = SaccoTransfer::with('client')
            ->when($request->client_id, fn ($q) => $q->where('client_id', $request->client_id))
            ->orderByDesc('id')
            ->paginate($limit);

        return response()->json([$transfers]);
    }

    public function create(Request $request)
    {
        $clientId = $request->client_id;
        $clients = Client::where('status', 'active')->get(['id', 'first_name', 'last_name', 'member_number']);
        $savings = $clientId ? Savings::where('client_id', $clientId)->where('status', 'active')->get() : collect();
        $loans = $clientId ? Loan::where('client_id', $clientId)->whereIn('status', ['active', 'disbursed'])->get() : collect();
        $shares = $clientId ? Share::where('client_id', $clientId)->where('status', 'active')->get() : collect();

        return response()->json([
            'data' => compact('clients', 'savings', 'loans', 'shares', 'clientId'),
        ]);
    }

    public function store(Request $request, TransferService $service)
    {
        $request->validate([
            'client_id' => 'required',
            'transfer_type' => 'required|in:savings_to_loan,savings_to_savings,share_to_savings,savings_to_share',
            'source_id' => 'required',
            'destination_id' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'transfer_date' => 'required|date',
        ]);

        try {
            $transfer = $service->execute($request->all());
            return response()->json(['message' => 'Transfer completed', 'data' => $transfer], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function show($id)
    {
        $transfer = SaccoTransfer::with('client')->findOrFail($id);
        return response()->json(['data' => $transfer]);
    }
}
