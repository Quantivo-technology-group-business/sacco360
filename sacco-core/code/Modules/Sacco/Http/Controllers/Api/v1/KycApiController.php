<?php

namespace Modules\Sacco\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Client\Entities\Client;
use Modules\Sacco\Entities\SaccoKycRequirement;
use Modules\Sacco\Services\KycService;

class KycApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware(['permission:sacco.kyc.index'])->only(['index', 'show', 'search']);
        $this->middleware(['permission:sacco.kyc.manage'])->only([
            'storeDocument', 'verifyIprs', 'approveDocument', 'rejectDocument',
            'approveKyc', 'rejectKyc', 'requirements', 'storeRequirement', 'toggleRequirement',
        ]);
    }

    public function index(KycService $service)
    {
        $stats = $service->getDashboardStats();
        $pendingIds = DB::table('sacco_kyc_documents')->where('status', 'pending')->pluck('client_id');
        $pending = Client::where('kyc_status', 'pending_review')
            ->orWhereIn('id', $pendingIds)
            ->orWhere(function ($q) {
                $q->whereNull('kyc_status')->orWhere('kyc_status', 'incomplete');
            })
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return response()->json(['data' => ['stats' => $stats, 'pending' => $pending]]);
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

        return response()->json(['data' => $clients]);
    }

    public function show(int $clientId, KycService $service)
    {
        return response()->json(['data' => $service->getMemberProfile($clientId)]);
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

        $document = $service->uploadDocument($clientId, $request->only([
            'document_type', 'document_number', 'issue_date', 'expiry_date',
        ]), $request->file('file'), auth('api')->id());

        return response()->json(['message' => 'Document uploaded', 'data' => $document], 201);
    }

    public function approveDocument(int $documentId, KycService $service)
    {
        $document = $service->approveDocument($documentId, auth('api')->id());
        return response()->json(['message' => 'Document approved', 'data' => $document]);
    }

    public function rejectDocument(Request $request, int $documentId, KycService $service)
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $document = $service->rejectDocument($documentId, auth('api')->id(), $request->reason);
        return response()->json(['message' => 'Document rejected', 'data' => $document]);
    }

    public function approveKyc(int $clientId, KycService $service)
    {
        $client = $service->approveKyc($clientId, auth('api')->id());
        return response()->json(['message' => 'KYC approved', 'data' => $client]);
    }

    public function rejectKyc(Request $request, int $clientId, KycService $service)
    {
        $request->validate(['reason' => 'required|string|max:500']);
        $client = $service->rejectKyc($clientId, auth('api')->id(), $request->reason);
        return response()->json(['message' => 'KYC rejected', 'data' => $client]);
    }

    public function verifyIprs(Request $request, int $clientId, KycService $service)
    {
        $request->validate(['id_number' => 'required|string']);
        $result = $service->verifyIprs($clientId, $request->id_number, auth('api')->id());
        return response()->json(['data' => $result]);
    }

    public function requirements()
    {
        $requirements = SaccoKycRequirement::orderBy('sort_order')->get();
        return response()->json(['data' => $requirements]);
    }

    public function storeRequirement(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'document_type' => 'required|string|max:50',
            'is_mandatory' => 'boolean',
        ]);

        $requirement = SaccoKycRequirement::create($request->only(['name', 'document_type', 'is_mandatory', 'description']));
        return response()->json(['message' => 'Requirement created', 'data' => $requirement], 201);
    }

    public function toggleRequirement(int $id)
    {
        $requirement = SaccoKycRequirement::findOrFail($id);
        $requirement->is_active = !$requirement->is_active;
        $requirement->save();
        return response()->json(['message' => 'Requirement updated', 'data' => $requirement]);
    }
}
