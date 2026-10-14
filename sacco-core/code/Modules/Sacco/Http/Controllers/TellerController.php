<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Branch\Entities\Branch;
use Modules\Core\Entities\PaymentType;
use Modules\Sacco\Entities\SaccoTeller;
use Modules\Sacco\Entities\SaccoTellerSession;
use Modules\Sacco\Services\EarmarkService;
use Modules\Sacco\Services\GlobalSearchService;
use Modules\Sacco\Services\Member360Service;
use Modules\ActivityLog\Services\AuditLogger;
use Modules\Sacco\Services\TellerService;

class TellerController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.teller.index'])->only(['index', 'memberLookup']);
        $this->middleware(['permission:sacco.teller.manage'])->only(['openSession', 'closeSession', 'updateLimits']);
    }

    public function index()
    {
        $teller = SaccoTeller::where('user_id', auth()->id())->first();
        $session = $teller ? SaccoTellerSession::where('sacco_teller_id', $teller->id)->where('status', 'open')->first() : null;
        $branches = Branch::all();
        $payment_types = PaymentType::where('active', 1)->get();
        return theme_view('sacco::teller.index', compact('teller', 'session', 'branches', 'payment_types'));
    }

    public function openSession(Request $request, TellerService $service)
    {
        $request->validate([
            'branch_id' => 'required',
            'opening_balance' => 'required|numeric|min:0',
        ]);
        $service->openSession($request->branch_id, $request->opening_balance, $request->till_number);
        AuditLogger::log('Teller Session Opened', null, ['branch_id' => $request->branch_id, 'opening_balance' => $request->opening_balance], 'sacco', 'create');
        \flash('Teller session opened')->success()->important();
        return redirect('sacco/teller');
    }

    public function closeSession(Request $request, TellerService $service)
    {
        $request->validate([
            'session_id' => 'required',
            'closing_balance' => 'required|numeric|min:0',
        ]);
        $session = SaccoTellerSession::findOrFail($request->session_id);
        $service->closeSession($session, $request->closing_balance, $request->notes);
        AuditLogger::log('Teller Session Closed', $session, ['session_id' => $session->id, 'closing_balance' => $request->closing_balance, 'notes' => $request->notes], 'sacco', 'update');
        \flash('Teller session closed')->success()->important();
        return redirect('sacco/teller');
    }

    public function updateLimits(Request $request, TellerService $service)
    {
        $request->validate([
            'teller_id' => 'required',
            'max_single_transaction' => 'nullable|numeric|min:0',
            'max_daily_withdrawal' => 'nullable|numeric|min:0',
            'max_cash_on_hand' => 'nullable|numeric|min:0',
        ]);
        $teller = SaccoTeller::findOrFail($request->teller_id);
        $service->updateLimits($teller, $request->only([
            'max_single_transaction', 'max_daily_withdrawal', 'max_cash_on_hand',
        ]));
        \flash('Teller limits updated')->success()->important();
        return redirect('sacco/teller');
    }

    public function memberLookup(Request $request)
    {
        $request->validate(['q' => 'required|string|min:2']);
        $results = (new GlobalSearchService())->search($request->q, 5);
        $clients = array_filter($results, function ($r) {
            return $r['type'] === 'client';
        });

        $enriched = [];
        foreach ($clients as $client) {
            $summary = (new Member360Service())->getSummary($client['id']);
            $earmarks = (new EarmarkService())->getActiveForClient($client['id'], true);
            $enriched[] = array_merge($client, [
                'member_number' => $summary['member_number'] ?? null,
                'savings_balance' => $summary['savings_balance'],
                'share_value' => $summary['share_value'],
                'loan_balance' => $summary['loan_balance'],
                'arrears_amount' => $summary['arrears_amount'],
                'earmarks' => $earmarks,
                'has_blocking_earmark' => collect($earmarks)->contains(function ($e) {
                    return in_array($e['restriction_type'], ['block_withdrawal', 'block_deposit', 'block_all_transactions']);
                }),
            ]);
        }

        return response()->json($enriched);
    }
}
