<?php

namespace Modules\Accounting\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Accounting\Entities\JournalEntry;
use Modules\Branch\Entities\Branch;
use Modules\Core\Entities\Currency;
use Modules\Core\Entities\PaymentDetail;
use Modules\Core\Entities\PaymentType;
use PDF;
use Yajra\DataTables\Facades\DataTables;

class JournalEntryController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:accounting.journal_entries.index'])->only(['index','show','get_journal_entries','print_entry','pdf']);
        $this->middleware(['permission:accounting.journal_entries.create'])->only(['create','store']);
        $this->middleware(['permission:accounting.journal_entries.edit'])->only(['edit','update']);
        $this->middleware(['permission:accounting.journal_entries.reverse'])->only(['reverse']);
        $this->middleware(['permission:accounting.journal_entries.destroy'])->only(['destroy']);
    }

    // ─── INDEX ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $perPage             = $request->per_page ?: 20;
        $orderBy             = $request->order_by;
        $orderByDir          = $request->order_by_dir;
        $search              = $request->s;
        $start_date          = $request->start_date;
        $end_date            = $request->end_date;
        $branch_id           = $request->branch_id;
        $chart_of_account_id = $request->chart_of_account_id;

        $data = JournalEntry::leftJoin('branches', 'branches.id', 'journal_entries.branch_id')
            ->leftJoin('chart_of_accounts', 'chart_of_accounts.id', 'journal_entries.chart_of_account_id')
            ->leftJoin('users', 'users.id', 'journal_entries.created_by_id')
            ->when($orderBy, fn($q) => $q->orderBy($orderBy, $orderByDir))
            ->when($end_date, fn($q) => $q->whereBetween('journal_entries.date', [$start_date, $end_date]))
            ->when($branch_id, fn($q) => $q->where('journal_entries.branch_id', $branch_id))
            ->when($chart_of_account_id, fn($q) => $q->where('journal_entries.chart_of_account_id', $chart_of_account_id))
            ->when($search, function (Builder $q) use ($search) {
                $q->where('journal_entries.name', 'like', "%$search%")
                  ->orWhere('journal_entries.transaction_number', 'like', "%$search%")
                  ->orWhere('branches.name', 'like', "%$search%")
                  ->orWhere('users.first_name', 'like', "%$search%")
                  ->orWhere('users.last_name', 'like', "%$search%");
            })
            ->selectRaw("journal_entries.id,journal_entries.date,journal_entries.debit,journal_entries.credit,
                journal_entries.transaction_number,journal_entries.manual_entry,journal_entries.reversed,
                branches.name branch,chart_of_accounts.account_type,chart_of_accounts.name account_name,
                concat(users.first_name,' ',users.last_name) created_by")
            ->orderByDesc('journal_entries.id')
            ->paginate($perPage)->appends($request->input());

        $branches          = Branch::all();
        $chart_of_accounts = ChartOfAccount::where('active', 1)->orderBy('gl_code')->get();
        return theme_view('accounting::journal_entry.index', compact('data','branches','chart_of_accounts'));
    }

    public function get_journal_entries(Request $request)
    {
        $start_date          = $request->start_date;
        $end_date            = $request->end_date;
        $branch_id           = $request->branch_id;
        $chart_of_account_id = $request->chart_of_account_id;

        $query = DB::table('journal_entries')
            ->leftJoin('branches', 'branches.id', 'journal_entries.branch_id')
            ->leftJoin('chart_of_accounts', 'chart_of_accounts.id', 'journal_entries.chart_of_account_id')
            ->leftJoin('users', 'users.id', 'journal_entries.created_by_id')
            ->when($end_date, fn($q) => $q->whereBetween('journal_entries.date', [$start_date, $end_date]))
            ->when($branch_id, fn($q) => $q->where('journal_entries.branch_id', $branch_id))
            ->when($chart_of_account_id, fn($q) => $q->where('journal_entries.chart_of_account_id', $chart_of_account_id))
            ->selectRaw("journal_entries.id,journal_entries.date,journal_entries.debit,journal_entries.credit,
                journal_entries.transaction_number,journal_entries.manual_entry,journal_entries.reversed,
                branches.name branch,chart_of_accounts.account_type,chart_of_accounts.name account_name,
                concat(users.first_name,' ',users.last_name) created_by");

        return DataTables::of($query)
            ->editColumn('debit',  fn($d) => !empty($d->debit)  ? number_format($d->debit,  2) : '')
            ->editColumn('credit', fn($d) => !empty($d->credit) ? number_format($d->credit, 2) : '')
            ->editColumn('account_type', function ($d) {
                $map = ['asset'=>'Asset','expense'=>'Expense','equity'=>'Equity','liability'=>'Liability','income'=>'Income'];
                return $map[$d->account_type] ?? $d->account_type;
            })
            ->editColumn('action', function ($d) {
                $a  = '<div class="btn-group"><button class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown"><i class="fa fa-navicon"></i></button>';
                $a .= '<ul class="dropdown-menu dropdown-menu-right">';
                $a .= '<li><a href="'.url('accounting/journal_entry/'.$d->transaction_number.'/show').'">'.trans_choice('core::general.detail',2).'</a></li>';
                $a .= '<li><a href="'.url('accounting/journal_entry/'.$d->transaction_number.'/print').'" target="_blank">Print / PDF</a></li>';
                if ($d->manual_entry && !$d->reversed) {
                    if (Auth::user()->hasPermissionTo('accounting.journal_entries.edit'))
                        $a .= '<li><a href="'.url('accounting/journal_entry/'.$d->transaction_number.'/edit').'">Edit</a></li>';
                    if (Auth::user()->hasPermissionTo('accounting.journal_entries.destroy'))
                        $a .= '<li><a href="'.url('accounting/journal_entry/'.$d->transaction_number.'/destroy').'" class="confirm text-danger">Delete</a></li>';
                }
                $a .= '</ul></div>';
                return $a;
            })
            ->editColumn('id', fn($d) => '<a href="'.url('accounting/journal_entry/'.$d->transaction_number.'/show').'">'.$d->id.'</a>')
            ->rawColumns(['id','action'])->make(true);
    }

    // ─── CREATE ───────────────────────────────────────────────────────────────

    public function create()
    {
        $chart_of_accounts = ChartOfAccount::where('active', 1)->orderBy('gl_code')->get();
        $currencies        = Currency::all();
        $payment_types     = PaymentType::where('active', 1)->get();
        $branches          = Branch::all();
        return theme_view('accounting::journal_entry.create', compact('chart_of_accounts','currencies','payment_types','branches'));
    }

    // ─── STORE  (multi-line: lines[].account_id / debit / credit / description) ─

    public function store(Request $request)
    {
        $request->validate([
            'branch_id'          => ['required'],
            'currency_id'        => ['required'],
            'date'               => ['required','date'],
            'lines'              => ['required','array','min:2'],
            'lines.*.account_id' => ['required','exists:chart_of_accounts,id'],
            'lines.*.debit'      => ['nullable','numeric','min:0'],
            'lines.*.credit'     => ['nullable','numeric','min:0'],
        ]);

        $totalDr = collect($request->lines)->sum(fn($l) => (float)($l['debit']  ?? 0));
        $totalCr = collect($request->lines)->sum(fn($l) => (float)($l['credit'] ?? 0));

        if (round($totalDr, 4) !== round($totalCr, 4)) {
            return back()->withErrors(['lines' => "Entry is not balanced — Debits ({$totalDr}) ≠ Credits ({$totalCr})."])->withInput();
        }
        if ($totalDr == 0) {
            return back()->withErrors(['lines' => 'Amounts cannot all be zero.'])->withInput();
        }

        $pd                   = new PaymentDetail();
        $pd->created_by_id    = Auth::id();
        $pd->payment_type_id  = $request->payment_type_id;
        $pd->transaction_type = 'journal_manual_entry';
        $pd->cheque_number    = $request->cheque_number;
        $pd->receipt          = $request->receipt;
        $pd->account_number   = $request->account_number;
        $pd->bank_name        = $request->bank_name;
        $pd->routing_code     = $request->routing_code;
        $pd->save();

        $txn            = uniqid('JE-');
        [$year, $month] = explode('-', $request->date);

        foreach ($request->lines as $line) {
            $dr = (float)($line['debit']  ?? 0);
            $cr = (float)($line['credit'] ?? 0);
            if ($dr == 0 && $cr == 0) continue;

            $e                      = new JournalEntry();
            $e->created_by_id       = Auth::id();
            $e->payment_detail_id   = $pd->id;
            $e->transaction_number  = $txn;
            $e->branch_id           = $request->branch_id;
            $e->currency_id         = $request->currency_id;
            $e->chart_of_account_id = $line['account_id'];
            $e->transaction_type    = 'manual_entry';
            $e->date                = $request->date;
            $e->month               = $month;
            $e->year                = $year;
            $e->debit               = $dr ?: null;
            $e->credit              = $cr ?: null;
            $e->reference           = $request->reference;
            $e->manual_entry        = 1;
            $e->notes               = $line['description'] ?? $request->notes;
            $e->save();
        }

        activity()->log('Create Journal Entry '.$txn);
        \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();
        return redirect('accounting/journal_entry/'.$txn.'/show');
    }

    // ─── SHOW ─────────────────────────────────────────────────────────────────

    public function show($id)
    {
        $data = JournalEntry::where('transaction_number', $id)
            ->with('chart_of_account','branch','created_by')->get();
        if ($data->isEmpty()) abort(404);
        $totalDebit  = $data->sum('debit');
        $totalCredit = $data->sum('credit');
        return theme_view('accounting::journal_entry.show', compact('data','totalDebit','totalCredit'));
    }

    // ─── PRINT / PDF ─────────────────────────────────────────────────────────

    public function print_entry($id)
    {
        $data        = JournalEntry::where('transaction_number', $id)->with('chart_of_account','branch','created_by')->get();
        $totalDebit  = $data->sum('debit');
        $totalCredit = $data->sum('credit');
        return theme_view('accounting::journal_entry.print', compact('data','totalDebit','totalCredit'));
    }

    public function pdf($id)
    {
        $data        = JournalEntry::where('transaction_number', $id)->with('chart_of_account','branch','created_by')->get();
        $totalDebit  = $data->sum('debit');
        $totalCredit = $data->sum('credit');
        $pdf = PDF::loadView(theme_view_file('accounting::journal_entry.print'), compact('data','totalDebit','totalCredit'));
        return $pdf->download('Journal-Entry-'.$id.'.pdf');
    }

    // ─── EDIT ─────────────────────────────────────────────────────────────────

    public function edit($id)
    {
        $entries = JournalEntry::where('transaction_number', $id)
            ->where('manual_entry', 1)->where('reversed', 0)
            ->with('chart_of_account')->get();

        if ($entries->isEmpty()) {
            \flash('This entry cannot be edited — it is system-generated or already reversed.')->error()->important();
            return redirect('accounting/journal_entry/'.$id.'/show');
        }

        $chart_of_accounts = ChartOfAccount::where('active', 1)->orderBy('gl_code')->get();
        $currencies        = Currency::all();
        $payment_types     = PaymentType::where('active', 1)->get();
        $branches          = Branch::all();
        $first             = $entries->first();

        return theme_view('accounting::journal_entry.edit',
            compact('entries','first','chart_of_accounts','currencies','payment_types','branches'));
    }

    // ─── UPDATE ───────────────────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        $entries = JournalEntry::where('transaction_number', $id)
            ->where('manual_entry', 1)->where('reversed', 0)->get();

        if ($entries->isEmpty()) {
            \flash('This entry cannot be edited.')->error()->important();
            return redirect('accounting/journal_entry/'.$id.'/show');
        }

        $request->validate([
            'branch_id'          => ['required'],
            'currency_id'        => ['required'],
            'date'               => ['required','date'],
            'lines'              => ['required','array','min:2'],
            'lines.*.account_id' => ['required','exists:chart_of_accounts,id'],
            'lines.*.debit'      => ['nullable','numeric','min:0'],
            'lines.*.credit'     => ['nullable','numeric','min:0'],
        ]);

        $totalDr = collect($request->lines)->sum(fn($l) => (float)($l['debit']  ?? 0));
        $totalCr = collect($request->lines)->sum(fn($l) => (float)($l['credit'] ?? 0));

        if (round($totalDr, 4) !== round($totalCr, 4)) {
            return back()->withErrors(['lines' => "Entry is not balanced — Debits ≠ Credits."])->withInput();
        }

        // Delete old lines and re-insert
        JournalEntry::where('transaction_number', $id)->delete();

        [$year, $month] = explode('-', $request->date);

        foreach ($request->lines as $line) {
            $dr = (float)($line['debit']  ?? 0);
            $cr = (float)($line['credit'] ?? 0);
            if ($dr == 0 && $cr == 0) continue;

            $e                      = new JournalEntry();
            $e->created_by_id       = Auth::id();
            $e->transaction_number  = $id;
            $e->branch_id           = $request->branch_id;
            $e->currency_id         = $request->currency_id;
            $e->chart_of_account_id = $line['account_id'];
            $e->transaction_type    = 'manual_entry';
            $e->date                = $request->date;
            $e->month               = $month;
            $e->year                = $year;
            $e->debit               = $dr ?: null;
            $e->credit              = $cr ?: null;
            $e->reference           = $request->reference;
            $e->manual_entry        = 1;
            $e->reversible          = 1;
            $e->notes               = $line['description'] ?? $request->notes;
            $e->save();
        }

        activity()->log('Update Journal Entry '.$id);
        \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();
        return redirect('accounting/journal_entry/'.$id.'/show');
    }

    // ─── DESTROY  (manual, non-reversed only) ─────────────────────────────────

    public function destroy($id)
    {
        $entries = JournalEntry::where('transaction_number', $id)->get();
        if ($entries->isEmpty()) {
            \flash('Journal entry not found.')->error()->important();
            return redirect('accounting/journal_entry');
        }
        if (!$entries->first()->manual_entry) {
            \flash('System-generated entries cannot be deleted. Use Reverse instead.')->error()->important();
            return redirect('accounting/journal_entry/'.$id.'/show');
        }
        if ($entries->first()->reversed) {
            \flash('Already-reversed entries cannot be deleted.')->error()->important();
            return redirect('accounting/journal_entry/'.$id.'/show');
        }
        JournalEntry::where('transaction_number', $id)->delete();
        activity()->log('Delete Journal Entry '.$id);
        \flash(trans_choice('core::general.successfully_deleted', 1))->success()->important();
        return redirect('accounting/journal_entry');
    }

    // ─── REVERSE ──────────────────────────────────────────────────────────────

    public function reverse($id)
    {
        $originals = JournalEntry::where('transaction_number', $id)->get();
        if ($originals->isEmpty()) {
            \flash('Journal entry not found.')->error()->important();
            return redirect()->back();
        }
        if ($originals->first()->reversed) {
            \flash('This entry has already been reversed.')->error()->important();
            return redirect('accounting/journal_entry/'.$id.'/show');
        }

        JournalEntry::where('transaction_number', $id)->update(['reversed' => 1, 'reversible' => 0]);

        $txn = uniqid('JE-REV-');
        foreach ($originals as $key) {
            $e                      = new JournalEntry();
            $e->created_by_id       = Auth::id();
            $e->transaction_number  = $txn;
            $e->payment_detail_id   = $key->payment_detail_id;
            $e->branch_id           = $key->branch_id;
            $e->currency_id         = $key->currency_id;
            $e->chart_of_account_id = $key->chart_of_account_id;
            $e->transaction_type    = $key->transaction_type;
            $e->date                = date('Y-m-d');
            [$year, $month]         = explode('-', date('Y-m-d'));
            $e->month               = $month;
            $e->year                = $year;
            $e->debit               = !empty($key->credit) ? $key->credit : null;
            $e->credit              = !empty($key->debit)  ? $key->debit  : null;
            $e->reference           = 'REV:'.$id;
            $e->manual_entry        = $key->manual_entry;
            $e->notes               = 'Reversal of '.$id.'. '.($key->notes ?? '');
            $e->save();
        }

        activity()->log('Reverse Journal Entry '.$id);
        \flash('Journal entry reversed successfully.')->success()->important();
        return redirect('accounting/journal_entry/'.$txn.'/show');
    }
}
