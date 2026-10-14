<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Branch\Entities\Branch;
use Modules\Client\Entities\ClientType;
use Modules\Client\Entities\Profession;
use Modules\Client\Entities\Title;
use Modules\Core\Entities\PaymentType;
use Modules\Sacco\Entities\SaccoAgent;
use Modules\Sacco\Entities\SaccoAgentTransaction;
use Modules\Sacco\Entities\SaccoUtilityProvider;
use Modules\Sacco\Services\AgentCommissionService;
use Modules\Sacco\Services\AgentPortalService;

class AgentPortalController extends Controller
{
    protected AgentPortalService $portalService;
    protected AgentCommissionService $commissionService;

    public function __construct(AgentPortalService $portalService, AgentCommissionService $commissionService)
    {
        $this->middleware(['auth', '2fa']);
        $this->portalService = $portalService;
        $this->commissionService = $commissionService;
    }

    protected function getAgent(): ?SaccoAgent
    {
        return SaccoAgent::where('user_id', Auth::id())->where('status', 'active')->first();
    }

    protected function requireAgent()
    {
        $agent = $this->getAgent();
        if (!$agent) {
            abort(403, 'You do not have an active agent account.');
        }
        return $agent;
    }

    // ─── DASHBOARD ───────────────────────────────────────────────────────────

    public function dashboard()
    {
        $agent = $this->requireAgent();
        $stats = $agent->getDashboardStats();
        $recentTransactions = $agent->transactions()
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $commissionSummary = $this->commissionService->getAgentCommissionSummary($agent, 'month');

        return theme_view('sacco::agent_portal.dashboard', compact('agent', 'stats', 'recentTransactions', 'commissionSummary'));
    }

    // ─── TRANSACTIONS ────────────────────────────────────────────────────────

    public function transactions(Request $request)
    {
        $agent = $this->requireAgent();

        $query = $agent->transactions()->with('client');

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(25);

        return theme_view('sacco::agent_portal.transactions', compact('agent', 'transactions'));
    }

    // ─── MEMBER LOOKUP ───────────────────────────────────────────────────────

    public function memberLookup(Request $request)
    {
        $agent = $this->requireAgent();
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = $this->portalService->searchMembers($query, null, 15);

        return response()->json($results);
    }

    public function memberDetails(Request $request)
    {
        $agent = $this->requireAgent();
        $clientId = $request->input('client_id');

        $details = $this->portalService->getMemberDetails($clientId);

        if (!$details) {
            return response()->json(['error' => 'Member not found'], 404);
        }

        return response()->json($details);
    }

    // ─── DEPOSIT ─────────────────────────────────────────────────────────────

    public function showDeposit()
    {
        $agent = $this->requireAgent();
        $paymentTypes = PaymentType::where('active', 1)->get();

        return theme_view('sacco::agent_portal.deposit', compact('agent', 'paymentTypes'));
    }

