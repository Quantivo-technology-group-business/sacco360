<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Accounting\Exports\AccountingExport;
use Modules\Branch\Entities\Branch;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class AccountStatementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','2fa']);
        $this->middleware(['permission:accounting.chart_of_accounts.index']);
    }

    /**
     * Per-account transaction ledger (account statement).
     * Shows opening balance + each individual journal entry line + running balance.
     */
    public function show(Request $request, $id)
    {
        $account    = ChartOfAccount::findOrFail($id);
        $start_date = $request->start_date;
        $end_date   = $request->end_date;
        $branch_id  = $request->branch_id;
        $branches   = Branch::all();

        // Opening balance = sum of all entries BEFORE start_date + account's own opening_debit/credit
        $openingDr = (float)($account->opening_debit  ?? 0);
        $openingCr = (float)($account->opening_credit ?? 0);

        if (!empty($start_date)) {
            $pre = DB::table('journal_entries')
                ->where('chart_of_account_id', $id)
                ->where('date', '<', $start_date)
                ->where('reversed', 0)
                ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
                ->selectRaw('COALESCE(SUM(debit),0) AS debit, COALESCE(SUM(credit),0) AS credit')
                ->first();
            $openingDr += $pre->debit;
            $openingCr += $pre->credit;
        }

        // Determine opening balance sign based on account normal side
        $isDebitNormal = in_array($account->account_type, ['asset','expense']);
        $openingBalance = $isDebitNormal ? ($openingDr - $openingCr) : ($openingCr - $openingDr);

        // Period transactions
        $entries = [];
        if (!empty($start_date) && !empty($end_date)) {
            $entries = DB::table('journal_entries')
                ->leftJoin('users', 'users.id', 'journal_entries.created_by_id')
                ->where('journal_entries.chart_of_account_id', $id)
                ->whereBetween('journal_entries.date', [$start_date, $end_date])
                ->where('journal_entries.reversed', 0)
                ->when($branch_id, fn($q) => $q->where('journal_entries.branch_id', $branch_id))
                ->selectRaw("journal_entries.id,journal_entries.date,journal_entries.transaction_number,
                    journal_entries.transaction_type,journal_entries.debit,journal_entries.credit,
                    journal_entries.reference,journal_entries.notes,journal_entries.manual_entry,
                    concat(users.first_name,' ',users.last_name) created_by")
                ->orderBy('journal_entries.date')
                ->orderBy('journal_entries.id')
                ->get();
        }

        // Compute running balance
        $running = $openingBalance;
        $lines   = [];
        foreach ($entries as $e) {
            $dr = (float)($e->debit  ?? 0);
            $cr = (float)($e->credit ?? 0);
            if ($isDebitNormal) {
                $running += ($dr - $cr);
            } else {
                $running += ($cr - $dr);
            }
            $obj = clone $e;
            $obj->running_balance = $running;
            $lines[] = $obj;
        }

        // Totals for the period
        $periodDr = collect($entries)->sum('debit');
        $periodCr = collect($entries)->sum('credit');
        $closingBalance = $running;

        if ($request->download && !empty($start_date)) {
            if ($request->type == 'pdf') {
                $pdf = PDF::loadView(theme_view_file('accounting::chart_of_account.statement_pdf'),
                    compact('account','start_date','end_date','branch_id','branches',
                            'openingBalance','lines','periodDr','periodCr','closingBalance','isDebitNormal'));
                return $pdf->download('AccountStatement-'.$account->gl_code.'.pdf');
            }
            $view = theme_view('accounting::chart_of_account.statement_pdf',
                compact('account','start_date','end_date','branch_id','branches',
                        'openingBalance','lines','periodDr','periodCr','closingBalance','isDebitNormal'));
            if ($request->type == 'excel_2007') return Excel::download(new AccountingExport($view), 'AccountStatement-'.$account->gl_code.'.xlsx');
            if ($request->type == 'excel')      return Excel::download(new AccountingExport($view), 'AccountStatement-'.$account->gl_code.'.xls');
            if ($request->type == 'csv')        return Excel::download(new AccountingExport($view), 'AccountStatement-'.$account->gl_code.'.csv');
        }

        return theme_view('accounting::chart_of_account.statement',
            compact('account','start_date','end_date','branch_id','branches',
                    'openingBalance','lines','periodDr','periodCr','closingBalance','isDebitNormal'));
    }
}
