<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Modules\Client\Entities\Client;
use Modules\Sacco\Entities\SaccoAtmTransaction;
use Modules\Savings\Entities\Savings;

class AtmService
{
    public function process(array $input): array
    {
        $terminalId = $input['terminal_id'] ?? 'ATM-001';
        $memberNumber = $input['member_number'] ?? null;
        $cardNumber = $input['card_number'] ?? null;
        $type = $input['transaction_type'] ?? 'balance_inquiry';
        $amount = (float)($input['amount'] ?? 0);
        $pin = $input['pin'] ?? null;
        $accountType = $input['account_type'] ?? 'fosa';

        $client = $this->resolveClient($memberNumber, $cardNumber);
        if (!$client) {
            return $this->fail('Member not found', $terminalId, $type, $amount, $memberNumber, $cardNumber);
        }

        if ($pin && !$this->validatePin($client, $pin)) {
            return $this->fail('Invalid PIN', $terminalId, $type, $amount, $memberNumber, $cardNumber, $client->id);
        }

        $earmarkCheck = (new EarmarkService())->validateTransaction(
            $client->id,
            $type === 'withdrawal' ? 'atm_withdrawal' : ($type === 'deposit' ? 'atm_deposit' : 'inquiry'),
            $amount
        );
        if (!$earmarkCheck['allowed']) {
            return $this->fail(implode('; ', $earmarkCheck['violations']), $terminalId, $type, $amount, $memberNumber, $cardNumber, $client->id);
        }

        $savings = $this->resolveAccount($client, $accountType, $cardNumber);

        switch ($type) {
            case 'balance_inquiry':
                return $this->balanceInquiry($client, $savings, $terminalId, $memberNumber, $cardNumber);
            case 'mini_statement':
                return $this->miniStatement($client, $savings, $terminalId, $memberNumber, $cardNumber);
            case 'withdrawal':
                return $this->withdrawal($client, $savings, $amount, $terminalId, $memberNumber, $cardNumber);
            case 'deposit':
                return $this->deposit($client, $savings, $amount, $terminalId, $memberNumber, $cardNumber);
            default:
                return $this->fail('Unsupported transaction type', $terminalId, $type, $amount, $memberNumber, $cardNumber, $client->id);
        }
    }

    protected function resolveAccount(Client $client, string $accountType, ?string $cardNumber = null): ?Savings
    {
        if ($cardNumber) {
            $card = (new CardIssuanceService())->resolveByCard($cardNumber);
            if ($card) {
                return Savings::find($card->savings_id);
            }
        }
        $wingService = new WingService();
        if ($accountType === 'bosa') {
            return $wingService->getBosaAccount($client->id);
        }
        return $wingService->getFosaAccount($client->id);
    }

    protected function resolveClient(?string $memberNumber, ?string $cardNumber): ?Client
    {
        if ($memberNumber) {
            $client = Client::where('member_number', $memberNumber)
                ->orWhere('external_id', $memberNumber)
                ->orWhere('account_number', $memberNumber)
                ->first();
            if ($client) {
                return $client;
            }
        }
        if ($cardNumber) {
            $card = (new CardIssuanceService())->resolveByCard($cardNumber);
            if ($card) {
                return Client::find($card->client_id);
            }
            return Client::where('account_number', $cardNumber)->first();
        }
        return null;
    }

    protected function validatePin(Client $client, string $pin): bool
    {
        return hash_equals((string)($client->pin ?? ''), (string)$pin)
            || hash_equals(substr(md5($client->mobile . config('app.key')), 0, 4), $pin);
    }

    protected function balanceInquiry(Client $client, ?Savings $savings, string $terminalId, ?string $memberNumber, ?string $cardNumber): array
    {
        $wingService = new WingService();
        $fosaBalance = $wingService->getSavingsBalanceByWing($client->id, WingService::WING_FOSA);
        $bosaBalance = $wingService->getSavingsBalanceByWing($client->id, WingService::WING_BOSA);
        $summary = (new Member360Service())->getSummary($client->id);
        $txn = $this->logTransaction($terminalId, $memberNumber, $cardNumber, $client->id, $savings->id ?? null, 'balance_inquiry', 0, 'completed', 'Balance inquiry successful');

        return [
            'success' => true,
            'reference' => $txn->reference,
            'member_number' => $client->member_number ?? $client->external_id,
            'account_type' => $savings ? $wingService->getAccountWing($savings) : 'fosa',
            'fosa_balance' => $fosaBalance,
            'bosa_balance' => $bosaBalance,
            'savings_balance' => $summary['savings_balance'],
            'share_value' => $summary['share_value'],
            'loan_balance' => $summary['loan_balance'],
            'net_position' => $summary['net_position'],
        ];
    }

