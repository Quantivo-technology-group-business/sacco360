<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Services\EarmarkService;

class SaccoEarmarkController extends Controller
{
    use ResolvesPortalClient;

    public function index(EarmarkService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $earmarks = $service->getActiveForClient($client->id);

        return theme_view('portal::sacco.earmarks', compact('earmarks'));
    }
}
