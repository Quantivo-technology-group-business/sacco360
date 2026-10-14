<?php

namespace Modules\Payroll\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Branch\Entities\Branch;
use Modules\Core\Entities\Currency;
use Modules\Payroll\Entities\Payroll;
use Modules\Payroll\Entities\PayrollEmployee;
use Modules\Payroll\Entities\PayrollRun;
use Modules\Payroll\Services\PayrollCalculationService;

class PayrollRunController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:payroll.payroll.runs.index'])->only(['index', 'show']);
        $this->middleware(['permission:payroll.payroll.runs.create'])->only(['create', 'store', 'generate', 'approve']);
        $this->middleware(['permission:payroll.payroll.runs.destroy'])->only(['destroy']);
    }

    public function index(Request $request)
    {
        $data = PayrollRun::orderByDesc('id')
            ->paginate($request->per_page ?: 20)
            ->appends($request->input());

        return theme_view('payroll::run.index', compact('data'));
    }

    public function create()
    {
        $branches = Branch::get();
        $currencies = Currency::all();

        return theme_view('payroll::run.create', compact('branches', 'currencies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'period' => 'required',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'currency_id' => 'required',
        ]);

        $run = new PayrollRun();
        $run->fill($request->only(['name', 'period', 'period_start', 'period_end', 'branch_id', 'currency_id', 'description']));
        $run->created_by_id = Auth::id();
        $run->status = 'draft';
        $run->save();

        \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();

        return redirect('payroll/run/' . $run->id . '/show');
    }

    public function show($id)
    {
        $run = PayrollRun::with(['payrolls.user'])->findOrFail($id);

        return theme_view('payroll::run.show', compact('run'));
    }

    public function generate($id, PayrollCalculationService $service)
    {
        $run = PayrollRun::findOrFail($id);
        if ($run->status !== 'draft') {
            \flash('Only draft runs can be generated')->warning()->important();

            return redirect()->back();
        }

        $employees = PayrollEmployee::with('user', 'payroll_template.payroll_items.payroll_item')
            ->where('is_active', 1)
            ->when($run->branch_id, fn($q) => $q->where('branch_id', $run->branch_id))
            ->get();

        Payroll::where('payroll_run_id', $run->id)->delete();

        foreach ($employees as $employee) {
            if (!$employee->user) {
                continue;
            }
            $service->generatePayslipForEmployee($run, $employee, $employee->user);
        }

        $run->status = 'processing';
        $service->recalculateRunTotals($run);

        \flash('Payroll generated for ' . $employees->count() . ' employees')->success()->important();

        return redirect('payroll/run/' . $run->id . '/show');
    }

    public function approve($id, PayrollCalculationService $service)
    {
        $run = PayrollRun::findOrFail($id);

        if (function_exists('sacco_requires_maker_checker_for') && sacco_requires_maker_checker_for(0, 'payroll_approval')) {
            if (function_exists('sacco_queue_approval')) {
                $totalGross = Payroll::where('payroll_run_id', $run->id)->sum('gross_pay');
                sacco_queue_approval(
                    'payroll_approval',
                    $run->id,
                    (float)$totalGross,
                    'Payroll run approval pending checker',
                    'Payroll Run Approval — ' . $run->name . ' (' . number_format($totalGross, 2) . ')'
                );
                \flash('Payroll approval submitted for checker review.')->warning()->important();
                return redirect('payroll/run/' . $run->id . '/show');
            }
        }

        $run->status = 'approved';
        $run->approved_by_id = Auth::id();
        $run->approved_at = now();
        $run->save();

        Payroll::where('payroll_run_id', $run->id)->update(['status' => 'approved']);
        $service->recalculateRunTotals($run);

        \flash('Payroll run approved')->success()->important();

        return redirect('payroll/run/' . $run->id . '/show');
    }

    public function destroy($id)
    {
        $run = PayrollRun::findOrFail($id);
        if ($run->status === 'approved') {
            \flash('Cannot delete an approved payroll run')->warning()->important();

            return redirect()->back();
        }

        Payroll::where('payroll_run_id', $run->id)->delete();
        $run->delete();

        \flash(trans_choice('core::general.successfully_deleted', 1))->success()->important();

        return redirect('payroll/run');
    }
}
