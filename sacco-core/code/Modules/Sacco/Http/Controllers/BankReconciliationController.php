<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Branch\Entities\Branch;
use Modules\Sacco\Entities\SaccoBankReconciliation;
use Modules\Sacco\Services\BankReconciliationService;

class BankReconciliationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.reconciliation.index'])->only(['index', 'show', 'autoMatch']);
        $this->middleware(['permission:sacco.reconciliation.create'])->only(['create', 'store']);
    }

    public function index()
    {
        $reconciliations = SaccoBankReconciliation::with('chart_of_account')->orderByDesc('id')->paginate(20);
        return theme_view('sacco::reconciliation.index', compact('reconciliations'));
    }

    public function create()
    {
        $bankAccounts = ChartOfAccount::where('account_type', 'asset')->where('active', 1)->get();
        $branches = Branch::all();
        return theme_view('sacco::reconciliation.create', compact('bankAccounts', 'branches'));
    }

    public function store(Request $request, BankReconciliationService $service)
    {
        $request->validate([
            'chart_of_account_id' => 'required|integer',
            'statement_date' => 'required|date',
            'statement_balance' => 'required|numeric',
        ]);

        $reconciliation = $service->create($request->only([
            'chart_of_account_id', 'branch_id', 'statement_date', 'statement_balance', 'notes',
        ]));

        \flash('Bank reconciliation started')->success()->important();
        return redirect('sacco/reconciliation/' . $reconciliation->id . '/show');
    }

    public function show($id)
    {
        $reconciliation = SaccoBankReconciliation::with(['items', 'chart_of_account'])->findOrFail($id);
        return theme_view('sacco::reconciliation.show', compact('reconciliation'));
    }

    public function autoMatch($id, BankReconciliationService $service)
    {
        $reconciliation = SaccoBankReconciliation::findOrFail($id);
        $result = $service->autoMatch($reconciliation);
        \flash("Auto-matched {$result['matched']} items. Status: {$result['status']}")->success()->important();
        return redirect('sacco/reconciliation/' . $id . '/show');
    }
}
