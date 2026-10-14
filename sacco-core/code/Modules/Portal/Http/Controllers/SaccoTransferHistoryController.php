<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Entities\SaccoTransfer;

class SaccoTransferHistoryController extends Controller
{
    use ResolvesPortalClient;

    public function index(Request $request)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $query = SaccoTransfer::where(function ($q) use ($client) {
            $q->where('client_id', $client->id)
                ->orWhere('recipient_client_id', $client->id);
        });

        if ($request->type === 'member') {
            $query->where('transfer_type', 'savings_to_member_savings');
        }

        $transfers = $query->orderByDesc('id')->paginate(20)->appends($request->input());

        return theme_view('portal::sacco.transfers', compact('transfers'));
    }
}
