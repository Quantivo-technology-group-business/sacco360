<?php

namespace Modules\Portal\Http\Controllers\Concerns;

use Modules\Sacco\Services\WingService;
use Modules\Wallet\Entities\Wallet;

trait ResolvesMpesaSourceAccounts
{
    protected function resolveMpesaSources(int $clientId): array
    {
        $wing = new WingService();
        $fosa = $wing->getFosaAccount($clientId);
        $fosaBalance = $fosa ? $wing->getAccountBalance($fosa->id) : 0;
        $wallet = Wallet::where('client_id', $clientId)->where('status', 'active')->first();
        $walletBalance = $wallet ? (float)$wallet->balance : 0;

        return compact('fosa', 'fosaBalance', 'wallet', 'walletBalance');
    }
}
