<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Services\CaelsClassificationService;
use Modules\Sacco\Services\CaelsReportService;
use Modules\Sacco\Services\ComplianceAuditService;
use Modules\Sacco\Services\MakerCheckerService;

class ClassificationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.regulatory.caels'])->only(['index']);
        $this->middleware(['permission:sacco.classification.override'])->only(['override']);
    }

    public function index(Request $request)
    {
        $asOfDate = $request->get('as_of', date('Y-m-d'));
        $report = (new CaelsReportService())->generateComparativeReport($asOfDate);
        return theme_view('sacco::regulatory.caels', compact('report', 'asOfDate'));
    }

    public function override(Request $request)
    {
        $request->validate([
            'loan_id' => 'required|integer',
            'classification' => 'required|in:Performing,Watch,Substandard,Doubtful,Loss',
        ]);

        (new MakerCheckerService())->submitForApproval('classification_override', $request->loan_id, 0, $request->classification);

        (new CaelsClassificationService())->overrideClassification(
            $request->loan_id,
            $request->classification,
            Auth::id()
        );

        (new ComplianceAuditService())->log('override', 'caels', 'loan', $request->loan_id, 'Manual classification override to ' . $request->classification);

        return redirect()->back()->with('success', 'Classification updated.');
    }
}
