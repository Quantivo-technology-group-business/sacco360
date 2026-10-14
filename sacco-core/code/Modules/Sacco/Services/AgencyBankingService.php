<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Accounting\Entities\JournalEntry;
use Modules\Sacco\Entities\SaccoAgent;
use Modules\Sacco\Entities\SaccoAgentTransaction;
use Modules\Savings\Entities\Savings;
use Modules\User\Entities\User;
use Modules\User\Entities\Role;

class AgencyBankingService
{
    public function createAgent(array $data): SaccoAgent
    {
        return DB::transaction(function () use ($data) {
            $agentCode = $data['agent_code'] ?? $this->generateAgentCode();
            $agentNumber = $data['agent_number'] ?? $this->generateAgentNumber();
            $userId = null;

            // Create user account if requested
            if (!empty($data['create_user_account'])) {
                $user = $this->createUserAccount($agentCode, $data);
                $userId = $user->id;
            }

            $agent = SaccoAgent::create([
                'user_id' => $userId,
                'created_by_id' => Auth::id(),
                'branch_id' => $data['branch_id'] ?? null,
                'float_chart_of_account_id' => $data['float_chart_of_account_id'] ?? null,
                'commission_chart_of_account_id' => $data['commission_chart_of_account_id'] ?? null,
                'agent_code' => $agentCode,
                'agent_number' => $agentNumber,
                'agent_name' => $data['agent_name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'id_number' => $data['id_number'] ?? null,
                'location' => $data['location'] ?? null,
                'address' => $data['address'] ?? null,
                'float_balance' => $data['float_balance'] ?? 0,
                'float_limit' => $data['float_limit'] ?? 100000,
                'daily_transaction_limit' => $data['daily_transaction_limit'] ?? 500000,
                'single_transaction_limit' => $data['single_transaction_limit'] ?? 100000,
                'commission_rate' => $data['commission_rate'] ?? 0.5,
                'commission_tier' => $data['commission_tier'] ?? null,
                'status' => 'active',
                'kyc_status' => 'incomplete',
                'activated_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            // Set initial PIN if provided
            if (!empty($data['pin'])) {
                $agent->setPin($data['pin']);
            }

            return $agent;
        });
    }

    protected function generateAgentNumber(): string
    {
        do {
            $number = str_pad(mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        } while (SaccoAgent::where('agent_number', $number)->exists());

        return $number;
    }

    public function createUserAccount(string $agentCode, array $data): User
    {
        $email = $data['email'] ?? strtolower($agentCode) . '@agent.local';
        $password = $data['password'] ?? $this->generateRandomPassword();

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            throw new \RuntimeException("A user with email {$email} already exists.");
        }

        $nameParts = explode(' ', $data['agent_name'], 2);
        $firstName = $nameParts[0] ?? $agentCode;
        $lastName = $nameParts[1] ?? '';

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);

        // Assign agent role
        $role = Role::where('name', 'agent')->first();
        if ($role) {
            $user->assignRole($role);
        }

        // Store original password temporarily for display (not persisted)
        $user->plain_password = $password;

        return $user;
    }

    protected function generateRandomPassword(): string
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    }

    public function linkUserToAgent(SaccoAgent $agent, int $userId): SaccoAgent
    {
        $agent->update(['user_id' => $userId]);
        return $agent;
    }

    public function updateAgent(SaccoAgent $agent, array $data): SaccoAgent
    {
        $updateData = [
            'branch_id' => $data['branch_id'] ?? $agent->branch_id,
            'agent_name' => $data['agent_name'] ?? $agent->agent_name,
            'phone' => $data['phone'] ?? $agent->phone,
            'email' => $data['email'] ?? $agent->email,
            'id_number' => $data['id_number'] ?? $agent->id_number,
            'location' => $data['location'] ?? $agent->location,
            'address' => $data['address'] ?? $agent->address,
            'float_limit' => $data['float_limit'] ?? $agent->float_limit,
            'daily_transaction_limit' => $data['daily_transaction_limit'] ?? $agent->daily_transaction_limit,
            'single_transaction_limit' => $data['single_transaction_limit'] ?? $agent->single_transaction_limit,
            'commission_rate' => $data['commission_rate'] ?? $agent->commission_rate,
            'commission_tier' => $data['commission_tier'] ?? $agent->commission_tier,
            'notes' => $data['notes'] ?? $agent->notes,
        ];

        // Update COA if provided
        if (array_key_exists('float_chart_of_account_id', $data)) {
            $updateData['float_chart_of_account_id'] = $data['float_chart_of_account_id'];
        }
        if (array_key_exists('commission_chart_of_account_id', $data)) {
            $updateData['commission_chart_of_account_id'] = $data['commission_chart_of_account_id'];
        }

        $agent->update($updateData);

        return $agent;
    }

    public function suspendAgent(SaccoAgent $agent, string $reason): SaccoAgent
    {
        $agent->update([
            'status' => 'suspended',
            'suspended_reason' => $reason,
        ]);

        return $agent;
    }

    public function reactivateAgent(SaccoAgent $agent): SaccoAgent
    {
        $agent->update([
            'status' => 'active',
            'suspended_reason' => null,
            'activated_at' => now(),
        ]);

        return $agent;
    }

    public function closeAgent(SaccoAgent $agent): SaccoAgent
    {
        if ($agent->float_balance > 0) {
            throw new \RuntimeException('Cannot close agent with remaining float balance. Please withdraw the float first.');
        }

        $agent->update(['status' => 'closed']);

        return $agent;
    }

    public function topUpFloat(SaccoAgent $agent, float $amount, ?string $notes = null): SaccoAgentTransaction
    {
        $newBalance = $agent->float_balance + $amount;

        if ($agent->float_limit && $newBalance > $agent->float_limit) {
            throw new \RuntimeException(
                "Float top-up would exceed agent limit. Current: {$agent->float_balance}, Limit: {$agent->float_limit}"
            );
        }

        return DB::transaction(function () use ($agent, $amount, $notes) {
            $floatBefore = $agent->float_balance;
            $agent->float_balance += $amount;
            $agent->save();

            $transaction = $this->logTransaction($agent, null, null, 'float_topup', $amount, 0, $notes, $floatBefore, $agent->float_balance);

            // Post to GL if agent has a linked float account
            $this->postFloatJournalEntry($agent, $amount, 'topup', $transaction->reference, $notes);

            return $transaction;
        });
    }

    public function withdrawFloat(SaccoAgent $agent, float $amount, ?string $notes = null): SaccoAgentTransaction
    {
        if ($agent->float_balance < $amount) {
            throw new \RuntimeException('Insufficient float balance');
        }

        return DB::transaction(function () use ($agent, $amount, $notes) {
            $floatBefore = $agent->float_balance;
            $agent->float_balance -= $amount;
            $agent->save();

            $transaction = $this->logTransaction($agent, null, null, 'float_withdrawal', $amount, 0, $notes, $floatBefore, $agent->float_balance);

            // Post to GL if agent has a linked float account
            $this->postFloatJournalEntry($agent, $amount, 'withdrawal', $transaction->reference, $notes);

            return $transaction;
        });
    }

    protected function postFloatJournalEntry(SaccoAgent $agent, float $amount, string $type, string $reference, ?string $notes = null): void
    {
        if (!$agent->float_chart_of_account_id) {
            return;
        }

        $description = $type === 'topup'
            ? "Agent float top-up: {$agent->agent_name} ({$agent->agent_code})"
            : "Agent float withdrawal: {$agent->agent_name} ({$agent->agent_code})";

        if ($notes) {
            $description .= " - {$notes}";
        }

        // For float top-up: Debit agent float account (asset increases)
        // For float withdrawal: Credit agent float account (asset decreases)
        JournalEntry::create([
            'created_by_id' => Auth::id(),
            'branch_id' => $agent->branch_id,
            'currency_id' => 1,
            'chart_of_account_id' => $agent->float_chart_of_account_id,
            'transaction_type' => 'agent_float',
            'reference' => $reference,
            'debit' => $type === 'topup' ? $amount : 0,
            'credit' => $type === 'withdrawal' ? $amount : 0,
            'date' => now()->format('Y-m-d'),
            'notes' => $description,
        ]);
    }

    public function processDeposit(SaccoAgent $agent, Savings $savings, float $amount, ?string $notes = null): SaccoAgentTransaction
    {
        if ($agent->status !== 'active') {
            throw new \RuntimeException('Agent is not active');
        }

        $wing = (new WingService())->getAccountWing($savings);
        if ($wing !== WingService::WING_FOSA) {
            throw new \RuntimeException('Agency deposits are only permitted to FOSA accounts');
        }

        return DB::transaction(function () use ($agent, $savings, $amount, $notes) {
            $commission = $this->calculateCommission($agent, $amount, 'deposit');
            $agent->float_balance += $amount;
            $agent->last_transaction_at = now();
            $agent->save();

            $reference = 'AG-DEP-' . strtoupper(uniqid());
            (new SavingsChannelService())->postDeposit($savings, $amount, 'agency_banking', $reference);

            return $this->logTransaction($agent, $savings->client_id, $savings->id, 'deposit', $amount, $commission, $notes);
        });
    }

    public function processWithdrawal(SaccoAgent $agent, Savings $savings, float $amount, ?string $notes = null): SaccoAgentTransaction
    {
        if ($agent->status !== 'active') {
            throw new \RuntimeException('Agent is not active');
        }

        if ($agent->float_balance < $amount) {
            throw new \RuntimeException('Insufficient agent float');
        }

        $wing = (new WingService())->getAccountWing($savings);
        if ($wing !== WingService::WING_FOSA) {
            throw new \RuntimeException('Agency withdrawals are only permitted from FOSA accounts');
        }

        return DB::transaction(function () use ($agent, $savings, $amount, $notes) {
            $commission = $this->calculateCommission($agent, $amount, 'withdrawal');
            $reference = 'AG-WDL-' . strtoupper(uniqid());
            (new SavingsChannelService())->postWithdrawal($savings, $amount, 'agency_banking', $reference);

            $agent->float_balance -= $amount;
            $agent->last_transaction_at = now();
            $agent->save();

            return $this->logTransaction($agent, $savings->client_id, $savings->id, 'withdrawal', $amount, $commission, $notes);
        });
    }

    public function reverseTransaction(SaccoAgentTransaction $transaction, string $reason): SaccoAgentTransaction
    {
        if (!$transaction->isReversible()) {
            throw new \RuntimeException('This transaction cannot be reversed');
        }

        return DB::transaction(function () use ($transaction, $reason) {
            $agent = $transaction->agent;

            if ($transaction->transaction_type === 'deposit') {
                $agent->float_balance -= $transaction->amount;
            } elseif ($transaction->transaction_type === 'withdrawal') {
                $agent->float_balance += $transaction->amount;
            }
            $agent->save();

            $transaction->update([
                'status' => 'reversed',
                'reversed_at' => now(),
                'reversed_by_id' => Auth::id(),
                'reversal_reason' => $reason,
            ]);

            return $transaction;
        });
    }

    protected function calculateCommission(SaccoAgent $agent, float $amount, string $type): float
    {
        return round($amount * ($agent->commission_rate / 100), 2);
    }

    protected function logTransaction(
        SaccoAgent $agent,
        ?int $clientId,
        ?int $savingsId,
        string $type,
        float $amount,
        float $commission,
        ?string $notes = null,
        ?float $floatBefore = null,
        ?float $floatAfter = null
    ): SaccoAgentTransaction {
        $txn = new SaccoAgentTransaction();
        $txn->sacco_agent_id = $agent->id;
        $txn->client_id = $clientId;
        $txn->savings_id = $savingsId;
        $txn->transaction_type = $type;
        $txn->amount = $amount;
        $txn->commission = $commission;
        $txn->reference = $this->generateReference($type);
        $txn->status = 'completed';
        $txn->notes = $notes;
        $txn->float_before = $floatBefore;
        $txn->float_after = $floatAfter;
        $txn->save();

        return $txn;
    }

    protected function generateAgentCode(): string
    {
        $prefix = 'AG';
        $year = date('y');
        $lastAgent = SaccoAgent::orderByDesc('id')->first();
        $sequence = $lastAgent ? ($lastAgent->id + 1) : 1;

        return $prefix . $year . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }

    protected function generateReference(string $type): string
    {
        $prefixes = [
            'deposit' => 'AGD',
            'withdrawal' => 'AGW',
            'float_topup' => 'AGF',
            'float_withdrawal' => 'AGX',
            'commission' => 'AGC',
        ];

        $prefix = $prefixes[$type] ?? 'AGT';

        return $prefix . '-' . strtoupper(uniqid()) . '-' . date('ymd');
    }

    // ─── Dashboard & Reporting ──────────────────────────────────────────────

    public function getDashboardStats(): array
    {
        $today = today();
        $monthStart = now()->startOfMonth();

        $agents = SaccoAgent::query();
        $transactions = SaccoAgentTransaction::query();

        return [
            'total_agents' => $agents->count(),
            'active_agents' => (clone $agents)->active()->count(),
            'suspended_agents' => (clone $agents)->suspended()->count(),
            'total_float' => (clone $agents)->sum('float_balance'),
            'today_transactions' => (clone $transactions)->today()->count(),
            'today_volume' => (clone $transactions)->today()
                ->whereIn('transaction_type', ['deposit', 'withdrawal'])
                ->sum('amount'),
            'today_deposits' => (clone $transactions)->today()->deposits()->sum('amount'),
            'today_withdrawals' => (clone $transactions)->today()->withdrawals()->sum('amount'),
            'today_commissions' => (clone $transactions)->today()->sum('commission'),
            'month_transactions' => (clone $transactions)->thisMonth()->count(),
            'month_volume' => (clone $transactions)->thisMonth()
                ->whereIn('transaction_type', ['deposit', 'withdrawal'])
                ->sum('amount'),
            'month_commissions' => (clone $transactions)->thisMonth()->sum('commission'),
            'low_float_agents' => (clone $agents)->active()->lowFloat(5000)->count(),
        ];
    }

    public function getAgentPerformance(SaccoAgent $agent, ?string $period = 'month'): array
    {
        $query = $agent->transactions();

        switch ($period) {
            case 'today':
                $query->today();
                break;
            case 'week':
                $query->where('created_at', '>=', now()->startOfWeek());
                break;
            case 'month':
            default:
                $query->thisMonth();
                break;
            case 'year':
                $query->whereYear('created_at', now()->year);
                break;
        }

        $transactions = $query->get();

        return [
            'total_transactions' => $transactions->count(),
            'deposits_count' => $transactions->where('transaction_type', 'deposit')->count(),
            'withdrawals_count' => $transactions->where('transaction_type', 'withdrawal')->count(),
            'deposits_amount' => $transactions->where('transaction_type', 'deposit')->sum('amount'),
            'withdrawals_amount' => $transactions->where('transaction_type', 'withdrawal')->sum('amount'),
            'total_volume' => $transactions->whereIn('transaction_type', ['deposit', 'withdrawal'])->sum('amount'),
            'commissions_earned' => $transactions->sum('commission'),
            'float_topups' => $transactions->where('transaction_type', 'float_topup')->sum('amount'),
        ];
    }

    public function getTransactionsSummary(?string $from = null, ?string $to = null, ?int $agentId = null): array
    {
        $query = SaccoAgentTransaction::with('agent');

        if ($from && $to) {
            $query->dateRange($from, $to);
        } else {
            $query->thisMonth();
        }

        if ($agentId) {
            $query->forAgent($agentId);
        }

        $transactions = $query->get();

        return [
            'total_count' => $transactions->count(),
            'deposits' => [
                'count' => $transactions->where('transaction_type', 'deposit')->count(),
                'amount' => $transactions->where('transaction_type', 'deposit')->sum('amount'),
            ],
            'withdrawals' => [
                'count' => $transactions->where('transaction_type', 'withdrawal')->count(),
                'amount' => $transactions->where('transaction_type', 'withdrawal')->sum('amount'),
            ],
            'float_topups' => [
                'count' => $transactions->where('transaction_type', 'float_topup')->count(),
                'amount' => $transactions->where('transaction_type', 'float_topup')->sum('amount'),
            ],
            'total_commissions' => $transactions->sum('commission'),
            'completed' => $transactions->where('status', 'completed')->count(),
            'reversed' => $transactions->where('status', 'reversed')->count(),
        ];
    }

    public function exportTransactions(array $filters = []): \Illuminate\Support\Collection
    {
        $query = SaccoAgentTransaction::with(['agent', 'client', 'savings']);

        if (!empty($filters['from']) && !empty($filters['to'])) {
            $query->dateRange($filters['from'], $filters['to']);
        }

        if (!empty($filters['agent_id'])) {
            $query->forAgent($filters['agent_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('transaction_type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('created_at')->get();
    }
}
