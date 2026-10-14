<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Client\Entities\Client;
use Modules\Sacco\Entities\SaccoBeneficiary;
use Modules\Sacco\Entities\SaccoMpesaB2cTransaction;
use Modules\Savings\Entities\Savings;
use Modules\Wallet\Entities\Wallet;
use Modules\Wallet\Services\WalletChannelService;

class MpesaB2cService
{
    protected MpesaDarajaClient $daraja;

    public function __construct(?MpesaDarajaClient $daraja = null)
    {
        $this->daraja = $daraja ?? new MpesaDarajaClient();
    }

    public function isSimulationMode(): bool
    {
        return !$this->daraja->isB2cConfigured();
    }

    public function sendToBeneficiary(array $data): SaccoMpesaB2cTransaction
    {
        return $this->processOutbound($data, 'send');
    }

    public function withdrawToSelf(array $data): SaccoMpesaB2cTransaction
    {
        return $this->processOutbound($data, 'withdraw');
    }

    protected function processOutbound(array $data, string $transactionType): SaccoMpesaB2cTransaction
    {
        $client = Client::findOrFail($data['client_id']);
        $amount = (float)$data['amount'];
        $sourceType = $data['source_type'];
        $phone = $this->daraja->normalizePhone($data['recipient_phone']);
        $recipientName = $data['recipient_name'] ?? trim($client->first_name . ' ' . $client->last_name);

        $this->checkLimits($client->id, $amount);
        $this->validateSource($sourceType, $data, $client->id, $amount);

        $beneficiaryId = null;
        if (!empty($data['beneficiary_id'])) {
            $beneficiary = SaccoBeneficiary::where('client_id', $client->id)
                ->where('is_active', 1)
                ->findOrFail($data['beneficiary_id']);
            $phone = $beneficiary->phone;
            $recipientName = $beneficiary->name;
            $beneficiaryId = $beneficiary->id;
        }

        if ($transactionType === 'withdraw') {
            $memberPhone = $client->mobile ?: $client->phone;
            if ($memberPhone) {
                $phone = $this->daraja->normalizePhone($memberPhone);
            }
        }

        $reference = 'MPB2C-' . strtoupper(uniqid());
        $txn = new SaccoMpesaB2cTransaction();
        $txn->created_by_id = Auth::id();
        $txn->client_id = $client->id;
        $txn->source_type = $sourceType;
        $txn->savings_id = $sourceType === 'fosa' ? ($data['savings_id'] ?? null) : null;
        $txn->wallet_id = $sourceType === 'wallet' ? ($data['wallet_id'] ?? null) : null;
        $txn->transaction_type = $transactionType;
        $txn->beneficiary_id = $beneficiaryId;
        $txn->recipient_phone = $phone;
        $txn->recipient_name = $recipientName;
        $txn->amount = $amount;
        $txn->reference = $reference;
        $txn->narration = $data['narration'] ?? ($transactionType === 'withdraw' ? 'M-Pesa withdrawal' : 'M-Pesa send');
        $txn->status = 'pending';
        $txn->save();

        if ($this->isSimulationMode()) {
            return DB::transaction(function () use ($txn, $reference, $client) {
                $this->debitSource($txn, $reference);
                $txn->status = 'completed';
                $txn->mpesa_receipt = 'SIM-' . $reference;
                $txn->response_message = 'Simulated M-Pesa B2C (configure B2C credentials in Payment Gateways for live transfers)';
                $txn->save();
                $this->notifyClient($client, $txn);
                $this->logIntegration('mpesa_b2c', 'transfer', $txn->toArray(), $txn->response_message, 'simulated');
                return $txn;
            });
        }

        try {
            $result = $this->daraja->initiateB2c([
                'amount' => $amount,
                'phone' => $phone,
                'remarks' => $txn->narration,
                'reference' => $reference,
            ]);

            $body = $result['body'];
            $this->logIntegration('mpesa_b2c', 'transfer', $result['request'], $body, $result['success'] ? 'submitted' : 'failed');

            if ($result['success'] && ($body['ResponseCode'] ?? '') === '0') {
                $txn->status = 'submitted';
                $txn->conversation_id = $body['ConversationID'] ?? null;
                $txn->originator_conversation_id = $body['OriginatorConversationID'] ?? null;
                $txn->response_message = $body['ResponseDescription'] ?? 'Request accepted for processing';
                $txn->save();
            } else {
                $txn->status = 'failed';
                $txn->response_message = $body['errorMessage'] ?? ($body['ResponseDescription'] ?? 'M-Pesa B2C request failed');
                $txn->save();
            }
        } catch (\Throwable $e) {
            $txn->status = 'failed';
            $txn->response_message = $e->getMessage();
            $txn->save();
        }

        return $txn;
    }

    public function handleResultCallback(array $payload): array
    {
        $result = $payload['Result'] ?? $payload;
        $originatorId = $result['OriginatorConversationID'] ?? null;
        $conversationId = $result['ConversationID'] ?? null;
        $resultCode = (int)($result['ResultCode'] ?? 1);
        $resultDesc = $result['ResultDesc'] ?? 'Unknown result';

        $txn = SaccoMpesaB2cTransaction::where(function ($q) use ($originatorId, $conversationId) {
            if ($originatorId) {
                $q->where('originator_conversation_id', $originatorId);
            }
            if ($conversationId) {
                $q->orWhere('conversation_id', $conversationId);
            }
        })->whereIn('status', ['pending', 'submitted'])->first();

        if (!$txn) {
            return ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
        }

        if ($txn->status === 'completed') {
            return ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
        }

        if ($resultCode === 0) {
            $receipt = $this->extractResultParameter($result, 'TransactionReceipt');
            if ($txn->status !== 'completed') {
                DB::transaction(function () use ($txn, $resultDesc, $receipt) {
                    $this->debitSource($txn, $txn->reference);
                    $txn->status = 'completed';
                    $txn->mpesa_receipt = $receipt;
                    $txn->response_message = $resultDesc;
                    $txn->save();
                });
                $client = Client::find($txn->client_id);
                if ($client) {
                    $this->notifyClient($client, $txn);
                }
            }
        } else {
            $txn->status = 'failed';
            $txn->response_message = $resultDesc;
            $txn->save();
        }

        $this->logIntegration('mpesa_b2c', 'result', $payload, $resultDesc, $resultCode === 0 ? 'success' : 'failed');

        return ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
    }

