<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Entities\SaccoCheckoffItem;

class SaccoCheckoffController extends Controller
{
    use ResolvesPortalClient;

    public function index()
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $items = SaccoCheckoffItem::with('batch')
            ->where('client_id', $client->id)
            ->where('status', 'posted')
            ->orderByDesc('id')
            ->paginate(20);

        return theme_view('portal::sacco.checkoff.index', compact('items'));
    }
}