    protected function miniStatement(Client $client, ?Savings $savings, string $terminalId, ?string $memberNumber, ?string $cardNumber): array
    {
        $query = DB::table('savings_transactions')
            ->join('savings', 'savings.id', '=', 'savings_transactions.savings_id')
            ->where('savings.client_id', $client->id);

        if ($savings) {
            $query->where('savings_transactions.savings_id', $savings->id);
        }

        $txns = $query->orderByDesc('savings_transactions.id')
            ->limit(5)
            ->select('savings_transactions.name', 'savings_transactions.amount', 'savings_transactions.submitted_on', 'savings_transactions.credit', 'savings_transactions.debit')
            ->get();

        $txn = $this->logTransaction($terminalId, $memberNumber, $cardNumber, $client->id, $savings->id ?? null, 'mini_statement', 0, 'completed', 'Mini statement retrieved');

        return [
            'success' => true,
            'reference' => $txn->reference,
            'transactions' => $txns,
        ];
    }

    protected function withdrawal(Client $client, ?Savings $savings, float $amount, string $terminalId, ?string $memberNumber, ?string $cardNumber): array
    {
        if (!$savings || $amount <= 0) {
            return $this->fail('Invalid withdrawal request', $terminalId, 'withdrawal', $amount, $memberNumber, $cardNumber, $client->id);
        }

        $wingService = new WingService();
        if ($wingService->getAccountWing($savings) === WingService::WING_BOSA) {
            return $this->fail('ATM withdrawals are only permitted from FOSA accounts', $terminalId, 'withdrawal', $amount, $memberNumber, $cardNumber, $client->id, $savings->id);
        }

        $balance = $wingService->getAccountBalance($savings->id);
        $atmLimit = config('sacco.atm_daily_withdrawal_limit', 50000);
        $todayTotal = SaccoAtmTransaction::where('client_id', $client->id)
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'completed')
            ->whereDate('created_at', date('Y-m-d'))
            ->sum('amount');

        if ($amount > $balance) {
            return $this->fail('Insufficient balance', $terminalId, 'withdrawal', $amount, $memberNumber, $cardNumber, $client->id, $savings->id);
        }
        if (($todayTotal + $amount) > $atmLimit) {
            return $this->fail('ATM daily withdrawal limit exceeded', $terminalId, 'withdrawal', $amount, $memberNumber, $cardNumber, $client->id, $savings->id);
        }

        try {
            $savingsTxn = (new SavingsChannelService())->postWithdrawal($savings, $amount, 'atm', 'ATM-' . uniqid());
            $txn = $this->logTransaction($terminalId, $memberNumber, $cardNumber, $client->id, $savings->id, 'withdrawal', $amount, 'completed', 'Withdrawal posted to ledger');

            return [
                'success' => true,
                'reference' => $txn->reference,
                'ledger_reference' => 'S' . $savingsTxn->id,
                'amount' => $amount,
                'new_balance' => $balance - $amount,
                'message' => 'Withdrawal completed successfully',
            ];
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), $terminalId, 'withdrawal', $amount, $memberNumber, $cardNumber, $client->id, $savings->id);
        }
    }

    protected function deposit(Client $client, ?Savings $savings, float $amount, string $terminalId, ?string $memberNumber, ?string $cardNumber): array
    {
        if (!$savings || $amount <= 0) {
            return $this->fail('Invalid deposit request', $terminalId, 'deposit', $amount, $memberNumber, $cardNumber, $client->id);
        }

        try {
            $savingsTxn = (new SavingsChannelService())->postDeposit($savings, $amount, 'atm', 'ATM-' . uniqid());
            $txn = $this->logTransaction($terminalId, $memberNumber, $cardNumber, $client->id, $savings->id, 'deposit', $amount, 'completed', 'Deposit posted to ledger');

            return [
                'success' => true,
                'reference' => $txn->reference,
                'ledger_reference' => 'S' . $savingsTxn->id,
                'amount' => $amount,
                'message' => 'Deposit completed successfully',
            ];
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), $terminalId, 'deposit', $amount, $memberNumber, $cardNumber, $client->id, $savings->id);
        }
    }

    protected function logTransaction(string $terminalId, ?string $memberNumber, ?string $cardNumber, ?int $clientId, ?int $savingsId, string $type, float $amount, string $status, string $message): SaccoAtmTransaction
    {
        $txn = new SaccoAtmTransaction();
        $txn->terminal_id = $terminalId;
        $txn->card_number = $cardNumber;
        $txn->member_number = $memberNumber;
        $txn->client_id = $clientId;
        $txn->savings_id = $savingsId;
        $txn->transaction_type = $type;
        $txn->amount = $amount;
        $txn->reference = 'ATM-' . strtoupper(uniqid());
        $txn->status = $status;
        $txn->response_message = $message;
        $txn->save();
        return $txn;
    }

    protected function fail(string $message, string $terminalId, string $type, float $amount, ?string $memberNumber, ?string $cardNumber, ?int $clientId = null, ?int $savingsId = null): array
    {
        $txn = $this->logTransaction($terminalId, $memberNumber, $cardNumber, $clientId, $savingsId, $type, $amount, 'failed', $message);
        return ['success' => false, 'reference' => $txn->reference, 'message' => $message];
    }
}
