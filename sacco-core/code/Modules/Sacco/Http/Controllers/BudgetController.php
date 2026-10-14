<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoBudgetPeriod;
use Modules\Sacco\Entities\SaccoBudgetLine;
use Modules\Sacco\Services\BudgetService;
use Modules\Accounting\Entities\ChartOfAccount;

class BudgetController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware('permission:sacco.budget.index')->only(['index', 'show', 'budgetVsActual']);
        $this->middleware('permission:sacco.budget.create')->only(['create', 'store', 'storeLine', 'updateLine', 'copyPeriod']);
        $this->middleware('permission:sacco.budget.approve')->only(['approve', 'activate']);
    }

    public function index()
    {
        $periods = SaccoBudgetPeriod::orderByDesc('fiscal_year')->paginate(20);
        return theme_view('sacco::budget.index', compact('periods'));
    }

    public function create()
    {
        return theme_view('sacco::budget.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:150',
            'fiscal_year' => 'required|integer|min:2000|max:2100',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after:start_date',
        ]);

        $period = SaccoBudgetPeriod::create(array_merge(
            $request->only(['name', 'fiscal_year', 'start_date', 'end_date', 'notes']),
            ['status' => 'draft', 'created_by_id' => Auth::id()]
        ));

        \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();
        return redirect('sacco/budget/' . $period->id . '/show');
    }

    public function show($id)
    {
        $period   = SaccoBudgetPeriod::with('lines.account')->findOrFail($id);
        $accounts = ChartOfAccount::orderBy('account_code')->get();
        return theme_view('sacco::budget.show', compact('period', 'accounts'));
    }

    public function storeLine(Request $request, $id)
    {
        $request->validate([
            'chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'budget_amount'        => 'required|numeric|min:0',
        ]);

        $period = SaccoBudgetPeriod::findOrFail($id);
        if ($period->status === 'closed') {
            \flash('Cannot edit a closed budget period.')->error()->important();
            return back();
        }

        SaccoBudgetLine::updateOrCreate(
            ['sacco_budget_period_id' => $period->id, 'chart_of_account_id' => $request->chart_of_account_id],
            array_merge(
                $request->only(['budget_amount', 'q1_amount', 'q2_amount', 'q3_amount', 'q4_amount', 'notes']),
                ['created_by_id' => Auth::id()]
            )
        );

        \flash('Budget line saved.')->success()->important();
        return redirect('sacco/budget/' . $id . '/show');
    }

    public function budgetVsActual($id, BudgetService $service)
    {
        $period  = SaccoBudgetPeriod::findOrFail($id);
        $results = $service->getBudgetVsActual($period);
        return theme_view('sacco::budget.vs_actual', compact('period', 'results'));
    }

    public function approve($id, BudgetService $service)
    {
        $period = SaccoBudgetPeriod::findOrFail($id);
        $service->approve($period, Auth::id());
        \flash('Budget approved.')->success()->important();
        return redirect('sacco/budget/' . $id . '/show');
    }

    public function activate($id, BudgetService $service)
    {
        $period = SaccoBudgetPeriod::findOrFail($id);
        if ($period->status !== 'approved') {
            \flash('Budget must be approved before activation.')->error()->important();
            return back();
        }
        $service->activate($period);
        \flash('Budget period activated.')->success()->important();
        return redirect('sacco/budget');
    }

    public function copyPeriod(Request $request, $id, BudgetService $service)
    {
        $request->validate(['target_period_id' => 'required|exists:sacco_budget_periods,id']);
        $source = SaccoBudgetPeriod::with('lines')->findOrFail($id);
        $target = SaccoBudgetPeriod::findOrFail($request->target_period_id);
        $copied = $service->copyFromPeriod($source, $target, Auth::id());
        \flash("{$copied} budget lines copied.")->success()->important();
        return redirect('sacco/budget/' . $target->id . '/show');
    }
}
