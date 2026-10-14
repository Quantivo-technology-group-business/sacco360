<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Modules\Sacco\Entities\SaccoPesalinkTransaction;
use Modules\Savings\Entities\Savings;

class PesalinkService
{
    public function sendFromFosa(array $data): SaccoPesalinkTransaction
    {
        $savings = Savings::with('savings_product')->findOrFail($data['savings_id']);
        $wingService = new WingService();

        if ($wingService->getAccountWing($savings) !== WingService::WING_FOSA) {
            throw new \RuntimeException('Pesalink transfers are only permitted from FOSA accounts');
        }

        $amount = (float)$data['amount'];
        if ($wingService->getAccountBalance($savings->id) < $amount) {
            throw new \RuntimeException('Insufficient FOSA balance');
        }

        $reference = 'PL-' . strtoupper(uniqid());
        $txn = new SaccoPesalinkTransaction();
        $txn->created_by_id = Auth::id();
        $txn->client_id = $savings->client_id;
        $txn->savings_id = $savings->id;
        $txn->direction = 'outbound';
        $txn->beneficiary_account = $data['beneficiary_account'];
        $txn->beneficiary_bank = $data['beneficiary_bank'];
        $txn->beneficiary_name = $data['beneficiary_name'];
        $txn->amount = $amount;
        $txn->reference = $reference;
        $txn->status = 'pending';
        $txn->save();

        $apiUrl = config('sacco.pesalink_api_url');
        if (empty($apiUrl)) {
            return DB::transaction(function () use ($txn, $savings, $amount, $reference) {
                (new SavingsChannelService())->postWithdrawal($savings, $amount, 'pesalink', $reference);
                $txn->status = 'completed';
                $txn->ips_reference = 'SIM-' . $reference;
                $txn->response_message = 'Simulated Pesalink transfer (configure SACCO_PESALINK_API_URL for live IPS)';
                $txn->save();
                $client = \Modules\Client\Entities\Client::find($savings->client_id);
                if ($client) {
                    (new TransactionNotificationService())->notify($client, 'pesalink', $amount, $reference);
                }
                $this->logIntegration('pesalink', 'transfer', $txn->toArray(), $txn->response_message, 'simulated');
                return $txn;
            });
        }

        try {
            $payload = [
                'source_account' => $savings->account_number,
                'beneficiary_account' => $txn->beneficiary_account,
                'beneficiary_bank' => $txn->beneficiary_bank,
                'beneficiary_name' => $txn->beneficiary_name,
                'amount' => $amount,
                'reference' => $reference,
                'narration' => $data['narration'] ?? 'SACCO Pesalink transfer',
            ];
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('sacco.pesalink_api_key'),
            ])->post($apiUrl . '/transfer', $payload);

            $result = $response->json() ?? [];
            $this->logIntegration('pesalink', 'transfer', $payload, $result, $response->successful() ? 'success' : 'failed');

            if ($response->successful() && ($result['status'] ?? '') === 'success') {
                DB::transaction(function () use ($txn, $savings, $amount, $reference, $result) {
                    (new SavingsChannelService())->postWithdrawal($savings, $amount, 'pesalink', $reference);
                    $txn->status = 'completed';
                    $txn->ips_reference = $result['ips_reference'] ?? null;
                    $txn->response_message = $result['message'] ?? 'Transfer completed';
                    $txn->save();
                    $client = \Modules\Client\Entities\Client::find($savings->client_id);
                    if ($client) {
                        (new TransactionNotificationService())->notify($client, 'pesalink', $amount, $reference);
                    }
                });
            } else {
                $txn->status = 'failed';
                $txn->response_message = $result['message'] ?? 'Pesalink transfer failed';
                $txn->save();
            }
        } catch (\Throwable $e) {
            $txn->status = 'failed';
            $txn->response_message = $e->getMessage();
            $txn->save();
        }

        return $txn;
    }

    public function receiveInbound(array $data): SaccoPesalinkTransaction
    {
        $client = (new MemberNumberService())->resolve($data['account_reference'] ?? '');
        if (!$client) {
            throw new \RuntimeException('Member not found for inbound Pesalink');
        }

        $fosa = (new WingService())->getFosaAccount($client->id);
        if (!$fosa) {
            throw new \RuntimeException('Member has no FOSA account');
        }

        $reference = $data['reference'] ?? 'PL-IN-' . uniqid();
        $existing = SaccoPesalinkTransaction::where('reference', $reference)->first();
        if ($existing && $existing->status === 'completed') {
            return $existing;
        }

        $txn = $existing ?? new SaccoPesalinkTransaction();
        $txn->client_id = $client->id;
        $txn->savings_id = $fosa->id;
        $txn->direction = 'inbound';
        $txn->beneficiary_account = $data['sender_account'] ?? null;
        $txn->beneficiary_bank = $data['sender_bank'] ?? null;
        $txn->beneficiary_name = $data['sender_name'] ?? null;
        $txn->amount = (float)$data['amount'];
        $txn->reference = $reference;
        $txn->ips_reference = $data['ips_reference'] ?? null;
        $txn->status = 'completed';
        $txn->response_message = 'Inbound Pesalink credit';
        $txn->save();

        (new SavingsChannelService())->postDeposit($fosa, $txn->amount, 'pesalink', $reference);

        return $txn;
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
}
