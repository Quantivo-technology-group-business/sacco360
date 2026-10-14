<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sacco\Entities\SaccoReportTemplate;

class ReportTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.report_templates.manage']);
    }

    public function index()
    {
        $templates = SaccoReportTemplate::orderBy('code')->get();
        return theme_view('sacco::regulatory.templates', compact('templates'));
    }

    public function store(Request $request)
    {
        SaccoReportTemplate::create($request->only([
            'code', 'name', 'form_type', 'frequency', 'field_definitions', 'query_binding', 'output_mapping', 'active',
        ]));
        return redirect('sacco/regulatory/templates')->with('success', 'Template created.');
    }
}
