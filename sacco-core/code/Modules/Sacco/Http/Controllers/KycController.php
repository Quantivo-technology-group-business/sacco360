<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Client\Entities\Client;
use Modules\ActivityLog\Services\AuditLogger;
use Modules\Sacco\Entities\SaccoCrbCheck;
use Modules\Sacco\Entities\SaccoKycRequirement;
use Modules\Sacco\Services\CrbIntegrationService;
use Modules\Sacco\Services\KycService;
use Yajra\DataTables\Facades\DataTables;

class KycController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.kyc.index'])->only(['index', 'show', 'search', 'get_kyc_pending']);
        $this->middleware(['permission:sacco.kyc.manage'])->only([
            'storeDocument', 'verifyIprs', 'approveDocument', 'rejectDocument',
            'approveKyc', 'rejectKyc', 'requirements', 'storeRequirement', 'toggleRequirement',
            'runCrbCheck',
        ]);
        $this->middleware(['permission:sacco.kyc.index'])->only(['crbHistory', 'crbDetail']);
    }

    public function index(KycService $service)
    {
        $stats = $service->getDashboardStats();
        return theme_view('sacco::kyc.index', compact('stats'));
    }

    public function get_kyc_pending(Request $request)
    {
        $pendingIds = DB::table('sacco_kyc_documents')->where('status', 'pending')->pluck('client_id');

        $query = Client::where('kyc_status', 'pending_review')
            ->orWhereIn('id', $pendingIds)
            ->orWhere(function ($q) {
                $q->whereNull('kyc_status')->orWhere('kyc_status', 'incomplete');
            })
            ->select(['id', 'first_name', 'last_name', 'member_number', 'external_id', 'kyc_status', 'iprs_verified_at', 'updated_at']);

        return DataTables::of($query)
            ->addColumn('name', fn($c) => $c->first_name . ' ' . $c->last_name)
            ->addColumn('member_no', fn($c) => $c->member_number ?? $c->external_id ?? $c->id)
            ->addColumn('kyc_badge', function ($c) {
                $status = $c->kyc_status ?? 'incomplete';
                $colour = $status === 'verified' ? 'success' : ($status === 'pending_review' ? 'warning' : 'danger');
                return '<span class="badge badge-' . $colour . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
            })
            ->addColumn('iprs', fn($c) => $c->iprs_verified_at
                ? '<span class="text-success"><i class="fas fa-check mr-1"></i>Verified</span>'
                : '<span class="text-muted">Not verified</span>'
            )
            ->addColumn('action', fn($c) =>
                '<a href="' . url('sacco/kyc/' . $c->id . '/show') . '" class="btn btn-xs btn-primary"><i class="fas fa-search mr-1"></i>Review</a>'
            )
            ->rawColumns(['kyc_badge', 'iprs', 'action'])
            ->make(true);
    }

    public function search(Request $request)
    {
        $q = trim($request->get('q', ''));
        $clients = collect();
        if ($q !== '') {
            $clients = Client::where('first_name', 'like', "%{$q}%")
                ->orWhere('last_name', 'like', "%{$q}%")
                ->orWhere('external_id', 'like', "%{$q}%")
                ->orWhere('member_number', 'like', "%{$q}%")
                ->limit(20)
                ->get();
        }

        return theme_view('sacco::kyc.search', compact('clients', 'q'));
    }

    public function show(int $clientId, KycService $service, CrbIntegrationService $crb)
    {
        $profile   = $service->getMemberProfile($clientId);
        $crbLatest = $crb->latestValid($clientId);
        $crbChecks = $crb->history($clientId, 5);

        return theme_view('sacco::kyc.show', array_merge($profile, [
            'crb_latest' => $crbLatest,
            'crb_checks' => $crbChecks,
            'crb_provider' => ucfirst(config('sacco.crb_provider', 'metropol')),
            'crb_configured' => !empty(config('sacco.crb_api_url')),
        ]));
    }

    public function storeDocument(Request $request, int $clientId, KycService $service)
    {
        $request->validate([
            'document_type' => 'required|string|max:50',
            'document_number' => 'nullable|string|max:100',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $service->uploadDocument($clientId, $request->only([
            'document_type', 'document_number', 'issue_date', 'expiry_date',
        ]), $request->file('file'), auth()->id());

        \flash('Document uploaded and queued for review.')->success()->important();

        return redirect('sacco/kyc/' . $clientId . '/show');
    }

    public function verifyIprs(Request $request, int $clientId, KycService $service)
    {
        $request->validate(['id_number' => 'nullable|string|max:20']);
        $result = $service->verifyIprs($clientId, $request->id_number, auth()->id());

        if (!empty($result['success'])) {
            \flash($result['message'])->success()->important();
        } else {
            \flash($result['message'])->warning()->important();
        }

        return redirect('sacco/kyc/' . $clientId . '/show');
    }

    public function approveDocument(int $documentId, KycService $service)
    {
        $doc = $service->approveDocument($documentId, auth()->id());
        \flash('Document approved.')->success()->important();

        return redirect('sacco/kyc/' . $doc->client_id . '/show');
    }

    public function rejectDocument(Request $request, int $documentId, KycService $service)
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $doc = $service->rejectDocument($documentId, auth()->id(), $request->reason);
        \flash('Document rejected.')->warning()->important();

        return redirect('sacco/kyc/' . $doc->client_id . '/show');
    }

    public function approveKyc(Request $request, int $clientId, KycService $service)
    {
        $request->validate(['notes' => 'nullable|string|max:1000']);
        try {
            $service->approveKyc($clientId, auth()->id(), $request->notes);
            \flash('Member KYC approved.')->success()->important();
        } catch (\RuntimeException $e) {
            \flash($e->getMessage())->warning()->important();
        }

        return redirect('sacco/kyc/' . $clientId . '/show');
    }

    public function rejectKyc(Request $request, int $clientId, KycService $service)
    {
        $request->validate(['reason' => 'required|string|max:1000']);
        $service->rejectKyc($clientId, auth()->id(), $request->reason);
        \flash('Member KYC rejected.')->warning()->important();

        return redirect('sacco/kyc/' . $clientId . '/show');
    }

    public function requirements()
    {
        $requirements = SaccoKycRequirement::orderBy('sort_order')->get();

        return theme_view('sacco::kyc.requirements', compact('requirements'));
    }

    public function storeRequirement(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:sacco_kyc_requirements,code',
            'name' => 'required|string|max:255',
            'category' => 'required|in:identity,tax,address,employment,biometric,other',
            'mandatory' => 'nullable|in:0,1',
            'requires_iprs' => 'nullable|in:0,1',
            'applies_to' => 'required|in:individual,organisation,both',
        ]);

        SaccoKycRequirement::create([
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'category' => $request->category,
            'mandatory' => (int)($request->mandatory ?? 1),
            'requires_iprs' => (int)($request->requires_iprs ?? 0),
            'sort_order' => (int)($request->sort_order ?? 99),
            'active' => 1,
            'applies_to' => $request->applies_to,
        ]);

        \flash('KYC requirement added.')->success()->important();

        return redirect('sacco/kyc/requirements');
    }

    public function toggleRequirement(int $id)
    {
        $req = SaccoKycRequirement::findOrFail($id);
        $req->active = $req->active ? 0 : 1;
        $req->save();

        return redirect('sacco/kyc/requirements');
    }

    // ─── CRB Check ────────────────────────────────────────────────────────────

    public function runCrbCheck(Request $request, int $clientId, CrbIntegrationService $crb)
    {
        $request->validate([
            'check_type'        => 'nullable|in:full,score_only,listing_only',
            'consent_obtained'  => 'nullable|boolean',
            'consent_reference' => 'nullable|string|max:100',
        ]);

        if (!(bool)$request->input('consent_obtained', true)) {
            \flash('Member consent must be obtained before running a CRB check.')->warning()->important();
            return redirect('sacco/kyc/' . $clientId . '/show');
        }

        $result = $crb->check(
            $clientId,
            auth()->id(),
            $request->input('check_type', 'full'),
            true,
            $request->input('consent_reference')
        );

        AuditLogger::log(
            'CRB Check Performed',
            Client::find($clientId),
            ['crb_check_id' => $result->id, 'status' => $result->status, 'score' => $result->credit_score, 'recommendation' => $result->recommendation],
            'sacco',
            'view'
        );

        $statusMessages = [
            'clean'     => 'CRB check complete — member is <strong>clean</strong>.',
            'listed'    => 'CRB check complete — member has <strong>negative listings</strong>. Review before approving credit.',
            'simulated' => 'Simulated CRB result returned (API not configured).',
            'error'     => 'CRB check failed: ' . $result->error_message,
        ];

        $msg  = $statusMessages[$result->status] ?? 'CRB check completed.';
        $type = in_array($result->status, ['clean', 'simulated']) ? 'success' : ($result->status === 'error' ? 'error' : 'warning');

        \flash($msg)->{$type}()->important();

        return redirect('sacco/kyc/' . $clientId . '/show');
    }

    public function crbHistory(int $clientId, CrbIntegrationService $crb)
    {
        $client    = Client::findOrFail($clientId);
        $checks    = $crb->history($clientId, 50);

        return theme_view('sacco::kyc.crb_history', compact('client', 'checks'));
    }

    public function crbDetail(int $checkId)
    {
        $check  = SaccoCrbCheck::with(['client', 'checkedBy'])->findOrFail($checkId);
        $client = $check->client;

        return theme_view('sacco::kyc.crb_detail', compact('check', 'client'));
    }
}
