<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Branch\Entities\Branch;
use Modules\Sacco\Entities\SaccoAgent;
use Modules\Sacco\Entities\SaccoAgentTransaction;
use Modules\Sacco\Services\AgencyBankingService;
use Modules\Savings\Entities\Savings;
use Modules\User\Entities\User;

class AgencyBankingController extends Controller
{
    protected $service;

    public function __construct(AgencyBankingService $service)
    {
        $this->service = $service;
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.agency.index'])->only(['index', 'transactions', 'show', 'dashboard']);
        $this->middleware(['permission:sacco.agency.manage'])->only([
            'create', 'store', 'edit', 'update', 'topUp', 'deposit', 'withdraw',
            'suspend', 'reactivate', 'close', 'reverse'
        ]);
    }

    public function index(Request $request)
    {
        $query = SaccoAgent::with('branch');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('branch_id')) {
            $query->byBranch($request->branch_id);
        }

        $agents = $query->orderBy('agent_name')->paginate(25);
        $branches = Branch::orderBy('name')->get();
        $stats = $this->service->getDashboardStats();

        return theme_view('sacco::agency.index', compact('agents', 'branches', 'stats'));
    }

    public function dashboard()
    {
        $stats = $this->service->getDashboardStats();
        $topAgents = SaccoAgent::active()
            ->withCount(['transactions as transaction_count' => function ($q) {
                $q->thisMonth()->whereIn('transaction_type', ['deposit', 'withdrawal']);
            }])
            ->orderByDesc('transaction_count')
            ->take(10)
            ->get();

        $recentTransactions = SaccoAgentTransaction::with(['agent', 'client'])
            ->orderByDesc('created_at')
            ->take(15)
            ->get();

        $lowFloatAgents = SaccoAgent::active()->lowFloat(5000)->get();

        return theme_view('sacco::agency.dashboard', compact('stats', 'topAgents', 'recentTransactions', 'lowFloatAgents'));
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        $chartOfAccounts = ChartOfAccount::active()->orderBy('gl_code')->get();
        return theme_view('sacco::agency.create', compact('branches', 'chartOfAccounts'));
    }

    public function store(Request $request)
    {
        $rules = [
            'agent_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'id_number' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'float_balance' => 'nullable|numeric|min:0',
            'float_limit' => 'nullable|numeric|min:0',
            'daily_transaction_limit' => 'nullable|numeric|min:0',
            'single_transaction_limit' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'branch_id' => 'nullable|exists:branches,id',
            'float_chart_of_account_id' => 'nullable|exists:chart_of_accounts,id',
            'commission_chart_of_account_id' => 'nullable|exists:chart_of_accounts,id',
            'create_user_account' => 'nullable|boolean',
            'pin' => 'nullable|digits:4',
        ];

        // If creating user account and no email provided, require password
        if ($request->create_user_account && empty($request->email)) {
            $rules['password'] = 'nullable|min:6';
        }

        $request->validate($rules);

        try {
            $agent = $this->service->createAgent($request->all());

            $message = 'Agent registered successfully.';
            if ($agent->user_id) {
                $user = User::find($agent->user_id);
                if ($user && isset($user->plain_password)) {
                    $message .= " Login credentials - Email: {$user->email}, Password: {$user->plain_password}";
                }
            }

            return redirect('sacco/agency')->with('success', $message);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $agent = SaccoAgent::with(['branch', 'createdBy', 'user', 'floatAccount', 'commissionAccount'])->findOrFail($id);
        $performance = $this->service->getAgentPerformance($agent, 'month');
        $recentTransactions = $agent->transactions()
            ->with(['client', 'savings'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return theme_view('sacco::agency.show', compact('agent', 'performance', 'recentTransactions'));
    }

    public function edit($id)
    {
        $agent = SaccoAgent::with(['user', 'floatAccount', 'commissionAccount'])->findOrFail($id);
        $branches = Branch::orderBy('name')->get();
        $chartOfAccounts = ChartOfAccount::active()->orderBy('gl_code')->get();
        $availableUsers = User::role('agent')->whereDoesntHave('saccoAgent')->orWhere('id', $agent->user_id)->orderBy('first_name')->get();
        return theme_view('sacco::agency.edit', compact('agent', 'branches', 'chartOfAccounts', 'availableUsers'));
    }

    public function update(Request $request, $id)
    {
        $agent = SaccoAgent::findOrFail($id);

        $request->validate([
            'agent_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'id_number' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'float_limit' => 'nullable|numeric|min:0',
            'daily_transaction_limit' => 'nullable|numeric|min:0',
            'single_transaction_limit' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'branch_id' => 'nullable|exists:branches,id',
            'float_chart_of_account_id' => 'nullable|exists:chart_of_accounts,id',
            'commission_chart_of_account_id' => 'nullable|exists:chart_of_accounts,id',
            'user_id' => 'nullable|exists:users,id',
        ]);

        try {
            // Handle user linking
            if ($request->has('user_id')) {
                $agent->user_id = $request->user_id ?: null;
                $agent->save();
            }

            // Create new user if requested
            if ($request->create_user_account && !$agent->user_id) {
                $user = $this->service->createUserAccount($agent->agent_code, [
                    'agent_name' => $agent->agent_name,
                    'email' => $request->email ?? $agent->email,
                    'phone' => $request->phone ?? $agent->phone,
                ]);
                $this->service->linkUserToAgent($agent, $user->id);
                session()->flash('info', "User account created. Email: {$user->email}, Password: {$user->plain_password}");
            }

            $this->service->updateAgent($agent, $request->all());
            return redirect("sacco/agency/{$id}/show")->with('success', 'Agent updated successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function transactions(Request $request)
    {
        $query = SaccoAgentTransaction::with(['agent', 'client', 'savings']);

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('agent_id')) {
            $query->forAgent($request->agent_id);
        }

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from') && $request->filled('to')) {
            $query->dateRange($request->from, $request->to);
        }

        $transactions = $query->orderByDesc('created_at')->paginate(30);
        $agents = SaccoAgent::orderBy('agent_name')->get();
        $summary = $this->service->getTransactionsSummary(
            $request->from,
            $request->to,
            $request->agent_id
        );

        return theme_view('sacco::agency.transactions', compact('transactions', 'agents', 'summary'));
    }

    public function topUp(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:sacco_agents,id',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $agent = SaccoAgent::findOrFail($request->agent_id);
            $this->service->topUpFloat($agent, (float)$request->amount, $request->notes);
            return back()->with('success', 'Float topped up successfully. New balance: ' . number_format($agent->fresh()->float_balance, 2));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function withdrawFloat(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:sacco_agents,id',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $agent = SaccoAgent::findOrFail($request->agent_id);
            $this->service->withdrawFloat($agent, (float)$request->amount, $request->notes);
            return back()->with('success', 'Float withdrawn successfully. New balance: ' . number_format($agent->fresh()->float_balance, 2));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function deposit(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:sacco_agents,id',
            'savings_id' => 'required|exists:savings,id',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $agent = SaccoAgent::findOrFail($request->agent_id);
            $savings = Savings::findOrFail($request->savings_id);
            $this->service->processDeposit($agent, $savings, (float)$request->amount, $request->notes);
            return back()->with('success', 'Agency deposit posted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function withdraw(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:sacco_agents,id',
            'savings_id' => 'required|exists:savings,id',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $agent = SaccoAgent::findOrFail($request->agent_id);
            $savings = Savings::findOrFail($request->savings_id);
            $this->service->processWithdrawal($agent, $savings, (float)$request->amount, $request->notes);
            return back()->with('success', 'Agency withdrawal posted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function suspend(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        try {
            $agent = SaccoAgent::findOrFail($id);
            $this->service->suspendAgent($agent, $request->reason);
            return back()->with('success', 'Agent suspended.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reactivate($id)
    {
        try {
            $agent = SaccoAgent::findOrFail($id);
            $this->service->reactivateAgent($agent);
            return back()->with('success', 'Agent reactivated.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function close($id)
    {
        try {
            $agent = SaccoAgent::findOrFail($id);
            $this->service->closeAgent($agent);
            return redirect('sacco/agency')->with('success', 'Agent account closed.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reverse(Request $request, $transactionId)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        try {
            $transaction = SaccoAgentTransaction::findOrFail($transactionId);
            $this->service->reverseTransaction($transaction, $request->reason);
            return back()->with('success', 'Transaction reversed.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function exportTransactions(Request $request)
    {
        $filters = $request->only(['from', 'to', 'agent_id', 'type', 'status']);
        $transactions = $this->service->exportTransactions($filters);

        $filename = 'agency_transactions_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Reference', 'Agent Code', 'Agent Name', 'Client', 'Type', 'Amount', 'Commission', 'Status', 'Date']);

            foreach ($transactions as $txn) {
                fputcsv($file, [
                    $txn->reference,
                    $txn->agent?->agent_code ?? 'N/A',
                    $txn->agent?->agent_name ?? 'N/A',
                    $txn->getClientName(),
                    $txn->formatted_type,
                    $txn->amount,
                    $txn->commission,
                    $txn->status,
                    $txn->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function ajaxAgentInfo($id)
    {
        $agent = SaccoAgent::find($id);

        if (!$agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        return response()->json([
            'id' => $agent->id,
            'code' => $agent->agent_code,
            'name' => $agent->agent_name,
            'float_balance' => $agent->float_balance,
            'status' => $agent->status,
            'commission_rate' => $agent->commission_rate,
        ]);
    }
}
