<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Client\Entities\Client;
use Modules\Core\Entities\PaymentDetail;
use Modules\Loan\Entities\Loan;
use Modules\Loan\Entities\LoanTransaction;
use Modules\Sacco\Entities\SaccoAgent;
use Modules\Sacco\Entities\SaccoAgentMemberRegistration;
use Modules\Sacco\Entities\SaccoAgentTransaction;
use Modules\Sacco\Entities\SaccoAgentUtilityPayment;
use Modules\Sacco\Entities\SaccoUtilityProvider;
use Modules\Savings\Entities\Savings;
use Modules\Savings\Entities\SavingsTransaction;

class AgentPortalService
{
    protected AgentCommissionService $commissionService;

    public function __construct(AgentCommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    public function generateReference(string $prefix = 'AGT'): string
    {
        return $prefix . date('ymdHis') . strtoupper(Str::random(4));
    }

    // ─── DEPOSIT ─────────────────────────────────────────────────────────────

    public function processDeposit(SaccoAgent $agent, array $data): array
    {
        $savings = Savings::with('savings_product', 'client')->findOrFail($data['savings_id']);
        $amount = (float)$data['amount'];

        $limitCheck = $agent->canProcessAmount($amount);
        if (!$limitCheck['allowed']) {
            return ['success' => false, 'message' => implode(' | ', $limitCheck['violations'])];
        }

        DB::beginTransaction();
        try {
            $reference = $this->generateReference('DEP');
            $floatBefore = (float)$agent->float_balance;

            $pd = new PaymentDetail();
            $pd->created_by_id = $agent->user_id;
            $pd->payment_type_id = $data['payment_type_id'] ?? 1;
            $pd->transaction_type = 'savings_transaction';
            $pd->receipt = $reference;
            $pd->notes = $data['notes'] ?? 'Agent deposit';
            $pd->save();

            $savingsTxn = new SavingsTransaction();
            $savingsTxn->created_by_id = $agent->user_id;
            $savingsTxn->savings_id = $savings->id;
            $savingsTxn->branch_id = $savings->branch_id;
            $savingsTxn->payment_detail_id = $pd->id;
            $savingsTxn->name = 'Agent Deposit';
            $savingsTxn->savings_transaction_type_id = 1;
            $savingsTxn->submitted_on = $data['date'] ?? date('Y-m-d');
            $savingsTxn->created_on = date('Y-m-d');
            $savingsTxn->reversible = 1;
            $savingsTxn->amount = $amount;
            $savingsTxn->credit = $amount;
            $savingsTxn->save();

            $agent->addFloat($amount);
            $floatAfter = (float)$agent->float_balance;

            $agentTxn = SaccoAgentTransaction::create([
                'sacco_agent_id' => $agent->id,
                'client_id' => $savings->client_id,
                'savings_id' => $savings->id,
                'transaction_type' => 'deposit',
                'amount' => $amount,
                'float_before' => $floatBefore,
                'float_after' => $floatAfter,
                'reference' => $reference,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ]);

            $commission = $this->commissionService->processTransactionCommission($agent, $agentTxn);

            $agent->last_transaction_at = now();
            $agent->save();

            DB::commit();

            activity()->log("Agent {$agent->agent_code} deposit: {$amount} to Savings #{$savings->id}");

            return [
                'success' => true,
                'message' => 'Deposit of ' . number_format($amount, 2) . ' processed successfully',
                'transaction_id' => $agentTxn->id,
                'savings_transaction_id' => $savingsTxn->id,
                'reference' => $reference,
                'commission' => $commission,
                'new_float_balance' => $floatAfter,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()];
        }
    }

    // ─── WITHDRAWAL ──────────────────────────────────────────────────────────

    public function processWithdrawal(SaccoAgent $agent, array $data): array
    {
        $savings = Savings::with('savings_product', 'client')->findOrFail($data['savings_id']);
        $amount = (float)$data['amount'];

        $limitCheck = $agent->canProcessAmount($amount);
        if (!$limitCheck['allowed']) {
            return ['success' => false, 'message' => implode(' | ', $limitCheck['violations'])];
        }

        if (!$agent->hasEnoughFloat($amount)) {
            return ['success' => false, 'message' => 'Insufficient float balance. Current: ' . number_format($agent->float_balance, 2)];
        }

        $balance = $savings->transactions()->where('reversed', 0)->sum('credit')
                 - $savings->transactions()->where('reversed', 0)->sum('debit');

        if ($amount > $balance && optional($savings->savings_product)->allow_overdraft == 0) {
            return ['success' => false, 'message' => 'Insufficient savings balance'];
        }

        DB::beginTransaction();
        try {
            $reference = $this->generateReference('WDR');
            $floatBefore = (float)$agent->float_balance;

            $pd = new PaymentDetail();
            $pd->created_by_id = $agent->user_id;
            $pd->payment_type_id = $data['payment_type_id'] ?? 1;
            $pd->transaction_type = 'savings_transaction';
            $pd->receipt = $reference;
            $pd->notes = $data['notes'] ?? 'Agent withdrawal';
            $pd->save();

            $savingsTxn = new SavingsTransaction();
            $savingsTxn->created_by_id = $agent->user_id;
            $savingsTxn->savings_id = $savings->id;
            $savingsTxn->branch_id = $savings->branch_id;
            $savingsTxn->payment_detail_id = $pd->id;
            $savingsTxn->name = 'Agent Withdrawal';
            $savingsTxn->savings_transaction_type_id = 2;
            $savingsTxn->submitted_on = $data['date'] ?? date('Y-m-d');
            $savingsTxn->created_on = date('Y-m-d');
            $savingsTxn->reversible = 1;
            $savingsTxn->amount = $amount;
            $savingsTxn->debit = $amount;
            $savingsTxn->save();

            $agent->deductFloat($amount);
            $floatAfter = (float)$agent->float_balance;

            $agentTxn = SaccoAgentTransaction::create([
                'sacco_agent_id' => $agent->id,
                'client_id' => $savings->client_id,
                'savings_id' => $savings->id,
                'transaction_type' => 'withdrawal',
                'amount' => $amount,
                'float_before' => $floatBefore,
                'float_after' => $floatAfter,
                'reference' => $reference,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ]);

            $commission = $this->commissionService->processTransactionCommission($agent, $agentTxn);

            $agent->last_transaction_at = now();
            $agent->save();

            DB::commit();

            activity()->log("Agent {$agent->agent_code} withdrawal: {$amount} from Savings #{$savings->id}");

            return [
                'success' => true,
                'message' => 'Withdrawal of ' . number_format($amount, 2) . ' processed successfully',
                'transaction_id' => $agentTxn->id,
                'savings_transaction_id' => $savingsTxn->id,
                'reference' => $reference,
                'commission' => $commission,
                'new_float_balance' => $floatAfter,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()];
        }
    }

    // ─── LOAN REPAYMENT ──────────────────────────────────────────────────────

    public function processLoanRepayment(SaccoAgent $agent, array $data): array
    {
        $loan = Loan::with('loan_product', 'client')->findOrFail($data['loan_id']);
        $amount = (float)$data['amount'];

        $limitCheck = $agent->canProcessAmount($amount);
        if (!$limitCheck['allowed']) {
            return ['success' => false, 'message' => implode(' | ', $limitCheck['violations'])];
        }

        DB::beginTransaction();
        try {
            $reference = $this->generateReference('LRP');
            $floatBefore = (float)$agent->float_balance;

            $pd = new PaymentDetail();
            $pd->created_by_id = $agent->user_id;
            $pd->payment_type_id = $data['payment_type_id'] ?? 1;
            $pd->transaction_type = 'loan_transaction';
            $pd->receipt = $reference;
            $pd->notes = $data['notes'] ?? 'Agent loan repayment';
            $pd->save();

            $loanTxn = new LoanTransaction();
            $loanTxn->created_by_id = $agent->user_id;
            $loanTxn->loan_id = $loan->id;
            $loanTxn->payment_detail_id = $pd->id;
            $loanTxn->name = 'Agent Repayment';
            $loanTxn->loan_transaction_type_id = 2;
            $loanTxn->submitted_on = $data['date'] ?? date('Y-m-d');
            $loanTxn->created_on = date('Y-m-d');
            $loanTxn->amount = $amount;
            $loanTxn->credit = $amount;
            $loanTxn->save();

            $agent->addFloat($amount);
            $floatAfter = (float)$agent->float_balance;

            $agentTxn = SaccoAgentTransaction::create([
                'sacco_agent_id' => $agent->id,
                'client_id' => $loan->client_id,
                'loan_id' => $loan->id,
                'transaction_type' => 'loan_repayment',
                'amount' => $amount,
                'float_before' => $floatBefore,
                'float_after' => $floatAfter,
                'reference' => $reference,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ]);

            $commission = $this->commissionService->processTransactionCommission($agent, $agentTxn);

            $agent->last_transaction_at = now();
            $agent->save();

            if (class_exists('\Modules\Loan\Events\TransactionUpdated')) {
                event(new \Modules\Loan\Events\TransactionUpdated($loan));
            }

            DB::commit();

            activity()->log("Agent {$agent->agent_code} loan repayment: {$amount} for Loan #{$loan->id}");

            return [
                'success' => true,
                'message' => 'Loan repayment of ' . number_format($amount, 2) . ' processed successfully',
                'transaction_id' => $agentTxn->id,
                'loan_transaction_id' => $loanTxn->id,
                'reference' => $reference,
                'commission' => $commission,
                'new_float_balance' => $floatAfter,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()];
        }
    }

    // ─── UTILITY PAYMENT ─────────────────────────────────────────────────────

    public function processUtilityPayment(SaccoAgent $agent, array $data): array
    {
        $provider = SaccoUtilityProvider::findOrFail($data['provider_id']);
        $amount = (float)$data['amount'];
        $fee = (float)($data['fee'] ?? 0);
        $total = $amount + $fee;

        $limitCheck = $agent->canProcessAmount($total);
        if (!$limitCheck['allowed']) {
            return ['success' => false, 'message' => implode(' | ', $limitCheck['violations'])];
        }

        if (!$agent->hasEnoughFloat($total)) {
            return ['success' => false, 'message' => 'Insufficient float balance for this payment'];
        }

        DB::beginTransaction();
        try {
            $reference = $this->generateReference('UTL');
            $commission = $this->commissionService->calculateCommission('utility', $amount, $agent);

            $payment = SaccoAgentUtilityPayment::create([
                'sacco_agent_id' => $agent->id,
                'client_id' => $data['client_id'] ?? null,
                'provider_id' => $provider->id,
                'account_number' => $data['account_number'],
                'customer_name' => $data['customer_name'] ?? null,
                'amount' => $amount,
                'fee' => $fee,
                'commission' => $commission,
                'reference' => $reference,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ]);

            $agent->deductFloat($total);

            $agentTxn = SaccoAgentTransaction::create([
                'sacco_agent_id' => $agent->id,
                'client_id' => $data['client_id'] ?? null,
                'utility_payment_id' => $payment->id,
                'transaction_type' => 'utility',
                'amount' => $total,
                'commission' => $commission,
                'reference' => $reference,
                'status' => 'completed',
                'notes' => "Utility payment to {$provider->name}: {$data['account_number']}",
            ]);

            if ($commission > 0) {
                $agent->addCommission($commission, $agentTxn->id, "Utility payment commission - {$provider->name}");
            }

            $agent->last_transaction_at = now();
            $agent->save();

            DB::commit();

            activity()->log("Agent {$agent->agent_code} utility payment: {$total} to {$provider->name}");

            return [
                'success' => true,
                'message' => 'Utility payment of ' . number_format($amount, 2) . ' processed successfully',
                'payment_id' => $payment->id,
                'reference' => $reference,
                'commission' => $commission,
                'new_float_balance' => (float)$agent->float_balance,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Payment failed: ' . $e->getMessage()];
        }
    }

    // ─── MEMBER REGISTRATION ─────────────────────────────────────────────────

    public function registerMember(SaccoAgent $agent, array $data): array
    {
        DB::beginTransaction();
        try {
            $client = new Client();
            $client->first_name = $data['first_name'];
            $client->middle_name = $data['middle_name'] ?? null;
            $client->last_name = $data['last_name'];
            $client->mobile = $data['mobile'];
            $client->email = $data['email'] ?? null;
            $client->dob = $data['dob'] ?? null;
            $client->gender = $data['gender'] ?? null;
            $client->address = $data['address'] ?? null;
            $client->branch_id = $agent->branch_id;
            $client->status = 'pending';
            $client->created_by_id = $agent->user_id;
            $client->save();

            $registrationFee = (float)($data['registration_fee'] ?? 0);
            $commission = $this->commissionService->calculateCommission('registration', $registrationFee, $agent);

            $registration = SaccoAgentMemberRegistration::create([
                'sacco_agent_id' => $agent->id,
                'client_id' => $client->id,
                'registration_fee' => $registrationFee,
                'commission_earned' => $commission,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            $agentTxn = SaccoAgentTransaction::create([
                'sacco_agent_id' => $agent->id,
                'client_id' => $client->id,
                'member_registration_id' => $registration->id,
                'transaction_type' => 'registration',
                'amount' => $registrationFee,
                'commission' => 0, // Commission is pending approval
                'reference' => $this->generateReference('REG'),
                'status' => 'pending',
                'notes' => "New member registration: {$client->first_name} {$client->last_name}",
            ]);

            $agent->last_transaction_at = now();
            $agent->save();

            DB::commit();

            activity()->log("Agent {$agent->agent_code} registered new member: {$client->first_name} {$client->last_name}");

            return [
                'success' => true,
                'message' => 'Member registration submitted successfully. Pending approval.',
                'client_id' => $client->id,
                'registration_id' => $registration->id,
                'pending_commission' => $commission,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    // ─── MEMBER LOOKUP ───────────────────────────────────────────────────────

    public function searchMembers(string $query, ?int $branchId = null, int $limit = 20): array
    {
        $clients = Client::query()
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('mobile', 'like', "%{$query}%")
                  ->orWhere('account_number', 'like', "%{$query}%")
                  ->orWhere('member_number', 'like', "%{$query}%");
            })
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('status', '!=', 'closed')
            ->limit($limit)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->first_name . ' ' . $c->last_name,
                'phone' => $c->mobile,
                'member_number' => $c->member_number ?? $c->account_number ?? 'CLT-' . $c->id,
                'status' => $c->status,
            ]);

        return $clients->toArray();
    }

    public function getMemberDetails(int $clientId): ?array
    {
        $client = Client::with(['savings.savings_product', 'loans.loan_product'])->find($clientId);
        
        if (!$client) {
            return null;
        }

        $savings = $client->savings
            ->where('status', 'active')
            ->map(fn($s) => [
                'id' => $s->id,
                'product' => optional($s->savings_product)->name,
                'balance' => $this->getSavingsBalance($s),
                'account_no' => $s->account_number ?? 'SAV-' . $s->id,
            ]);

        $loans = $client->loans
            ->where('status', 'active')
            ->map(fn($l) => [
                'id' => $l->id,
                'product' => optional($l->loan_product)->name,
                'outstanding' => (float)($l->total_outstanding ?? 0),
                'account_no' => 'LN-' . $l->id,
            ]);

        return [
            'client' => [
                'id' => $client->id,
                'name' => $client->first_name . ' ' . $client->last_name,
                'phone' => $client->mobile,
                'member_number' => $client->member_number ?? $client->account_number ?? 'CLT-' . $client->id,
                'photo' => $client->photo ? url('storage/' . $client->photo) : null,
            ],
            'savings' => $savings->values()->toArray(),
            'loans' => $loans->values()->toArray(),
        ];
    }

    protected function getSavingsBalance(Savings $savings): float
    {
        return $savings->transactions()->where('reversed', 0)->sum('credit')
             - $savings->transactions()->where('reversed', 0)->sum('debit');
    }
}
