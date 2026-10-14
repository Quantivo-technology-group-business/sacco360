<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoGuarantorSubstitution;
use Modules\Sacco\Services\GuarantorSubstitutionService;
use Modules\Loan\Entities\Loan;
use Modules\Client\Entities\Client;

class GuarantorSubstitutionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware('permission:sacco.guarantor_sub.index')->only(['index', 'show']);
        $this->middleware('permission:sacco.guarantor_sub.create')->only(['create', 'store']);
        $this->middleware('permission:sacco.guarantor_sub.approve')->only(['approve', 'reject', 'consent']);
    }

    public function index()
    {
        $substitutions = SaccoGuarantorSubstitution::with([
            'loan', 'outgoingGuarantor', 'incomingGuarantor',
        ])->orderByDesc('id')->paginate(20);

        return theme_view('sacco::guarantor_sub.index', compact('substitutions'));
    }

    public function create()
    {
        $loans   = Loan::whereIn('status', ['disbursed', 'partial'])
            ->with('client')
            ->orderByDesc('id')
            ->get();
        $clients = Client::where('status', 1)->orderBy('first_name')->get();
        return theme_view('sacco::guarantor_sub.create', compact('loans', 'clients'));
    }

    public function store(Request $request, GuarantorSubstitutionService $service)
    {
        $request->validate([
            'loan_id'                        => 'required|exists:loans,id',
            'outgoing_guarantor_client_id'   => 'required|exists:clients,id',
            'incoming_guarantor_client_id'   => 'required|exists:clients,id|different:outgoing_guarantor_client_id',
            'reason'                         => 'required|string',
        ]);

        try {
            $sub = $service->submitRequest(array_merge(
                $request->only([
                    'loan_id', 'outgoing_guarantor_client_id',
                    'incoming_guarantor_client_id', 'guaranteed_amount', 'reason',
                ]),
                ['created_by_id' => Auth::id()]
            ));
            \flash('Guarantor substitution request submitted.')->success()->important();
            return redirect('sacco/guarantor-sub/' . $sub->id . '/show');
        } catch (\Exception $e) {
            \flash($e->getMessage())->error()->important();
            return back()->withInput();
        }
    }

    public function show($id)
    {
        $sub = SaccoGuarantorSubstitution::with([
            'loan.client', 'outgoingGuarantor', 'incomingGuarantor',
        ])->findOrFail($id);
        return theme_view('sacco::guarantor_sub.show', compact('sub'));
    }

    public function consent(Request $request, $id, GuarantorSubstitutionService $service)
    {
        $request->validate(['consent_type' => 'required|in:outgoing,incoming']);
        $sub = SaccoGuarantorSubstitution::findOrFail($id);

        if ($sub->status !== 'pending') {
            \flash('This request is no longer pending.')->error()->important();
            return back();
        }

        if ($request->consent_type === 'outgoing') {
            $service->recordOutgoingConsent($sub);
        } else {
            $service->recordIncomingConsent($sub);
        }

        \flash('Consent recorded.')->success()->important();
        return redirect('sacco/guarantor-sub/' . $id . '/show');
    }

    public function approve($id, GuarantorSubstitutionService $service)
    {
        $sub = SaccoGuarantorSubstitution::findOrFail($id);
        try {
            $service->approve($sub, Auth::id());
            \flash('Guarantor substitution approved and applied.')->success()->important();
        } catch (\Exception $e) {
            \flash($e->getMessage())->error()->important();
        }
        return redirect('sacco/guarantor-sub/' . $id . '/show');
    }

    public function reject(Request $request, $id, GuarantorSubstitutionService $service)
    {
        $request->validate(['rejection_reason' => 'required|string']);
        $sub = SaccoGuarantorSubstitution::findOrFail($id);
        $service->reject($sub, $request->rejection_reason, Auth::id());
        \flash('Request rejected.')->warning()->important();
        return redirect('sacco/guarantor-sub/' . $id . '/show');
    }
}
