<?php

namespace Modules\Sacco\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Client\Entities\Client;
use Modules\Sacco\Entities\SaccoAgent;
use Modules\Sacco\Entities\SaccoAgentTransaction;
use Modules\Savings\Entities\Savings;
use Modules\Savings\Entities\SavingsTransaction;

class AgencyApiController extends Controller
{
    /**
     * Find agent by agent number for customer-initiated withdrawal
     * Public endpoint - no auth required for lookup
     */
    public function findAgent($agentNumber)
    {
        $agent = SaccoAgent::where('agent_number', $agentNumber)
            ->orWhere('agent_code', $agentNumber)
            ->first();

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Agent not found',
            ], 404);
        }

        if ($agent->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Agent is not active',
            ], 400);
        }

        if ($agent->kyc_status !== 'verified') {
            return response()->json([
                'success' => false,
                'message' => 'Agent KYC is not verified',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'agent' => [
                'agent_number' => $agent->agent_number,
                'agent_code' => $agent->agent_code,
                'agent_name' => $agent->agent_name,
                'location' => $agent->location,
                'branch' => $agent->branch?->name,
                'can_withdraw' => $agent->float_balance > 0,
                'max_withdrawal' => min(
                    (float)$agent->float_balance,
                    (float)$agent->single_transaction_limit
                ),
            ],
        ]);
    }

    /**
     * Initiate a customer withdrawal request at an agent
     * Requires authenticated member
     */
    public function initiateWithdrawal(Request $request)
    {
        $request->validate([
            'agent_number' => 'required|string',
            'savings_id' => 'required|exists:savings,id',
            'amount' => 'required|numeric|min:1',
        ]);

        // Get the agent
        $agent = SaccoAgent::where('agent_number', $request->agent_number)
            ->orWhere('agent_code', $request->agent_number)
            ->first();

        if (!$agent || $agent->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive agent',
            ], 400);
        }

        if ($agent->kyc_status !== 'verified') {
            return response()->json([
                'success' => false,
                'message' => 'Agent KYC not verified',
            ], 400);
        }

        // Get the savings account and verify ownership
        $savings = Savings::find($request->savings_id);
        
        // Get the client for this user
        $client = $this->getClientForUser(Auth::user());
        if (!$client || $savings->client_id !== $client->id) {
            return response()->json([
                'success' => false,
                'message' => 'Savings account not found or unauthorized',
            ], 403);
        }

        $amount = (float)$request->amount;

        // Check agent float
        if ($agent->float_balance < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Agent has insufficient float. Please try a lower amount or another agent.',
                'available_float' => $agent->float_balance,
            ], 400);
        }

        // Check agent limits
        $limitCheck = $agent->canProcessAmount($amount);
        if (!$limitCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => implode('. ', $limitCheck['violations']),
            ], 400);
        }

        // Check savings balance
        $savingsBalance = (float)($savings->balance ?? 0);
        if ($savingsBalance < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient savings balance',
                'available_balance' => $savingsBalance,
            ], 400);
        }

        // Generate withdrawal token/code
        $withdrawalCode = strtoupper(substr(md5(uniqid()), 0, 8));

        // Store pending withdrawal request (could use cache or a dedicated table)
        $pendingKey = "agency_withdrawal:{$withdrawalCode}";
        cache()->put($pendingKey, [
            'agent_id' => $agent->id,
            'client_id' => $client->id,
            'savings_id' => $savings->id,
            'amount' => $amount,
            'initiated_at' => now()->toIso8601String(),
            'status' => 'pending',
        ], now()->addMinutes(30));

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal initiated. Share the code with the agent.',
            'withdrawal_code' => $withdrawalCode,
            'agent' => [
                'name' => $agent->agent_name,
                'number' => $agent->agent_number,
                'location' => $agent->location,
            ],
            'amount' => $amount,
            'expires_at' => now()->addMinutes(30)->toIso8601String(),
        ]);
    }

    /**
     * Agent confirms/processes the withdrawal using the code
     */
    public function confirmWithdrawal(Request $request)
    {
        $request->validate([
            'withdrawal_code' => 'required|string',
            'agent_pin' => 'required|string|size:4',
        ]);

        // Get agent for authenticated user
        $agent = SaccoAgent::where('user_id', Auth::id())->first();
        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Agent account not found',
            ], 403);
        }

        // Verify PIN
        if (!$agent->verifyPin($request->agent_pin)) {
            return response()->json([
                'success' => false,
                'message' => $agent->isLocked() ? 'Account locked. Try again later.' : 'Invalid PIN',
            ], 401);
        }

        // Get pending withdrawal
        $pendingKey = "agency_withdrawal:{$request->withdrawal_code}";
        $pending = cache()->get($pendingKey);

        if (!$pending) {
            return response()->json([
                'success' => false,
                'message' => 'Withdrawal code expired or invalid',
            ], 404);
        }

        if ($pending['agent_id'] !== $agent->id) {
            return response()->json([
                'success' => false,
                'message' => 'This withdrawal was initiated for a different agent',
            ], 403);
        }

        if ($pending['status'] !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This withdrawal has already been processed',
            ], 400);
        }

        $savings = Savings::find($pending['savings_id']);
        $amount = (float)$pending['amount'];

        // Final validation checks
        if ($agent->float_balance < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient float balance',
            ], 400);
        }

        $savingsBalance = (float)($savings->balance ?? 0);
        if ($savingsBalance < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient savings balance',
            ], 400);
        }

        // Process the withdrawal
        try {
            DB::transaction(function () use ($agent, $savings, $amount, $pending, $pendingKey) {
                $floatBefore = $agent->float_balance;

                // Deduct from savings
                $reference = 'AGW-' . strtoupper(uniqid());
                $savingsTxn = new SavingsTransaction();
                $savingsTxn->savings_id = $savings->id;
                $savingsTxn->branch_id = $savings->branch_id;
                $savingsTxn->payment_detail_id = null;
                $savingsTxn->name = 'Agency Withdrawal';
                $savingsTxn->type = 'withdrawal';
                $savingsTxn->reversible = 1;
                $savingsTxn->amount = $amount;
                $savingsTxn->debit = $amount;
                $savingsTxn->credit = 0;
                $savingsTxn->balance = $savingsBalance - $amount;
                $savingsTxn->submitted_on = now();
                $savingsTxn->reference = $reference;
                $savingsTxn->save();

                $savings->balance = $savingsBalance - $amount;
                $savings->save();

                // Deduct from agent float
                $agent->float_balance -= $amount;
                $agent->last_transaction_at = now();
                $agent->save();

                // Calculate commission
                $commission = round($amount * ($agent->commission_rate / 100), 2);

                // Record agent transaction
                $agentTxn = new SaccoAgentTransaction();
                $agentTxn->sacco_agent_id = $agent->id;
                $agentTxn->client_id = $pending['client_id'];
                $agentTxn->savings_id = $savings->id;
                $agentTxn->transaction_type = 'withdrawal';
                $agentTxn->amount = $amount;
                $agentTxn->commission = $commission;
                $agentTxn->reference = $reference;
                $agentTxn->float_before = $floatBefore;
                $agentTxn->float_after = $agent->float_balance;
                $agentTxn->status = 'completed';
                $agentTxn->notes = 'Customer-initiated withdrawal';
                $agentTxn->save();

                // Add commission to agent
                if ($commission > 0) {
                    $agent->addCommission($commission, $agentTxn->id, 'Commission from withdrawal');
                }

                // Mark as processed
                cache()->put($pendingKey, array_merge($pending, [
                    'status' => 'completed',
                    'completed_at' => now()->toIso8601String(),
                    'reference' => $reference,
                ]), now()->addHours(24));
            });

            $client = Client::find($pending['client_id']);

            return response()->json([
                'success' => true,
                'message' => 'Withdrawal processed successfully',
                'transaction' => [
                    'amount' => $amount,
                    'client_name' => $client ? $client->first_name . ' ' . $client->last_name : 'Member',
                    'new_float_balance' => $agent->fresh()->float_balance,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process withdrawal: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function getClientForUser($user): ?Client
    {
        // Check if user has a linked client via client_users table
        $clientUser = DB::table('client_users')
            ->where('user_id', $user->id)
            ->first();

        if ($clientUser) {
            return Client::find($clientUser->client_id);
        }

        // Try to find client by email
        return Client::where('email', $user->email)->first();
    }
}
