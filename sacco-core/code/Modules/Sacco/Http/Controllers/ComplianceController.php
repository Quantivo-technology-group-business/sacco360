<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sacco\Services\ComplianceAuditService;
use Modules\Sacco\Services\PrudentialRatioService;

class ComplianceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.compliance.dashboard'])->only(['index']);
        $this->middleware(['permission:sacco.compliance.ratios'])->only(['ratios']);
    }

    public function index(Request $request)
    {
        $asOfDate = $request->get('as_of', date('Y-m-d'));
        $ratios = (new PrudentialRatioService())->calculateAll($asOfDate);
        (new ComplianceAuditService())->log('view', 'compliance', 'dashboard', null, 'Viewed compliance dashboard');
        return theme_view('sacco::compliance.index', compact('ratios', 'asOfDate'));
    }

    public function ratios(Request $request)
    {
        $asOfDate = $request->get('as_of', date('Y-m-d'));
        return response()->json((new PrudentialRatioService())->calculateAll($asOfDate));
    }
}
