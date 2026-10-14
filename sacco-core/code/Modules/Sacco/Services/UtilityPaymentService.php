<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Modules\Client\Entities\Client;
use Modules\Sacco\Entities\SaccoUtilityPayment;
use Modules\Sacco\Entities\SaccoUtilityProvider;
use Modules\Savings\Entities\SavingsTransaction;

class UtilityPaymentService
{
    public function listProviders()
    {
        return SaccoUtilityProvider::where('active', 1)->orderBy('name')->get();
    }

    public function listPaymentsForClient(int $clientId, int $limit = 50)
    {
        return SaccoUtilityPayment::with('provider')
            ->where('client_id', $clientId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function payFromFosa(int $clientId, int $providerId, string $accountNumber, float $amount, string $channel = 'ussd'): array
    {
        $client = Client::findOrFail($clientId);
        $provider = SaccoUtilityProvider::where('id', $providerId)->where('active', 1)->firstOrFail();

        if ($amount < $provider->min_amount || $amount > $provider->max_amount) {
            throw new \RuntimeException(sprintf(
                'Amount must be between %.2f and %.2f',
                $provider->min_amount,
                $provider->max_amount
            ));
        }

        $kyc = (new KycService())->canTransact($clientId);
        if (!$kyc['allowed']) {
            throw new \RuntimeException($kyc['reason'] ?? 'KYC verification required');
        }

        $earmark = (new EarmarkService())->validateTransaction($clientId, 'withdrawal', $amount);
        if (!$earmark['allowed']) {
            throw new \RuntimeException(implode('; ', $earmark['violations']));
        }

        $wingService = new WingService();
        $fosa = $wingService->getFosaAccount($clientId);
        if (!$fosa) {
            throw new \RuntimeException('No FOSA account found');
        }

        $withdrawCheck = $wingService->validateWithdrawalAmount($fosa, $amount);
        if (!$withdrawCheck['allowed']) {
            throw new \RuntimeException(implode(' ', $withdrawCheck['violations']));
        }

        return DB::transaction(function () use ($client, $provider, $accountNumber, $amount, $fosa, $wingService, $channel) {
            $reference = 'UTIL-' . time();
            $channelService = new SavingsChannelService();

            $savingsTxn = $channelService->postWithdrawal($fosa, $amount, 'utility', $reference);
            $savingsTxn->name = 'Utility payment - ' . $provider->name;
            $savingsTxn->save();

            $payment = new SaccoUtilityPayment();
            $payment->client_id = $client->id;
            $payment->savings_id = $fosa->id;
            $payment->provider_id = $provider->id;
            $payment->account_number = $accountNumber;
            $payment->amount = $amount;
            $payment->status = 'completed';
            $payment->reference = $reference;
            $payment->channel = $channel;
            $payment->savings_transaction_id = $savingsTxn->id;
            $payment->notes = $provider->name . ' - ' . $accountNumber;
            $payment->save();

            (new TransactionNotificationService())->notify($client, 'utility payment', $amount, $reference);

            return [
                'reference' => $reference,
                'newFosaBalance' => $wingService->getAccountBalance($fosa->id),
                'provider' => $provider->name,
                'accountNumber' => $accountNumber,
                'amount' => $amount,
            ];
        });
    }
}
