<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Entities\SaccoAtmTransaction;

class SaccoAtmHistoryController extends Controller
{
    use ResolvesPortalClient;

    public function index()
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $transactions = SaccoAtmTransaction::where('client_id', $client->id)
            ->orderByDesc('id')
            ->paginate(20);

        return theme_view('portal::sacco.atm.index', compact('transactions'));
    }
}
