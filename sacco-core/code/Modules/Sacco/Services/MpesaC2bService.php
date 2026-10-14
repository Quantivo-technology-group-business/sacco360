<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Log;
use Modules\Client\Entities\Client;
use Modules\Sacco\Entities\SaccoMpesaC2bTransaction;
use Modules\Savings\Entities\Savings;

class MpesaC2bService
{
    public function validate(array $payload): array
    {
        $billRef = $payload['BillRefNumber'] ?? $payload['bill_ref_number'] ?? '';
        $amount = (float)($payload['TransAmount'] ?? $payload['trans_amount'] ?? 0);

        $client = $this->resolveMember($billRef);
        if (!$client) {
            return ['ResultCode' => 'C2B00012', 'ResultDesc' => 'Invalid member account reference'];
        }

        $fosa = (new WingService())->getFosaAccount($client->id);
        if (!$fosa) {
            return ['ResultCode' => 'C2B00012', 'ResultDesc' => 'Member has no active FOSA account'];
        }

        if ($amount <= 0) {
            return ['ResultCode' => 'C2B00013', 'ResultDesc' => 'Invalid amount'];
        }

        return [
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
            'client_id' => $client->id,
            'savings_id' => $fosa->id,
        ];
    }

    public function confirm(array $payload): SaccoMpesaC2bTransaction
    {
        $transId = $payload['TransID'] ?? $payload['trans_id'] ?? uniqid('MP');
        $existing = SaccoMpesaC2bTransaction::where('trans_id', $transId)->first();
        if ($existing && $existing->status === 'posted') {
            return $existing;
        }

        $billRef = $payload['BillRefNumber'] ?? $payload['bill_ref_number'] ?? '';
        $amount = (float)($payload['TransAmount'] ?? $payload['trans_amount'] ?? 0);
        $client = $this->resolveMember($billRef);

        $record = $existing ?? new SaccoMpesaC2bTransaction();
        $record->transaction_type = $payload['TransactionType'] ?? 'Pay Bill';
        $record->trans_id = $transId;
        $record->trans_time = $payload['TransTime'] ?? null;
        $record->trans_amount = $amount;
        $record->business_short_code = $payload['BusinessShortCode'] ?? null;
        $record->bill_ref_number = $billRef;
        $record->msisdn = $payload['MSISDN'] ?? $payload['msisdn'] ?? null;
        $record->status = 'received';
        $record->save();

        if (!$client) {
            $record->status = 'failed';
            $record->response_message = 'Member not found for reference: ' . $billRef;
            $record->save();
            return $record;
        }

        $fosa = (new WingService())->getFosaAccount($client->id);
        if (!$fosa) {
            $record->status = 'failed';
            $record->response_message = 'No FOSA account for member';
            $record->save();
            return $record;
        }

        try {
            $txn = (new SavingsChannelService())->postDeposit(
                $fosa,
                $amount,
                'mpesa_c2b',
                $transId
            );
            $record->client_id = $client->id;
            $record->savings_id = $fosa->id;
            $record->savings_transaction_id = $txn->id;
            $record->status = 'posted';
            $record->response_message = 'Credited to FOSA account';
            $record->save();

            if (class_exists(TransactionNotificationService::class)) {
                (new TransactionNotificationService())->notify($client, 'deposit', $amount, $transId);
            }
        } catch (\Throwable $e) {
            Log::error('M-Pesa C2B posting failed: ' . $e->getMessage());
            $record->status = 'failed';
            $record->response_message = $e->getMessage();
            $record->save();
        }

        return $record;
    }

    protected function resolveMember(string $reference): ?Client
    {
        if (empty($reference)) {
            return null;
        }

        return Client::where('member_number', $reference)
            ->orWhere('external_id', $reference)
            ->orWhere('account_number', $reference)
            ->orWhere('mobile', 'like', '%' . substr($reference, -9))
            ->first();
    }
}
