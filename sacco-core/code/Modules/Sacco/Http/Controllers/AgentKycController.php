<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sacco\Entities\SaccoAgent;
use Modules\Sacco\Entities\SaccoAgentKycDocument;
use Modules\Sacco\Entities\SaccoAgentKycRequirement;
use Modules\Sacco\Services\AgentKycService;

class AgentKycController extends Controller
{
    protected AgentKycService $kycService;

    public function __construct(AgentKycService $kycService)
    {
        $this->kycService = $kycService;
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.agency.index'])->only(['index', 'search', 'show']);
        $this->middleware(['permission:sacco.agency.manage'])->only([
            'storeDocument', 'approveDocument', 'rejectDocument',
            'approveKyc', 'rejectKyc', 'requirements', 'storeRequirement',
            'updateRequirement', 'deleteRequirement'
        ]);
    }

    public function index()
    {
        $stats = $this->kycService->getDashboardStats();
        
        $pendingAgents = SaccoAgent::with('branch')
            ->where('kyc_status', 'pending_review')
            ->orderByDesc('updated_at')
            ->take(10)
            ->get();

        $recentDocuments = SaccoAgentKycDocument::with(['agent', 'uploadedBy'])
            ->pending()
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return theme_view('sacco::agent_kyc.index', compact('stats', 'pendingAgents', 'recentDocuments'));
    }

    public function search(Request $request)
    {
        $agents = $this->kycService->searchAgents(
            $request->q,
            $request->kyc_status,
            25
        );

        return theme_view('sacco::agent_kyc.search', compact('agents'));
    }

    public function show($id)
    {
        $agent = SaccoAgent::with(['branch', 'kycDocuments', 'kycVerifiedBy'])->findOrFail($id);
        $kycStatus = $this->kycService->getAgentKycStatus($agent);
        $requirements = $this->kycService->getRequirements();

        return theme_view('sacco::agent_kyc.show', compact('agent', 'kycStatus', 'requirements'));
    }

    public function storeDocument(Request $request, $agentId)
    {
        $request->validate([
            'document_type' => 'required|string|max:50',
            'document_number' => 'nullable|string|max:100',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $agent = SaccoAgent::findOrFail($agentId);

        try {
            $this->kycService->uploadDocument(
                $agent,
                $request->document_type,
                $request->file('file'),
                $request->document_number
            );

            return back()->with('success', 'Document uploaded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approveDocument($documentId)
    {
        $document = SaccoAgentKycDocument::findOrFail($documentId);

        try {
            $this->kycService->approveDocument($document);
            return back()->with('success', 'Document approved.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function rejectDocument(Request $request, $documentId)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $document = SaccoAgentKycDocument::findOrFail($documentId);

        try {
            $this->kycService->rejectDocument($document, $request->reason);
            return back()->with('success', 'Document rejected.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function approveKyc(Request $request, $agentId)
    {
        $agent = SaccoAgent::findOrFail($agentId);

        try {
            $this->kycService->approveKyc($agent, $request->notes);
            return back()->with('success', 'Agent KYC approved successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function rejectKyc(Request $request, $agentId)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $agent = SaccoAgent::findOrFail($agentId);

        try {
            $this->kycService->rejectKyc($agent, $request->reason);
            return back()->with('success', 'Agent KYC rejected.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function requirements()
    {
        $requirements = SaccoAgentKycRequirement::ordered()->get();
        $categories = SaccoAgentKycRequirement::getCategories();

        return theme_view('sacco::agent_kyc.requirements', compact('requirements', 'categories'));
    }

    public function storeRequirement(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:sacco_agent_kyc_requirements,code',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'description' => 'nullable|string',
            'is_mandatory' => 'nullable|boolean',
            'requires_verification' => 'nullable|boolean',
        ]);

        $maxOrder = SaccoAgentKycRequirement::max('sort_order') ?? 0;

        SaccoAgentKycRequirement::create([
            'code' => $request->code,
            'name' => $request->name,
            'category' => $request->category,
            'description' => $request->description,
            'is_mandatory' => $request->boolean('is_mandatory'),
            'requires_verification' => $request->boolean('requires_verification'),
            'active' => true,
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('success', 'Requirement added successfully.');
    }

    public function updateRequirement(Request $request, $id)
    {
        $requirement = SaccoAgentKycRequirement::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:50',
            'description' => 'nullable|string',
            'is_mandatory' => 'nullable|boolean',
            'requires_verification' => 'nullable|boolean',
            'active' => 'nullable|boolean',
        ]);

        $requirement->update([
            'name' => $request->name,
            'category' => $request->category,
            'description' => $request->description,
            'is_mandatory' => $request->boolean('is_mandatory'),
            'requires_verification' => $request->boolean('requires_verification'),
            'active' => $request->boolean('active', true),
        ]);

        return back()->with('success', 'Requirement updated.');
    }

    public function deleteRequirement($id)
    {
        $requirement = SaccoAgentKycRequirement::findOrFail($id);
        
        // Don't allow deleting if documents exist
        $docCount = SaccoAgentKycDocument::where('document_type', $requirement->code)->count();
        if ($docCount > 0) {
            return back()->with('error', "Cannot delete: {$docCount} documents are linked to this requirement. Deactivate instead.");
        }

        $requirement->delete();
        return back()->with('success', 'Requirement deleted.');
    }
}
