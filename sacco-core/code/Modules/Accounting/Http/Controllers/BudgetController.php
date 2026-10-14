<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Entities\AccountBudget;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Branch\Entities\Branch;

class BudgetController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','2fa']);
        $this->middleware(['permission:accounting.budget.index'])->only(['index']);
        $this->middleware(['permission:accounting.budget.create'])->only(['create','store']);
        $this->middleware(['permission:accounting.budget.edit'])->only(['edit','update']);
        $this->middleware(['permission:accounting.budget.destroy'])->only(['destroy']);
        $this->middleware(['permission:accounting.budget.index'])->only(['variance']);
    }

    /** List all budget entries */
    public function index(Request $request)
    {
        $year      = $request->year ?? date('Y');
        $branch_id = $request->branch_id;
        $branches  = Branch::all();

        $budgets = AccountBudget::with('chart_of_account','branch')
            ->where('year', $year)
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->orderBy('year')->orderBy('month')
            ->paginate(50)->appends($request->input());

        $years = range(date('Y') - 3, date('Y') + 2);
        return theme_view('accounting::budget.index', compact('budgets','year','branch_id','branches','years'));
    }

    /** Create budget form */
    public function create()
    {
        $chart_of_accounts = ChartOfAccount::where('active',1)->orderBy('gl_code')->get();
        $branches          = Branch::all();
        $years             = range(date('Y') - 1, date('Y') + 3);
        $months            = [
            1=>'January',2=>'February',3=>'March',4=>'April',
            5=>'May',6=>'June',7=>'July',8=>'August',
            9=>'September',10=>'October',11=>'November',12=>'December',
        ];
        return theme_view('accounting::budget.create', compact('chart_of_accounts','branches','years','months'));
    }

    /** Store budget entry */
    public function store(Request $request)
    {
        $request->validate([
            'chart_of_account_id' => ['required','exists:chart_of_accounts,id'],
            'year'                => ['required','integer','min:2000','max:2100'],
            'amount'              => ['required','numeric','min:0'],
        ]);

        // If month is empty, treat as annual (null)
        $month = $request->month ?: null;

        // Upsert: if same account/year/month/branch exists, update amount
        AccountBudget::updateOrCreate(
            [
                'chart_of_account_id' => $request->chart_of_account_id,
                'year'                => $request->year,
                'month'               => $month,
                'branch_id'           => $request->branch_id ?: null,
            ],
            [
                'amount'         => $request->amount,
                'notes'          => $request->notes,
                'created_by_id'  => Auth::id(),
            ]
        );

        activity()->log('Create/Update Budget for account '.$request->chart_of_account_id);
        \flash('Budget saved successfully.')->success()->important();
        return redirect('accounting/budget');
    }

    /** Edit budget */
    public function edit($id)
    {
        $budget            = AccountBudget::findOrFail($id);
        $chart_of_accounts = ChartOfAccount::where('active',1)->orderBy('gl_code')->get();
        $branches          = Branch::all();
        $years             = range(date('Y') - 1, date('Y') + 3);
        $months            = [
            1=>'January',2=>'February',3=>'March',4=>'April',
            5=>'May',6=>'June',7=>'July',8=>'August',
            9=>'September',10=>'October',11=>'November',12=>'December',
        ];
        return theme_view('accounting::budget.edit', compact('budget','chart_of_accounts','branches','years','months'));
    }

    /** Update budget */
    public function update(Request $request, $id)
    {
        $request->validate([
            'chart_of_account_id' => ['required','exists:chart_of_accounts,id'],
            'year'                => ['required','integer','min:2000','max:2100'],
            'amount'              => ['required','numeric','min:0'],
        ]);
        $budget = AccountBudget::findOrFail($id);
        $budget->update([
            'chart_of_account_id' => $request->chart_of_account_id,
            'year'                => $request->year,
            'month'               => $request->month ?: null,
            'branch_id'           => $request->branch_id ?: null,
            'amount'              => $request->amount,
            'notes'               => $request->notes,
        ]);
        activity()->log('Update Budget '.$id);
        \flash('Budget updated successfully.')->success()->important();
        return redirect('accounting/budget');
    }

    /** Delete budget */
    public function destroy($id)
    {
        $budget = AccountBudget::findOrFail($id);
        $budget->delete();
        activity()->log('Delete Budget '.$id);
        \flash('Budget deleted.')->success()->important();
        return redirect()->back();
    }

    /**
     * Budget vs Actual variance report.
     * Shows budgeted amount vs actual journal entry totals per account for a given year/month.
     */
    public function variance(Request $request)
    {
        $year      = $request->year ?? date('Y');
        $month     = $request->month;  // null = full year
        $branch_id = $request->branch_id;
        $branches  = Branch::all();

        // Build date range
        if ($month) {
            $start_date = "{$year}-".str_pad($month,2,'0',STR_PAD_LEFT)."-01";
            $end_date   = date('Y-m-t', strtotime($start_date));
        } else {
            $start_date = "{$year}-01-01";
            $end_date   = "{$year}-12-31";
        }

        // Actual totals
        $actuals = DB::table('chart_of_accounts')
            ->leftJoin('journal_entries', function ($join) use ($start_date, $end_date, $branch_id) {
                $join->on('journal_entries.chart_of_account_id', '=', 'chart_of_accounts.id')
                     ->whereBetween('journal_entries.date', [$start_date, $end_date])
                     ->where('journal_entries.reversed', 0);
                if ($branch_id) $join->where('journal_entries.branch_id', $branch_id);
            })
            ->where('chart_of_accounts.active', 1)
            ->selectRaw("chart_of_accounts.id,chart_of_accounts.name,chart_of_accounts.gl_code,
                chart_of_accounts.account_type,
                COALESCE(SUM(journal_entries.debit),0)  AS actual_debit,
                COALESCE(SUM(journal_entries.credit),0) AS actual_credit")
            ->groupBy('chart_of_accounts.id')
            ->orderBy('chart_of_accounts.account_type')
            ->orderBy('chart_of_accounts.gl_code')
            ->get();

        // Budget totals
        $budgetQuery = AccountBudget::where('year', $year)
            ->when($month, fn($q) => $q->where('month', $month))
            ->when(!$month, fn($q) => $q->whereNull('month')->orWhere(fn($q2) => $q2->where('year',$year)))
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id));

        $budgetMap = $budgetQuery->get()->keyBy('chart_of_account_id');

        // Merge
        $data = $actuals->map(function ($row) use ($budgetMap) {
            $budgeted = $budgetMap->get($row->id)?->amount ?? 0;
            if (in_array($row->account_type, ['income','liability','equity'])) {
                $actual = $row->actual_credit - $row->actual_debit;
            } else {
                $actual = $row->actual_debit - $row->actual_credit;
            }
            $row->budgeted  = $budgeted;
            $row->actual    = $actual;
            $row->variance  = $actual - $budgeted;
            $row->pct       = $budgeted != 0 ? round(($actual / $budgeted) * 100, 1) : null;
            return $row;
        });

        $years  = range(date('Y') - 3, date('Y') + 2);
        $months = [
            1=>'January',2=>'February',3=>'March',4=>'April',
            5=>'May',6=>'June',7=>'July',8=>'August',
            9=>'September',10=>'October',11=>'November',12=>'December',
        ];

        return theme_view('accounting::budget.variance',
            compact('data','year','month','branch_id','branches','start_date','end_date','years','months'));
    }
}
