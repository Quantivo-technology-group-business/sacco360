<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Portal\Http\Controllers\Concerns\RequiresTransactionAuth;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Entities\SaccoDebitCard;
use Modules\Sacco\Services\CardIssuanceService;
use Modules\Sacco\Services\MemberAccountService;

class SaccoCrossSellController extends Controller
{
    use ResolvesPortalClient;

    public function openBosa(MemberAccountService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        try {
            $service->openBosaAccount($client->id);
            return redirect('portal/sacco/accounts')->with('success', 'BOSA account opened successfully.');
        } catch (\Exception $e) {
            return redirect('portal/dashboard')->with('error', $e->getMessage());
        }
    }

    public function openFosa(MemberAccountService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        try {
            $service->openFosaAccount($client->id);
            return redirect('portal/sacco/accounts')->with('success', 'FOSA account opened successfully.');
        } catch (\Exception $e) {
            return redirect('portal/dashboard')->with('error', $e->getMessage());
        }
    }
}
