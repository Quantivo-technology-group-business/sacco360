<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoAmlAlert;
use Modules\Sacco\Services\AmlMonitoringService;

class AmlController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.aml.index']);
    }

    public function index()
    {
        $alerts = SaccoAmlAlert::orderByDesc('id')->paginate(20);
        return theme_view('sacco::aml.index', compact('alerts'));
    }

    public function runChecks(AmlMonitoringService $service)
    {
        $count = $service->runDailyChecks();
        \flash("AML scan complete: {$count} new alerts")->success()->important();
        return redirect('sacco/aml');
    }

    public function review(Request $request, $id, AmlMonitoringService $service)
    {
        $alert = SaccoAmlAlert::findOrFail($id);
        if ($request->sar_status) {
            $service->updateSarStatus($alert->id, $request->sar_status);
        } else {
            $alert->status = $request->status ?? 'closed';
            $alert->reviewed_by_id = Auth::id();
            $alert->reviewed_at = now();
            $alert->save();
        }
        if (function_exists('sacco_compliance_log')) {
            sacco_compliance_log('aml_review', 'aml', 'alert', $alert->id, 'AML alert reviewed');
        }
        return back();
    }
}