    public function handleTimeoutCallback(array $payload): array
    {
        $originatorId = $payload['OriginatorConversationID'] ?? ($payload['Result']['OriginatorConversationID'] ?? null);
        if ($originatorId) {
            $txn = SaccoMpesaB2cTransaction::where('originator_conversation_id', $originatorId)
                ->whereIn('status', ['pending', 'submitted'])
                ->first();
            if ($txn) {
                $txn->status = 'timeout';
                $txn->response_message = 'M-Pesa B2C request timed out';
                $txn->save();
            }
        }

        $this->logIntegration('mpesa_b2c', 'timeout', $payload, 'timeout', 'timeout');

        return ['ResultCode' => 0, 'ResultDesc' => 'Accepted'];
    }

    public function checkLimits(int $clientId, float $amount): void
    {
        $min = (float)config('sacco.mpesa_b2c_min_single', 10);
        $max = (float)config('sacco.mpesa_b2c_max_single', 70000);
        $dailyLimit = (float)config('sacco.mpesa_b2c_daily_limit', 50000);

        if ($amount < $min) {
            throw new \RuntimeException('Minimum M-Pesa transfer amount is ' . number_format($min, 2));
        }
        if ($amount > $max) {
            throw new \RuntimeException('Maximum single M-Pesa transfer amount is ' . number_format($max, 2));
        }

        $todayTotal = SaccoMpesaB2cTransaction::where('client_id', $clientId)
            ->whereIn('status', ['submitted', 'completed'])
            ->whereDate('created_at', date('Y-m-d'))
            ->sum('amount');

        if (($todayTotal + $amount) > $dailyLimit) {
            throw new \RuntimeException('Daily M-Pesa transfer limit exceeded.');
        }
    }

    protected function validateSource(string $sourceType, array $data, int $clientId, float $amount): void
    {
        if ($sourceType === 'fosa') {
            $savings = Savings::with('savings_product')->findOrFail($data['savings_id'] ?? 0);
            if ((int)$savings->client_id !== $clientId) {
                throw new \RuntimeException('Invalid FOSA account.');
            }
            $wingService = new WingService();
            if ($wingService->getAccountWing($savings) !== WingService::WING_FOSA) {
                throw new \RuntimeException('M-Pesa transfers are only permitted from FOSA accounts.');
            }
            $withdrawCheck = $wingService->isWithdrawable($savings);
            if (!$withdrawCheck['allowed']) {
                throw new \RuntimeException(implode(' ', $withdrawCheck['violations']));
            }
            if ($wingService->getAccountBalance($savings->id) < $amount) {
                throw new \RuntimeException('Insufficient FOSA balance.');
            }
            return;
        }

        if ($sourceType === 'wallet') {
            $wallet = Wallet::findOrFail($data['wallet_id'] ?? 0);
            if ((int)$wallet->client_id !== $clientId) {
                throw new \RuntimeException('Invalid wallet account.');
            }
            if ($wallet->status !== 'active') {
                throw new \RuntimeException('Wallet is not active.');
            }
            if ((float)$wallet->balance < $amount) {
                throw new \RuntimeException('Insufficient wallet balance.');
            }
            return;
        }

        throw new \RuntimeException('Invalid source account type.');
    }

    protected function debitSource(SaccoMpesaB2cTransaction $txn, string $reference): void
    {
        if ($txn->source_type === 'fosa' && $txn->savings_id) {
            $savings = Savings::findOrFail($txn->savings_id);
            (new SavingsChannelService())->postWithdrawal($savings, (float)$txn->amount, 'mpesa_b2c', $reference);
            return;
        }

        if ($txn->source_type === 'wallet' && $txn->wallet_id) {
            $wallet = Wallet::findOrFail($txn->wallet_id);
            (new WalletChannelService())->postWithdrawal($wallet, (float)$txn->amount, 'mpesa_b2c', $reference);
        }
    }

    protected function extractResultParameter(array $result, string $key): ?string
    {
        $params = $result['ResultParameters']['ResultParameter'] ?? [];
        if (!is_array($params)) {
            return null;
        }
        foreach ($params as $param) {
            if (($param['Key'] ?? '') === $key) {
                return $param['Value'] ?? null;
            }
        }
        return null;
    }

    protected function logIntegration(string $provider, string $action, $request, $response, string $status): void
    {
        if (!Schema::hasTable('sacco_integration_logs')) {
            return;
        }
        DB::table('sacco_integration_logs')->insert([
            'provider' => $provider,
            'action' => $action,
            'request_payload' => json_encode($request),
            'response_payload' => is_string($response) ? $response : json_encode($response),
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function notifyClient(Client $client, SaccoMpesaB2cTransaction $txn): void
    {
        $type = $txn->transaction_type === 'withdraw' ? 'mpesa_withdraw' : 'mpesa_send';
        (new TransactionNotificationService())->notify($client, $type, (float)$txn->amount, $txn->reference);
    }
}
