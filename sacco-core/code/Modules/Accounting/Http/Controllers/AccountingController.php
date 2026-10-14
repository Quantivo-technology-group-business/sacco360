<?php

namespace Modules\Accounting\Http\Controllers;

use Maatwebsite\Excel\Facades\Excel;
use Modules\Accounting\Exports\AccountingExport;
use PDF;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Branch\Entities\Branch;

class AccountingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(['permission:accounting.reports.trial_balance'])->only(['trial_balance']);
        $this->middleware(['permission:accounting.reports.balance_sheet'])->only(['balance_sheet']);
        $this->middleware(['permission:accounting.reports.income_statement'])->only(['income_statement']);
        $this->middleware(['permission:accounting.reports.ledger'])->only(['ledger']);
        $this->middleware(['permission:accounting.reports.cash_flow'])->only(['cash_flow']);
    }

    // ─── TRIAL BALANCE ────────────────────────────────────────────────────────

    public function trial_balance(Request $request)
    {
        $start_date = $request->start_date;
        $end_date   = $request->end_date;
        $branch_id  = $request->branch_id;
        $branches   = Branch::all();
        $data       = [];

        if (!empty($start_date)) {
            $data = DB::table('chart_of_accounts')
                ->leftJoin('journal_entries', function ($join) use ($start_date, $end_date, $branch_id) {
                    $join->on('journal_entries.chart_of_account_id', '=', 'chart_of_accounts.id')
                         ->whereBetween('journal_entries.date', [$start_date, $end_date])
                         ->where('journal_entries.reversed', 0);
                    if ($branch_id) $join->where('journal_entries.branch_id', $branch_id);
                })
                ->where('chart_of_accounts.active', 1)
                ->selectRaw("chart_of_accounts.id,chart_of_accounts.name,chart_of_accounts.gl_code,
                    chart_of_accounts.account_type,chart_of_accounts.opening_debit,chart_of_accounts.opening_credit,
                    COALESCE(SUM(journal_entries.debit),0)  AS period_debit,
                    COALESCE(SUM(journal_entries.credit),0) AS period_credit")
                ->groupBy('chart_of_accounts.id')
                ->orderBy('chart_of_accounts.gl_code')
                ->get();

            if ($request->download) {
                if ($request->type == 'pdf') {
                    $pdf = PDF::loadView(theme_view_file('accounting::report.trial_balance_pdf'),
                        compact('start_date','end_date','branch_id','data','branches'));
                    return $pdf->download('TrialBalance('.$start_date.' to '.$end_date.').pdf');
                }
                $view = theme_view('accounting::report.trial_balance_pdf', compact('start_date','end_date','branch_id','data','branches'));
                if ($request->type == 'excel_2007') return Excel::download(new AccountingExport($view), 'TrialBalance.xlsx');
                if ($request->type == 'excel')      return Excel::download(new AccountingExport($view), 'TrialBalance.xls');
                if ($request->type == 'csv')        return Excel::download(new AccountingExport($view), 'TrialBalance.csv');
            }
        }
        return theme_view('accounting::report.trial_balance', compact('start_date','end_date','branch_id','data','branches'));
    }

    // ─── INCOME STATEMENT ─────────────────────────────────────────────────────

    public function income_statement(Request $request)
    {
        $start_date = $request->start_date;
        $end_date   = $request->end_date;
        $branch_id  = $request->branch_id;
        $branches   = Branch::all();
        $data       = [];

        if (!empty($start_date)) {
            $data = DB::table('chart_of_accounts')
                ->leftJoin('journal_entries', function ($join) use ($start_date, $end_date, $branch_id) {
                    $join->on('journal_entries.chart_of_account_id', '=', 'chart_of_accounts.id')
                         ->whereBetween('journal_entries.date', [$start_date, $end_date])
                         ->where('journal_entries.reversed', 0);
                    if ($branch_id) $join->where('journal_entries.branch_id', $branch_id);
                })
                ->where('chart_of_accounts.active', 1)
                ->whereIn('chart_of_accounts.account_type', ['income', 'expense'])
                ->selectRaw("chart_of_accounts.id,chart_of_accounts.name,chart_of_accounts.gl_code,
                    chart_of_accounts.account_type,
                    COALESCE(SUM(journal_entries.debit),0)  AS debit,
                    COALESCE(SUM(journal_entries.credit),0) AS credit")
                ->groupBy('chart_of_accounts.id')
                ->orderBy('chart_of_accounts.account_type')
                ->orderBy('chart_of_accounts.gl_code')
                ->get();

            if ($request->download) {
                if ($request->type == 'pdf') {
                    $pdf = PDF::loadView(theme_view_file('accounting::report.income_statement_pdf'),
                        compact('start_date','end_date','branch_id','data','branches'));
                    return $pdf->download('IncomeStatement('.$start_date.' to '.$end_date.').pdf');
                }
                $view = theme_view('accounting::report.income_statement_pdf', compact('start_date','end_date','branch_id','data','branches'));
                if ($request->type == 'excel_2007') return Excel::download(new AccountingExport($view), 'IncomeStatement.xlsx');
                if ($request->type == 'excel')      return Excel::download(new AccountingExport($view), 'IncomeStatement.xls');
                if ($request->type == 'csv')        return Excel::download(new AccountingExport($view), 'IncomeStatement.csv');
            }
        }
        return theme_view('accounting::report.income_statement', compact('start_date','end_date','branch_id','data','branches'));
    }

    // ─── BALANCE SHEET ────────────────────────────────────────────────────────

    public function balance_sheet(Request $request)
    {
        $end_date  = $request->end_date;
        $branch_id = $request->branch_id;
        $branches  = Branch::all();
        $data      = [];

        if (!empty($end_date)) {
            $data = DB::table('chart_of_accounts')
                ->leftJoin('journal_entries', function ($join) use ($end_date, $branch_id) {
                    $join->on('journal_entries.chart_of_account_id', '=', 'chart_of_accounts.id')
                         ->where('journal_entries.date', '<=', $end_date)
                         ->where('journal_entries.reversed', 0);
                    if ($branch_id) $join->where('journal_entries.branch_id', $branch_id);
                })
                ->where('chart_of_accounts.active', 1)
                ->whereIn('chart_of_accounts.account_type', ['asset','equity','liability'])
                ->selectRaw("chart_of_accounts.id,chart_of_accounts.name,chart_of_accounts.gl_code,
                    chart_of_accounts.account_type,chart_of_accounts.opening_debit,chart_of_accounts.opening_credit,
                    COALESCE(SUM(journal_entries.debit),0)  AS debit,
                    COALESCE(SUM(journal_entries.credit),0) AS credit")
                ->groupBy('chart_of_accounts.id')
                ->orderBy('chart_of_accounts.account_type')
                ->orderBy('chart_of_accounts.gl_code')
                ->get();

            if ($request->download) {
                if ($request->type == 'pdf') {
                    $pdf = PDF::loadView(theme_view_file('accounting::report.balance_sheet_pdf'),
                        compact('end_date','branch_id','data','branches'));
                    return $pdf->download('BalanceSheet('.$end_date.').pdf');
                }
                $view = theme_view('accounting::report.balance_sheet_pdf', compact('end_date','branch_id','data','branches'));
                if ($request->type == 'excel_2007') return Excel::download(new AccountingExport($view), 'BalanceSheet.xlsx');
                if ($request->type == 'excel')      return Excel::download(new AccountingExport($view), 'BalanceSheet.xls');
                if ($request->type == 'csv')        return Excel::download(new AccountingExport($view), 'BalanceSheet.csv');
            }
        }
        return theme_view('accounting::report.balance_sheet', compact('end_date','branch_id','data','branches'));
    }

    // ─── GENERAL LEDGER ───────────────────────────────────────────────────────

    public function ledger(Request $request)
    {
        $start_date          = $request->start_date;
        $end_date            = $request->end_date;
        $branch_id           = $request->branch_id;
        $chart_of_account_id = $request->chart_of_account_id;
        $branches            = Branch::all();

        $accounts = DB::table('chart_of_accounts')
            ->where('active', 1)->orderBy('gl_code')->get();

        $data = [];

        if (!empty($start_date) && !empty($end_date)) {
            $query = DB::table('chart_of_accounts')
                ->leftJoin('journal_entries', function ($join) use ($start_date, $end_date, $branch_id) {
                    $join->on('journal_entries.chart_of_account_id', '=', 'chart_of_accounts.id')
                         ->whereBetween('journal_entries.date', [$start_date, $end_date])
                         ->where('journal_entries.reversed', 0);
                    if ($branch_id) $join->where('journal_entries.branch_id', $branch_id);
                })
                ->where('chart_of_accounts.active', 1)
                ->when($chart_of_account_id, fn($q) => $q->where('chart_of_accounts.id', $chart_of_account_id))
                ->selectRaw("chart_of_accounts.id,chart_of_accounts.name,chart_of_accounts.gl_code,
                    chart_of_accounts.account_type,chart_of_accounts.opening_debit,chart_of_accounts.opening_credit,
                    COALESCE(SUM(journal_entries.debit),0)  AS debit,
                    COALESCE(SUM(journal_entries.credit),0) AS credit")
                ->groupBy('chart_of_accounts.id')
                ->orderBy('chart_of_accounts.account_type')
                ->orderBy('chart_of_accounts.gl_code')
                ->get();

            $data = $query;

            if ($request->download) {
                if ($request->type == 'pdf') {
                    $pdf = PDF::loadView(theme_view_file('accounting::report.ledger_pdf'),
                        compact('start_date','end_date','branch_id','data','branches'));
                    return $pdf->download('Ledger.pdf');
                }
                $view = theme_view('accounting::report.ledger_pdf', compact('start_date','end_date','branch_id','data','branches'));
                if ($request->type == 'excel_2007') return Excel::download(new AccountingExport($view), 'Ledger.xlsx');
                if ($request->type == 'excel')      return Excel::download(new AccountingExport($view), 'Ledger.xls');
                if ($request->type == 'csv')        return Excel::download(new AccountingExport($view), 'Ledger.csv');
            }
        }
        return theme_view('accounting::report.ledger', compact('start_date','end_date','branch_id','chart_of_account_id','data','branches','accounts'));
    }

    // ─── CASH FLOW STATEMENT ──────────────────────────────────────────────────
    // Direct method: groups net flows by cash_flow_category set on each GL account.
    // Operating / Investing / Financing — accounts with category='none' are excluded.

    public function cash_flow(Request $request)
    {
        $start_date = $request->start_date;
        $end_date   = $request->end_date;
        $branch_id  = $request->branch_id;
        $branches   = Branch::all();
        $data       = collect();
        $summary    = [];

        if (!empty($start_date)) {
            $rows = DB::table('chart_of_accounts')
                ->leftJoin('journal_entries', function ($join) use ($start_date, $end_date, $branch_id) {
                    $join->on('journal_entries.chart_of_account_id', '=', 'chart_of_accounts.id')
                         ->whereBetween('journal_entries.date', [$start_date, $end_date])
                         ->where('journal_entries.reversed', 0);
                    if ($branch_id) $join->where('journal_entries.branch_id', $branch_id);
                })
                ->where('chart_of_accounts.active', 1)
                ->where('chart_of_accounts.cash_flow_category', '!=', 'none')
                ->selectRaw("chart_of_accounts.id,chart_of_accounts.name,chart_of_accounts.gl_code,
                    chart_of_accounts.account_type,chart_of_accounts.cash_flow_category,
                    COALESCE(SUM(journal_entries.debit),0)  AS debit,
                    COALESCE(SUM(journal_entries.credit),0) AS credit")
                ->groupBy('chart_of_accounts.id')
                ->orderBy('chart_of_accounts.cash_flow_category')
                ->orderBy('chart_of_accounts.gl_code')
                ->get();

            $data = $rows;

            // Net flow per account: cash inflow = credit for income/liability, debit for asset/expense
            // Simplified: net = credit - debit for inflow-type accounts
            $categories = ['operating', 'investing', 'financing'];
            foreach ($categories as $cat) {
                $catRows = $rows->where('cash_flow_category', $cat);
                $net = 0;
                foreach ($catRows as $r) {
                    if (in_array($r->account_type, ['income','liability','equity'])) {
                        $net += ($r->credit - $r->debit);
                    } else {
                        $net += ($r->debit - $r->credit);
                    }
                }
                $summary[$cat] = $net;
            }
            $summary['total'] = array_sum($summary);

            if ($request->download) {
                if ($request->type == 'pdf') {
                    $pdf = PDF::loadView(theme_view_file('accounting::report.cash_flow_pdf'),
                        compact('start_date','end_date','branch_id','data','branches','summary'));
                    return $pdf->download('CashFlow('.$start_date.' to '.$end_date.').pdf');
                }
                $view = theme_view('accounting::report.cash_flow_pdf', compact('start_date','end_date','branch_id','data','branches','summary'));
                if ($request->type == 'excel_2007') return Excel::download(new AccountingExport($view), 'CashFlow.xlsx');
                if ($request->type == 'excel')      return Excel::download(new AccountingExport($view), 'CashFlow.xls');
                if ($request->type == 'csv')        return Excel::download(new AccountingExport($view), 'CashFlow.csv');
            }
        }
        return theme_view('accounting::report.cash_flow', compact('start_date','end_date','branch_id','data','branches','summary'));
    }
}
