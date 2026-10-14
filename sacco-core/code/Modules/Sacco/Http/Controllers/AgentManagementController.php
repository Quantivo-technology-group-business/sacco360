<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Branch\Entities\Branch;
use Modules\Sacco\Entities\SaccoAgent;
use Modules\Sacco\Entities\SaccoAgentCommissionRate;
use Modules\Sacco\Entities\SaccoAgentFloatRequest;
use Modules\User\Entities\User;
use Spatie\Permission\Models\Role;

class AgentManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.agency.index']);
    }

    public function index(Request $request)
    {
        $query = SaccoAgent::with(['branch', 'user']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $agents = $query->orderBy('created_at', 'desc')->paginate(25);
        $branches = Branch::where('active', 1)->get();

        return theme_view('sacco::agency.index', compact('agents', 'branches'));
    }

    public function create()
    {
        $branches = Branch::where('active', 1)->get();
        $commissionRates = SaccoAgentCommissionRate::active()->get()->groupBy('transaction_type');

        return theme_view('sacco::agency.create', compact('branches', 'commissionRates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'agent_name' => 'required|string|max:100',
            'phone' => 'required|string|max:20|unique:sacco_agents,phone',
            'email' => 'nullable|email|max:100',
            'id_number' => 'nullable|string|max:50',
            'branch_id' => 'required|exists:branches,id',
            'location' => 'nullable|string|max:200',
            'address' => 'nullable|string|max:500',
            'float_limit' => 'required|numeric|min:1000',
            'initial_float' => 'nullable|numeric|min:0',
            'daily_transaction_limit' => 'nullable|numeric|min:0',
            'single_transaction_limit' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'create_user_account' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            $agentCode = $this->generateAgentCode();

            $agent = new SaccoAgent();
            $agent->created_by_id = Auth::id();
            $agent->branch_id = $request->branch_id;
            $agent->agent_code = $agentCode;
            $agent->agent_name = $request->agent_name;
            $agent->phone = $request->phone;
            $agent->email = $request->email;
            $agent->id_number = $request->id_number;
            $agent->location = $request->location;
            $agent->address = $request->address;
            $agent->float_balance = $request->initial_float ?? 0;
            $agent->float_limit = $request->float_limit;
            $agent->daily_transaction_limit = $request->daily_transaction_limit ?? 500000;
            $agent->single_transaction_limit = $request->single_transaction_limit ?? 100000;
            $agent->commission_rate = $request->commission_rate ?? 0;
            $agent->status = 'active';
            $agent->activated_at = now();

            if ($request->create_user_account) {
                $user = $this->createUserAccount($request, $agentCode);
                $agent->user_id = $user->id;
                
                $pin = $this->generatePin();
                $agent->pin = Hash::make($pin);
            }

            $agent->save();

            DB::commit();

            activity()->log("Agent created: {$agent->agent_code} - {$agent->agent_name}");

            $message = 'Agent created successfully. Agent Code: ' . $agentCode;
            if (isset($pin)) {
                $message .= '. Initial PIN: ' . $pin . '. Please share securely with the agent.';
            }

            flash($message)->success();
            return redirect()->url('sacco/agency');
        } catch (\Exception $e) {
            DB::rollBack();
            flash('Failed to create agent: ' . $e->getMessage())->error();
            return back()->withInput();
        }
    }

    public function show($id)
    {
        $agent = SaccoAgent::with(['branch', 'user', 'transactions' => function ($q) {
            $q->orderBy('created_at', 'desc')->limit(20);
        }])->findOrFail($id);

        $stats = $agent->getDashboardStats();
        $monthlyStats = $agent->getMonthlyStats();

        return theme_view('sacco::agency.show', compact('agent', 'stats', 'monthlyStats'));
    }

    public function suspend(Request $request, $id)
    {
        $agent = SaccoAgent::findOrFail($id);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $agent->status = 'suspended';
        $agent->suspended_reason = $request->reason;
        $agent->save();

        activity()->log("Agent suspended: {$agent->agent_code} - Reason: {$request->reason}");

        flash('Agent suspended successfully.')->warning();
        return back();
    }

    public function activate($id)
    {
        $agent = SaccoAgent::findOrFail($id);

        $agent->status = 'active';
        $agent->suspended_reason = null;
        if (!$agent->activated_at) {
            $agent->activated_at = now();
        }
        $agent->save();

        activity()->log("Agent activated: {$agent->agent_code}");

        flash('Agent activated successfully.')->success();
        return back();
    }

    public function topUpFloat(Request $request, $id)
    {
        $agent = SaccoAgent::findOrFail($id);

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $amount = (float)$request->amount;
        $newBalance = (float)$agent->float_balance + $amount;

        if ($agent->float_limit && $newBalance > $agent->float_limit) {
            flash('Float top-up would exceed the agent\'s limit of ' . number_format($agent->float_limit, 2))->error();
            return back();
        }

        $agent->float_balance = $newBalance;
        $agent->save();

        activity()->log("Float topped up for agent {$agent->agent_code}: +{$amount}");

        flash('Float topped up by ' . number_format($amount, 2) . '. New balance: ' . number_format($newBalance, 2))->success();
        return back();
    }

    public function resetPin($id)
    {
        $agent = SaccoAgent::findOrFail($id);

        $newPin = $this->generatePin();
        $agent->setPin($newPin);

        activity()->log("PIN reset for agent {$agent->agent_code}");

        flash('PIN reset successfully. New PIN: ' . $newPin . '. Please share securely with the agent.')->success();
        return back();
    }

    public function unlockAgent($id)
    {
        $agent = SaccoAgent::findOrFail($id);
        $agent->unlock();

        activity()->log("Agent unlocked: {$agent->agent_code}");

        flash('Agent account unlocked successfully.')->success();
        return back();
    }

    public function commissionRates()
    {
        $rates = SaccoAgentCommissionRate::orderBy('transaction_type')->orderBy('min_amount')->get();

        return theme_view('sacco::agency.commission_rates', compact('rates'));
    }

    public function storeCommissionRate(Request $request)
    {
        $request->validate([
            'transaction_type' => 'required|string|max:50',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0',
            'rate_type' => 'required|in:percentage,flat',
            'rate_value' => 'required|numeric|min:0',
            'min_commission' => 'nullable|numeric|min:0',
            'max_commission' => 'nullable|numeric|min:0',
        ]);

        SaccoAgentCommissionRate::create($request->all());

        flash('Commission rate created successfully.')->success();
        return back();
    }

    public function updateCommissionRate(Request $request, $id)
    {
        $rate = SaccoAgentCommissionRate::findOrFail($id);

        $request->validate([
            'rate_value' => 'required|numeric|min:0',
            'min_commission' => 'nullable|numeric|min:0',
            'max_commission' => 'nullable|numeric|min:0',
            'active' => 'nullable|boolean',
        ]);

        $rate->update($request->only(['rate_value', 'min_commission', 'max_commission', 'active']));

        flash('Commission rate updated successfully.')->success();
        return back();
    }

    protected function generateAgentCode(): string
    {
        $prefix = 'AGT';
        $count = SaccoAgent::count() + 1;
        return $prefix . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    protected function generatePin(): string
    {
        return str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    protected function createUserAccount(Request $request, string $agentCode): User
    {
        $password = Str::random(12);

        $user = User::create([
            'first_name' => explode(' ', $request->agent_name)[0],
            'last_name' => count(explode(' ', $request->agent_name)) > 1 
                ? implode(' ', array_slice(explode(' ', $request->agent_name), 1)) 
                : '',
            'name' => $request->agent_name,
            'email' => $request->email ?? $agentCode . '@agent.local',
            'username' => strtolower($agentCode),
            'password' => Hash::make($password),
            'phone' => $request->phone,
            'branch_id' => $request->branch_id,
        ]);

        $agentRole = Role::where('name', 'agent')->first();
        if ($agentRole) {
            $user->assignRole($agentRole);
        }

        return $user;
    }
}
