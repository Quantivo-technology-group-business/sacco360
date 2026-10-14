<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Entities\SaccoDividendAllocation;

class SaccoDividendController extends Controller
{
    use ResolvesPortalClient;

    public function index()
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $allocations = SaccoDividendAllocation::with('dividend')
            ->where('client_id', $client->id)
            ->orderByDesc('id')
            ->paginate(20);

        return theme_view('portal::sacco.dividends.index', compact('allocations'));
    }
}
