<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoAuditorAppointment;
use Modules\Sacco\Entities\SaccoBoardResolution;
use Modules\Sacco\Entities\SaccoComplianceCalendar;
use Modules\Sacco\Entities\SaccoFitProperRecord;
use Modules\Sacco\Entities\SaccoInspectionAction;
use Modules\Sacco\Entities\SaccoLicenseRenewal;
use Modules\Sacco\Services\ComplianceAuditService;
use Modules\Sacco\Services\GovernanceService;

class GovernanceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.governance.licenses'])->only(['licenses', 'storeLicense']);
        $this->middleware(['permission:sacco.governance.resolutions'])->only(['resolutions', 'storeResolution', 'fitProper', 'storeFitProper', 'auditors', 'storeAuditor']);
        $this->middleware(['permission:sacco.governance.inspections'])->only(['inspections', 'storeInspectionAction', 'completeInspectionAction', 'calendar', 'markCalendarSubmitted']);
    }

    public function licenses()
    {
        $licenses = SaccoLicenseRenewal::orderByDesc('expiry_date')->get();
        return theme_view('sacco::governance.licenses', compact('licenses'));
    }

    public function storeLicense(Request $request)
    {
        $license = SaccoLicenseRenewal::create($request->only([
            'license_number', 'issue_date', 'expiry_date', 'status', 'notes',
        ]));
        (new ComplianceAuditService())->log('create', 'governance', 'license', $license->id, 'License record created');
        return redirect('sacco/governance/licenses')->with('success', 'License record saved.');
    }

    public function resolutions()
    {
        $resolutions = SaccoBoardResolution::orderByDesc('meeting_date')->get();
        return theme_view('sacco::governance.resolutions', compact('resolutions'));
    }

    public function storeResolution(Request $request)
    {
        $data = $request->only(['resolution_number', 'title', 'meeting_type', 'meeting_date', 'resolution_text', 'status']);
        $data['created_by_id'] = Auth::id();
        $resolution = SaccoBoardResolution::create($data);
        (new ComplianceAuditService())->log('create', 'governance', 'resolution', $resolution->id, 'Board resolution recorded');
        return redirect('sacco/governance/resolutions')->with('success', 'Resolution saved.');
    }

    public function inspections()
    {
        $actions = SaccoInspectionAction::orderBy('deadline')->get();
        return theme_view('sacco::governance.inspections', compact('actions'));
    }

    public function storeInspectionAction(Request $request)
    {
        $data = $request->only(['inspection_reference', 'inspection_date', 'action_point', 'deadline', 'assigned_to_id', 'status']);
        $data['created_by_id'] = Auth::id();
        $action = SaccoInspectionAction::create($data);
        (new ComplianceAuditService())->log('create', 'governance', 'inspection_action', $action->id, 'Inspection action point created');
        return redirect('sacco/governance/inspections')->with('success', 'Action point saved.');
    }

    public function completeInspectionAction($id)
    {
        $action = SaccoInspectionAction::findOrFail($id);
        $action->status = 'completed';
        $action->completed_date = date('Y-m-d');
        $action->save();
        return redirect('sacco/governance/inspections')->with('success', 'Action marked complete.');
    }

    public function calendar(Request $request)
    {
        $service = new GovernanceService();
        $service->generateComplianceCalendar((int) date('Y'), (int) date('m'));
        $entries = SaccoComplianceCalendar::orderBy('due_date')->get();
        return theme_view('sacco::governance.calendar', compact('entries'));
    }

    public function markCalendarSubmitted(Request $request, $id)
    {
        $entry = SaccoComplianceCalendar::findOrFail($id);
        $entry->status = 'submitted';
        $entry->submitted_date = date('Y-m-d');
        $entry->submitted_by_id = Auth::id();
        $entry->save();
        return redirect('sacco/governance/calendar')->with('success', 'Marked as submitted.');
    }

    public function fitProper()
    {
        $records = SaccoFitProperRecord::orderByDesc('appointment_date')->get();
        return theme_view('sacco::governance.fit_proper', compact('records'));
    }

    public function storeFitProper(Request $request)
    {
        SaccoFitProperRecord::create($request->only([
            'person_name', 'role', 'appointment_date', 'last_review_date', 'status', 'notes',
        ]));
        return redirect('sacco/governance/fit-proper')->with('success', 'Record saved.');
    }

    public function auditors()
    {
        $auditors = SaccoAuditorAppointment::orderByDesc('agm_date')->get();
        return theme_view('sacco::governance.auditors', compact('auditors'));
    }

    public function storeAuditor(Request $request)
    {
        $data = $request->only(['auditor_name', 'agm_date', 'appointment_date', 'notes']);
        $data['sasra_submission_deadline'] = date('Y-m-d', strtotime($data['agm_date'] . ' +30 days'));
        $data['status'] = 'appointed';
        SaccoAuditorAppointment::create($data);
        return redirect('sacco/governance/auditors')->with('success', 'Auditor appointment saved.');
    }

}
