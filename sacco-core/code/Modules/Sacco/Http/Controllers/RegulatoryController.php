<?php

namespace Modules\Sacco\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Sacco\Exports\RegulatoryExport;
use Modules\Sacco\Services\ComplianceAuditService;
use Modules\Sacco\Services\SasraReportingService;

class RegulatoryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.regulatory.index'])->only(['index']);
        $this->middleware(['permission:sacco.regulatory.export'])->only(['export']);
    }

    public function index(Request $request)
    {
        $asOfDate = $request->get('as_of', date('Y-m-d'));
        $reports = (new SasraReportingService())->generateAll($asOfDate);
        (new ComplianceAuditService())->log('view', 'regulatory', 'reports', null, 'Viewed SASRA reports', ['as_of' => $asOfDate]);
        return theme_view('sacco::regulatory.index', compact('reports', 'asOfDate'));
    }

    public function export(Request $request)
    {
        $asOfDate = $request->get('as_of', date('Y-m-d'));
        $format = $request->get('format', 'json');
        $service = new SasraReportingService();
        $reports = $service->generateAll($asOfDate);

        (new ComplianceAuditService())->log('export', 'regulatory', 'reports', null, 'Exported SASRA reports', ['as_of' => $asOfDate, 'format' => $format]);

        if ($format === 'pdf') {
            $pdf = Pdf::loadView(theme_view_file('sacco::regulatory.export_pdf'), compact('reports', 'asOfDate'));
            return $pdf->download('sasra-reports-' . $asOfDate . '.pdf');
        }

        if ($format === 'excel') {
            $view = theme_view('sacco::regulatory.export_excel', compact('reports', 'asOfDate'));
            return Excel::download(new RegulatoryExport($view), 'sasra-reports-' . $asOfDate . '.xlsx');
        }

        $form = $request->get('form', 'all');
        $data = $form === 'all' ? $reports : [$form => $reports[$form] ?? []];
        return response()->json($data);
    }
}
