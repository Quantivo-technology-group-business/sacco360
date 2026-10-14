<?php

namespace Modules\Sacco\Services;

use Modules\Client\Entities\Client;
use Modules\Sacco\Entities\SaccoDebitCard;
use Modules\Savings\Entities\Savings;

class CardIssuanceService
{
    public function issue(int $clientId, ?int $savingsId = null): SaccoDebitCard
    {
        $fosa = $savingsId
            ? Savings::with('savings_product')->findOrFail($savingsId)
            : (new WingService())->getFosaAccount($clientId);

        if (!$fosa || $fosa->client_id !== $clientId) {
            throw new \RuntimeException('Valid FOSA account required for card issuance');
        }

        if ((new WingService())->getAccountWing($fosa) !== WingService::WING_FOSA) {
            throw new \RuntimeException('Debit cards can only be linked to FOSA accounts');
        }

        $existing = SaccoDebitCard::where('client_id', $clientId)
            ->whereIn('status', ['pending', 'active'])
            ->first();
        if ($existing) {
            throw new \RuntimeException('Member already has an active or pending debit card');
        }

        $card = new SaccoDebitCard();
        $card->client_id = $clientId;
        $card->savings_id = $fosa->id;
        $card->card_number = $this->generateCardNumber();
        $card->card_token = hash('sha256', $card->card_number . config('app.key'));
        $card->issue_date = date('Y-m-d');
        $card->expiry_date = date('Y-m-d', strtotime('+3 years'));
        $card->status = 'active';
        $card->daily_limit = config('sacco.atm_daily_withdrawal_limit', 50000);
        $card->save();

        $client = Client::find($clientId);
        if ($client) {
            $client->account_number = $card->card_number;
            $client->save();
        }

        return $card;
    }

    public function block(SaccoDebitCard $card): SaccoDebitCard
    {
        $card->status = 'blocked';
        $card->save();
        return $card;
    }

    public function activate(SaccoDebitCard $card): SaccoDebitCard
    {
        $card->status = 'active';
        $card->save();
        return $card;
    }

    public function cancel(SaccoDebitCard $card): SaccoDebitCard
    {
        $card->status = 'cancelled';
        $card->save();
        return $card;
    }

    public function resolveByCard(string $cardNumber): ?SaccoDebitCard
    {
        return SaccoDebitCard::where('card_number', $cardNumber)
            ->where('status', 'active')
            ->first();
    }

    protected function generateCardNumber(): string
    {
        do {
            $number = '4' . str_pad((string)random_int(0, 999999999999999), 15, '0', STR_PAD_LEFT);
        } while (SaccoDebitCard::where('card_number', $number)->exists());

        return $number;
    }
}
