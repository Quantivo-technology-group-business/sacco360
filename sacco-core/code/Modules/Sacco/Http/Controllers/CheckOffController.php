<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoCheckoffBatch;
use Modules\Sacco\Entities\SaccoCheckoffItem;
use Modules\ActivityLog\Services\AuditLogger;
use Modules\Sacco\Services\CheckOffService;

class CheckOffController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.checkoff.index'])->only(['index', 'show']);
        $this->middleware(['permission:sacco.checkoff.create'])->only(['create', 'store', 'post', 'refund']);
    }

    public function index()
    {
        $batches = SaccoCheckoffBatch::orderByDesc('id')->paginate(20);
        return theme_view('sacco::checkoff.index', compact('batches'));
    }

    public function create()
    {
        return theme_view('sacco::checkoff.create');
    }

    public function store(Request $request, CheckOffService $service)
    {
        $request->validate([
            'employer_name' => 'required',
            'period' => 'required',
            'file' => 'required|file|mimes:csv,txt,xlsx,xls',
        ]);

        $batch = new SaccoCheckoffBatch();
        $batch->created_by_id = Auth::id();
        $batch->employer_name = $request->employer_name;
        $batch->period = $request->period;
        $batch->upload_date = date('Y-m-d');
        $batch->filename = $request->file('file')->getClientOriginalName();
        $batch->save();

        $path = $request->file('file')->getRealPath();
        $rows = $service->parseCsv($path);
        $service->processUpload($batch, $rows);

        AuditLogger::log('Checkoff Processed', $batch, ['id' => $batch->id, 'employer_name' => $batch->employer_name, 'period' => $batch->period, 'filename' => $batch->filename], 'sacco', 'create');
        \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();
        return redirect('sacco/checkoff/' . $batch->id . '/show');
    }

    public function show($id)
    {
        $batch = SaccoCheckoffBatch::with('items')->findOrFail($id);
        return theme_view('sacco::checkoff.show', compact('batch'));
    }

    public function post($id, CheckOffService $service)
    {
        $batch = SaccoCheckoffBatch::findOrFail($id);
        $service->postBatch($batch);
        \flash('Check-off payments posted')->success()->important();
        return redirect('sacco/checkoff/' . $id . '/show');
    }

    public function refund($itemId, CheckOffService $service)
    {
        $item = SaccoCheckoffItem::findOrFail($itemId);
        $service->refundItem($item);
        \flash('Item marked for refund')->success()->important();
        return back();
    }

    public function syncPayroll(Request $request, CheckOffService $service)
    {
        $request->validate([
            'payroll_id' => 'required|integer',
            'employer_name' => 'required',
            'period' => 'required',
        ]);

        $batch = $service->syncFromPayroll(
            (int)$request->payroll_id,
            $request->employer_name,
            $request->period
        );

        \flash('Payroll deductions imported')->success()->important();
        return redirect('sacco/checkoff/' . $batch->id . '/show');
    }
}
