<?php

namespace Modules\Sacco\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoMpesaReconciliation;
use Modules\Sacco\Entities\SaccoMpesaReconciliationItem;
use Modules\Sacco\Entities\SaccoMpesaReconciliationAuditLog;
use Modules\Sacco\Entities\SaccoMpesaFloatAccount;
use Modules\Sacco\Entities\SaccoMpesaFloatEntry;
use Modules\Sacco\Entities\SaccoMpesaC2bTransaction;
use Modules\Sacco\Entities\SaccoMpesaB2cTransaction;
use Modules\Sacco\Services\MpesaReconciliationService;

class MpesaReconciliationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware('permission:sacco.mpesa_recon.index')
            ->only(['index', 'dashboard', 'show', 'auditLog', 'exceptions']);
        $this->middleware('permission:sacco.mpesa_recon.create')
            ->only(['create', 'store', 'importItems', 'autoMatch', 'manualMatch', 'unmatch',
                    'flagException', 'resolveException']);
        $this->middleware('permission:sacco.mpesa_recon.approve')
            ->only(['approve', 'archive']);
        $this->middleware('permission:sacco.mpesa_recon.float')
            ->only(['floatAccounts', 'createFloat', 'storeFloat', 'addFloatEntry']);
    }

    // ─── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard(MpesaReconciliationService $service)
    {
        $stats           = $service->getDashboardStats();
        $recentSessions  = SaccoMpesaReconciliation::orderByDesc('id')->limit(5)->get();
        $recentC2b       = SaccoMpesaC2bTransaction::orderByDesc('id')->limit(10)->get();
        $recentB2c       = SaccoMpesaB2cTransaction::orderByDesc('id')->limit(10)->get();

        return theme_view('sacco::mpesa_recon.dashboard',
            compact('stats', 'recentSessions', 'recentC2b', 'recentB2c'));
    }

    // ─── Sessions CRUD ────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = SaccoMpesaReconciliation::orderByDesc('id');
        if ($request->filled('status'))   $query->where('status', $request->status);
        if ($request->filled('provider')) $query->where('provider', $request->provider);
        $sessions = $query->paginate(20);
        return theme_view('sacco::mpesa_recon.index', compact('sessions'));
    }

    public function create()
    {
        return theme_view('sacco::mpesa_recon.create');
    }

    public function store(Request $request, MpesaReconciliationService $service)
    {
        $request->validate([
            'provider'    => 'required|in:mpesa,airtel_money,tkash,mtn,other',
            'short_code'  => 'required|string|max:20',
            'account_type'=> 'required|in:c2b,b2c,both',
            'period_from' => 'required|date',
            'period_to'   => 'required|date|after_or_equal:period_from',
        ]);

        $recon = $service->createSession($request->only([
            'provider', 'short_code', 'account_type',
            'period_from', 'period_to', 'notes',
        ]), Auth::id());

        \flash('Reconciliation session ' . $recon->reference . ' created.')->success()->important();
        return redirect('sacco/mpesa-recon/' . $recon->id . '/show');
    }

    public function show($id)
    {
        $recon = SaccoMpesaReconciliation::with([
            'items' => fn($q) => $q->orderBy('completion_time'),
        ])->findOrFail($id);

        $unmatchedC2b = SaccoMpesaC2bTransaction::where('is_reconciled', 0)
            ->whereBetween('created_at', [
                $recon->period_from . ' 00:00:00',
                $recon->period_to   . ' 23:59:59',
            ])->orderByDesc('created_at')->get();

        $unmatchedB2c = SaccoMpesaB2cTransaction::where('is_reconciled', 0)
            ->where('status', 'completed')
            ->whereBetween('created_at', [
                $recon->period_from . ' 00:00:00',
                $recon->period_to   . ' 23:59:59',
            ])->orderByDesc('created_at')->get();

        return theme_view('sacco::mpesa_recon.show',
            compact('recon', 'unmatchedC2b', 'unmatchedB2c'));
    }

    // ─── Import ───────────────────────────────────────────────────────────────

    public function importItems(Request $request, $id, MpesaReconciliationService $service)
    {
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt']);

        $recon = SaccoMpesaReconciliation::findOrFail($id);
        if (!$recon->isEditable()) {
            \flash('This reconciliation session cannot be edited.')->error()->important();
            return back();
        }

        $file   = $request->file('csv_file');
        $path   = $file->store('mpesa_statements', 'local');
        $handle = fopen($file->getRealPath(), 'r');

        // Skip header row
        $headers = fgetcsv($handle);
        $headers = array_map('trim', $headers);

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === count($headers)) {
                $rows[] = array_combine($headers, $data);
            }
        }
        fclose($handle);

        $recon->statement_file_path = $path;
        $recon->statement_file_name = $file->getClientOriginalName();
        $recon->save();

        $result = $service->importRows($recon, $rows, Auth::id());

        \flash("Imported {$result['imported']} rows. Skipped: {$result['skipped']}.")->success()->important();
        return redirect('sacco/mpesa-recon/' . $id . '/show');
    }

    // ─── Matching ─────────────────────────────────────────────────────────────

    public function autoMatch($id, MpesaReconciliationService $service)
    {
        $recon  = SaccoMpesaReconciliation::findOrFail($id);
        $result = $service->autoMatch($recon, Auth::id());

        \flash("Auto-match: {$result['matched']} matched, {$result['variance']} with variance, {$result['unmatched']} unmatched.")
            ->success()->important();
        return redirect('sacco/mpesa-recon/' . $id . '/show');
    }

    public function manualMatch(Request $request, $id, MpesaReconciliationService $service)
    {
        $request->validate([
            'item_id'          => 'required|integer',
            'transaction_type' => 'required|in:c2b,b2c',
            'transaction_id'   => 'required|integer',
        ]);

        $item = SaccoMpesaReconciliationItem::where('sacco_mpesa_reconciliation_id', $id)
            ->findOrFail($request->item_id);

        $service->manualMatch(
            $item,
            $request->transaction_type,
            (int) $request->transaction_id,
            Auth::id(),
            $request->notes ?? ''
        );

        \flash('Item matched successfully.')->success()->important();
        return redirect('sacco/mpesa-recon/' . $id . '/show');
    }

    public function unmatch(Request $request, $id, $itemId, MpesaReconciliationService $service)
    {
        $item = SaccoMpesaReconciliationItem::where('sacco_mpesa_reconciliation_id', $id)
            ->findOrFail($itemId);
        $service->unmatch($item, Auth::id(), $request->reason ?? '');

        \flash('Item unmatched.')->warning()->important();
        return redirect('sacco/mpesa-recon/' . $id . '/show');
    }

    // ─── Exceptions ───────────────────────────────────────────────────────────

    public function exceptions($id)
    {
        $recon      = SaccoMpesaReconciliation::findOrFail($id);
        $exceptions = SaccoMpesaReconciliationItem::where('sacco_mpesa_reconciliation_id', $id)
            ->where('is_exception', 1)->paginate(30);

        $unmatched  = SaccoMpesaReconciliationItem::where('sacco_mpesa_reconciliation_id', $id)
            ->where('match_status', 'unmatched')->paginate(30);

        return theme_view('sacco::mpesa_recon.exceptions', compact('recon', 'exceptions', 'unmatched'));
    }

    public function flagException(Request $request, $id, $itemId, MpesaReconciliationService $service)
    {
        $request->validate(['reason' => 'required|string|max:200']);
        $item = SaccoMpesaReconciliationItem::findOrFail($itemId);
        $service->flagException($item, $request->reason, Auth::id());
        \flash('Item flagged as exception.')->warning()->important();
        return back();
    }

    public function resolveException(Request $request, $id, $itemId, MpesaReconciliationService $service)
    {
        $request->validate(['resolution' => 'required|string|max:300']);
        $item = SaccoMpesaReconciliationItem::findOrFail($itemId);
        $service->resolveException($item, $request->resolution, Auth::id());
        \flash('Exception resolved.')->success()->important();
        return back();
    }

    // ─── Approve / Archive ────────────────────────────────────────────────────

    public function approve($id, MpesaReconciliationService $service)
    {
        $recon = SaccoMpesaReconciliation::findOrFail($id);
        $service->approve($recon, Auth::id());

        $msg = $recon->status === 'discrepancy'
            ? 'Approved with discrepancies. Net variance: ' . number_format($recon->net_variance, 2)
            : 'Reconciliation approved.';

        \flash($msg)->success()->important();
        return redirect('sacco/mpesa-recon/' . $id . '/show');
    }

    public function archive($id, MpesaReconciliationService $service)
    {
        $recon = SaccoMpesaReconciliation::findOrFail($id);
        $recon->status = 'archived';
        $recon->save();
        $service->audit($recon, 'status_changed', 'Session archived', [], Auth::id(),
            ['status_before' => 'approved', 'status_after' => 'archived']);
        \flash('Session archived.')->success()->important();
        return redirect('sacco/mpesa-recon');
    }

    // ─── Audit Log ────────────────────────────────────────────────────────────

    public function auditLog($id)
    {
        $recon = SaccoMpesaReconciliation::findOrFail($id);
        $logs  = SaccoMpesaReconciliationAuditLog::where('sacco_mpesa_reconciliation_id', $id)
            ->orderByDesc('created_at')
            ->paginate(50);

        return theme_view('sacco::mpesa_recon.audit_log', compact('recon', 'logs'));
    }

    // ─── Float Accounts ───────────────────────────────────────────────────────

    public function floatAccounts()
    {
        $accounts = SaccoMpesaFloatAccount::with(['entries' => fn($q) => $q->latest()->limit(5)])
            ->orderBy('provider')->get();
        return theme_view('sacco::mpesa_recon.float', compact('accounts'));
    }

    public function storeFloat(Request $request)
    {
        $request->validate([
            'provider'   => 'required|string',
            'short_code' => 'required|string|max:20',
            'type'       => 'required|in:paybill,till,b2c_account',
        ]);

        SaccoMpesaFloatAccount::create(array_merge(
            $request->only(['provider', 'short_code', 'account_name', 'type', 'minimum_float', 'notes']),
            ['created_by_id' => Auth::id(), 'is_active' => 1]
        ));

        \flash('Float account added.')->success()->important();
        return redirect('sacco/mpesa-recon/float');
    }

    public function addFloatEntry(Request $request, $accountId, MpesaReconciliationService $service)
    {
        $request->validate([
            'entry_type'  => 'required|in:top_up,deduction,adjustment,confirmed_balance',
            'amount'      => 'required|numeric|min:0.01',
            'entry_date'  => 'required|date',
        ]);

        $account = SaccoMpesaFloatAccount::findOrFail($accountId);
        $service->addFloatEntry($account, $request->only([
            'entry_type', 'amount', 'entry_date', 'reference', 'notes',
        ]), Auth::id());

        \flash('Float entry recorded. New balance: ' . number_format($account->current_balance, 2))
            ->success()->important();
        return redirect('sacco/mpesa-recon/float');
    }
}
