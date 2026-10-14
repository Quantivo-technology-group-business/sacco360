<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoBoardResolution;
use Modules\Sacco\Entities\SaccoRegulatoryApproval;
use Modules\Sacco\Services\ComplianceAuditService;

class RegulatoryApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.governance.resolutions']);
    }

    public function index()
    {
        $approvals = SaccoRegulatoryApproval::orderByDesc('requested_date')->paginate(20);
        $resolutions = SaccoBoardResolution::where('status', 'approved')->orderByDesc('meeting_date')->get();
        return theme_view('sacco::governance.regulatory_approvals', compact('approvals', 'resolutions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'approval_type' => 'required|in:branch_open,branch_close,agency_open,relocation,business_closure,land_purchase,insider_lending',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'board_resolution_id' => 'nullable|integer',
            'requested_date' => 'required|date',
        ]);
        $data['created_by_id'] = Auth::id();
        $data['status'] = 'pending';
        $approval = SaccoRegulatoryApproval::create($data);
        (new ComplianceAuditService())->log('create', 'governance', 'regulatory_approval', $approval->id, 'Regulatory approval requested');
        return redirect('sacco/governance/regulatory-approvals')->with('success', 'Approval request saved.');
    }

    public function approve($id)
    {
        $approval = SaccoRegulatoryApproval::findOrFail($id);
        $approval->status = 'approved';
        $approval->approval_date = date('Y-m-d');
        $approval->save();
        (new ComplianceAuditService())->log('approve', 'governance', 'regulatory_approval', $approval->id, 'Regulatory approval granted');
        return redirect('sacco/governance/regulatory-approvals')->with('success', 'Approval granted.');
    }
}
