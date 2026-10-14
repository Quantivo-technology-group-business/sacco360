<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Entities\SaccoDebitCard;
use Modules\Sacco\Entities\SaccoFixedDeposit;
use Modules\Sacco\Services\Member360Service;
use Modules\Sacco\Services\WingService;
use Modules\Wallet\Entities\Wallet;

class SaccoAccountsController extends Controller
{
    use ResolvesPortalClient;

    public function index()
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $wing = new WingService();
        $bosa = $wing->getBosaAccount($client->id);
        $fosa = $wing->getFosaAccount($client->id);
        $bosaBalance = $bosa ? $wing->getAccountBalance($bosa->id) : 0;
        $fosaBalance = $fosa ? $wing->getAccountBalance($fosa->id) : 0;
        $wallet = Wallet::where('client_id', $client->id)->where('status', 'active')->first();
        $walletBalance = $wallet ? (float) $wallet->balance : 0;
        $fixedDeposits = SaccoFixedDeposit::where('client_id', $client->id)->orderByDesc('id')->get();
        $cards = SaccoDebitCard::where('client_id', $client->id)->get();
        $member360 = (new Member360Service())->getSummary($client->id);
        $suggestions = class_exists(\Modules\Sacco\Services\CrossSellService::class)
            ? (new \Modules\Sacco\Services\CrossSellService())->suggestForMember($client->id)
            : [];

        return theme_view('portal::sacco.accounts', compact(
            'client', 'bosa', 'fosa', 'bosaBalance', 'fosaBalance', 'wallet', 'walletBalance',
            'fixedDeposits', 'cards', 'member360', 'suggestions'
        ));
    }
}
