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

class BeneficiaryService
{
    protected MpesaDarajaClient $daraja;

    public function __construct(?MpesaDarajaClient $daraja = null)
    {
        $this->daraja = $daraja ?? new MpesaDarajaClient();
    }

    public function listForClient(int $clientId)
    {
        return SaccoBeneficiary::where('client_id', $clientId)
            ->where('is_active', 1)
            ->orderBy('nickname')
            ->get();
    }

    public function create(int $clientId, array $data): SaccoBeneficiary
    {
        $phone = $this->daraja->normalizePhone($data['phone']);
        $existing = SaccoBeneficiary::where('client_id', $clientId)->where('phone', $phone)->first();
        if ($existing) {
            throw new \RuntimeException('A beneficiary with this phone number already exists.');
        }

        $beneficiary = new SaccoBeneficiary();
        $beneficiary->client_id = $clientId;
        $beneficiary->nickname = $data['nickname'];
        $beneficiary->name = $data['name'];
        $beneficiary->phone = $phone;
        $beneficiary->is_active = 1;
        $beneficiary->save();

        return $beneficiary;
    }

    public function deactivate(int $clientId, int $beneficiaryId): void
    {
        $beneficiary = SaccoBeneficiary::where('client_id', $clientId)->findOrFail($beneficiaryId);
        $beneficiary->is_active = 0;
        $beneficiary->save();
    }
}