    public function processDeposit(Request $request)
    {
        $agent = $this->requireAgent();

        $request->validate([
            'savings_id' => 'required|exists:savings,id',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'payment_type_id' => 'required|exists:payment_types,id',
        ]);

        $result = $this->portalService->processDeposit($agent, $request->all());

        if ($request->expectsJson()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        if ($result['success']) {
            flash($result['message'])->success();
            return redirect()->route('agent.deposit')->with('receipt', $result);
        }

        flash($result['message'])->error();
        return back()->withInput();
    }

    // ─── WITHDRAWAL ──────────────────────────────────────────────────────────

    public function showWithdrawal()
    {
        $agent = $this->requireAgent();
        $paymentTypes = PaymentType::where('active', 1)->get();

        return theme_view('sacco::agent_portal.withdrawal', compact('agent', 'paymentTypes'));
    }

    public function processWithdrawal(Request $request)
    {
        $agent = $this->requireAgent();

        $request->validate([
            'savings_id' => 'required|exists:savings,id',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'payment_type_id' => 'required|exists:payment_types,id',
        ]);

        $result = $this->portalService->processWithdrawal($agent, $request->all());

        if ($request->expectsJson()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        if ($result['success']) {
            flash($result['message'])->success();
            return redirect()->route('agent.withdrawal')->with('receipt', $result);
        }

        flash($result['message'])->error();
        return back()->withInput();
    }

    // ─── LOAN REPAYMENT ──────────────────────────────────────────────────────

    public function showLoanPayment()
    {
        $agent = $this->requireAgent();
        $paymentTypes = PaymentType::where('active', 1)->get();

        return theme_view('sacco::agent_portal.loan_payment', compact('agent', 'paymentTypes'));
    }

    public function processLoanPayment(Request $request)
    {
        $agent = $this->requireAgent();

        $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'payment_type_id' => 'required|exists:payment_types,id',
        ]);

        $result = $this->portalService->processLoanRepayment($agent, $request->all());

        if ($request->expectsJson()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        if ($result['success']) {
            flash($result['message'])->success();
            return redirect()->route('agent.loan_payment')->with('receipt', $result);
        }

        flash($result['message'])->error();
        return back()->withInput();
    }

    // ─── UTILITY PAYMENT ─────────────────────────────────────────────────────

    public function showUtilityPayment()
    {
        $agent = $this->requireAgent();
        $providers = SaccoUtilityProvider::where('status', 'active')->orderBy('name')->get();

        return theme_view('sacco::agent_portal.utility_payment', compact('agent', 'providers'));
    }

    public function processUtilityPayment(Request $request)
    {
        $agent = $this->requireAgent();

        $request->validate([
            'provider_id' => 'required|exists:sacco_utility_providers,id',
            'account_number' => 'required|string|max:50',
            'amount' => 'required|numeric|min:1',
        ]);

        $result = $this->portalService->processUtilityPayment($agent, $request->all());

        if ($request->expectsJson()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        if ($result['success']) {
            flash($result['message'])->success();
            return redirect()->route('agent.utility_payment')->with('receipt', $result);
        }

        flash($result['message'])->error();
        return back()->withInput();
    }

    public function utilityPayments(Request $request)
    {
        $agent = $this->requireAgent();

        $payments = $agent->utilityPayments()
            ->with('provider')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return theme_view('sacco::agent_portal.utility_payments', compact('agent', 'payments'));
    }

    // ─── MEMBER REGISTRATION ─────────────────────────────────────────────────

    public function showRegisterMember()
    {
        $agent = $this->requireAgent();
        $titles = Title::all();
        $clientTypes = ClientType::all();
        $professions = Profession::all();
        $branches = Branch::where('active', 1)->get();

        return theme_view('sacco::agent_portal.register_member', compact('agent', 'titles', 'clientTypes', 'professions', 'branches'));
    }

    public function registerMember(Request $request)
    {
        $agent = $this->requireAgent();

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'mobile' => 'required|string|max:20',
            'email' => 'nullable|email|max:100',
            'dob' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
        ]);

        $result = $this->portalService->registerMember($agent, $request->all());

        if ($request->expectsJson()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        if ($result['success']) {
            flash($result['message'])->success();
            return redirect()->route('agent.register_member');
        }

        flash($result['message'])->error();
        return back()->withInput();
    }

    public function memberRegistrations(Request $request)
    {
        $agent = $this->requireAgent();

        $registrations = $agent->memberRegistrations()
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return theme_view('sacco::agent_portal.member_registrations', compact('agent', 'registrations'));
    }

    // ─── COMMISSIONS ─────────────────────────────────────────────────────────

    public function commissions(Request $request)
    {
        $agent = $this->requireAgent();

        $query = $agent->commissionLedger();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $ledger = $query->orderBy('created_at', 'desc')->paginate(25);
        $summary = $this->commissionService->getAgentCommissionSummary($agent, 'month');
        $rates = $this->commissionService->getCommissionRates();

        return theme_view('sacco::agent_portal.commissions', compact('agent', 'ledger', 'summary', 'rates'));
    }

    public function withdrawCommission(Request $request)
    {
        $agent = $this->requireAgent();

        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $result = $this->commissionService->processCommissionWithdrawal(
            $agent, 
            (float)$request->amount,
            $request->input('method', 'cash')
        );

        if ($request->expectsJson()) {
            return response()->json($result, $result['success'] ? 200 : 422);
        }

        if ($result['success']) {
            flash($result['message'])->success();
        } else {
            flash($result['message'])->error();
        }

        return redirect()->route('agent.commissions');
    }

    // ─── FLOAT MANAGEMENT ────────────────────────────────────────────────────

    public function floatHistory(Request $request)
    {
        $agent = $this->requireAgent();

        $transactions = $agent->transactions()
            ->whereNotNull('float_before')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return theme_view('sacco::agent_portal.float_history', compact('agent', 'transactions'));
    }

    // ─── RECEIPTS ────────────────────────────────────────────────────────────

    public function receipt($transactionId)
    {
        $agent = $this->requireAgent();

        $transaction = SaccoAgentTransaction::with(['client', 'savings.savings_product', 'agent'])
            ->where('sacco_agent_id', $agent->id)
            ->findOrFail($transactionId);

        return theme_view('sacco::agent_portal.receipt', compact('agent', 'transaction'));
    }

    // ─── PROFILE ─────────────────────────────────────────────────────────────

    public function profile()
    {
        $agent = $this->requireAgent();

        return theme_view('sacco::agent_portal.profile', compact('agent'));
    }

    public function updatePin(Request $request)
    {
        $agent = $this->requireAgent();

        $request->validate([
            'current_pin' => 'required|digits:4',
            'new_pin' => 'required|digits:4|confirmed',
        ]);

        if (!$agent->verifyPin($request->current_pin)) {
            flash('Current PIN is incorrect.')->error();
            return back();
        }

        $agent->setPin($request->new_pin);
        flash('PIN updated successfully.')->success();

        return redirect()->route('agent.profile');
    }
}
