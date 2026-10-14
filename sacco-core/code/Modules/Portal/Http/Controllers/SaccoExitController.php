<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Portal\Http\Controllers\Concerns\RequiresTransactionAuth;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Entities\SaccoExitSettlement;
use Modules\Sacco\Services\ExitSettlementService;

class SaccoExitController extends Controller
{
    use ResolvesPortalClient;
    use RequiresTransactionAuth;

    public function index(ExitSettlementService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $preview = $service->calculate($client->id);
        $settlements = SaccoExitSettlement::where('client_id', $client->id)->orderByDesc('id')->get();

        return theme_view('portal::sacco.exit.index', compact('preview', 'settlements'));
    }

    public function store(Request $request, ExitSettlementService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $request->validate([
            'exit_reason' => 'required|in:resignation,retirement,death,other',
            'notes' => 'nullable|string|max:500',
            'payout_account' => 'nullable|string|max:100',
        ]);

        $preview = $service->calculate($client->id);
        if ($redirect = $this->verifyTransactionAuth($request, $client->id, (float) $preview['net_payout'], 'exit')) {
            return $redirect;
        }

        $service->createDraft($client->id, $request->only(['exit_reason', 'notes', 'payout_account']));

        return redirect('portal/sacco/exit')->with('success', trans('sacco::general.exit_settlement_draft'));
    }
}
