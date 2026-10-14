<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Sacco\Entities\ComplianceAuditLog;
use Modules\Sacco\Services\ComplianceAuditService;

class ComplianceAuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.compliance.dashboard']);
    }

    public function index()
    {
        $logs = ComplianceAuditLog::orderByDesc('id')->paginate(50);
        (new ComplianceAuditService())->log('view', 'compliance', 'audit_log', null, 'Viewed compliance audit log');
        return theme_view('sacco::compliance.audit_log', compact('logs'));
    }
}
