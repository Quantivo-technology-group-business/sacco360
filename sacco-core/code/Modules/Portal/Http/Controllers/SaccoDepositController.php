<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Entities\SaccoMpesaC2bTransaction;
use Modules\Sacco\Services\WingService;
use Modules\Setting\Entities\Setting;

class SaccoDepositController extends Controller
{
    use ResolvesPortalClient;

    public function index()
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $memberNumber = $client->member_number ?? $client->external_id ?? (string)$client->id;
        $paybill = Setting::where('setting_key', 'mpesa.business_shortcode')->value('setting_value') ?? '';
        $fosa = (new WingService())->getFosaAccount($client->id);

        $recentDeposits = SaccoMpesaC2bTransaction::where(function ($q) use ($memberNumber, $client) {
            $q->where('bill_ref_number', $memberNumber)
                ->orWhere('client_id', $client->id);
        })->orderByDesc('id')->limit(10)->get();

        return theme_view('portal::sacco.deposit', compact('memberNumber', 'paybill', 'fosa', 'recentDeposits'));
    }
}
