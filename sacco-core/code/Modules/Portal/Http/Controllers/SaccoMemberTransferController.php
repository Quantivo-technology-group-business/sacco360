<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Portal\Http\Controllers\Concerns\RequiresTransactionAuth;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Services\TransferService;
use Modules\Sacco\Services\WingService;

class SaccoMemberTransferController extends Controller
{
    use ResolvesPortalClient;
    use RequiresTransactionAuth;

    public function create()
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $wing = new WingService();
        $fosa = $wing->getFosaAccount($client->id);
        $balance = $fosa ? $wing->getAccountBalance($fosa->id) : 0;

        return theme_view('portal::sacco.member_transfer.create', compact('fosa', 'balance'));
    }

    public function store(Request $request, TransferService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        if ($redirect = $this->requireKyc($client->id, 'portal/sacco/member-transfer/create')) {
            return $redirect;
        }

        $request->validate([
            'member_number' => 'required|string|max:50',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($redirect = $this->verifyTransactionAuth($request, $client->id, (float) $request->amount, 'member_transfer')) {
            return $redirect;
        }

        $recipient = $service->resolveMemberByNumber($request->member_number);
        if (!$recipient) {
            return redirect('portal/sacco/member-transfer/create')->with('error', trans('sacco::general.member_not_found'));
        }
        if ((int) $recipient->id === (int) $client->id) {
            return redirect('portal/sacco/member-transfer/create')->with('error', trans('sacco::general.cannot_transfer_to_self'));
        }

        $wing = new WingService();
        $source = $wing->getFosaAccount($client->id);
        $dest = $wing->getFosaAccount($recipient->id);
        if (!$source || !$dest) {
            return redirect('portal/sacco/member-transfer/create')->with('error', trans('sacco::general.no_fosa_account'));
        }

        try {
            $service->execute([
                'client_id' => $client->id,
                'recipient_client_id' => $recipient->id,
                'recipient_member_number' => $request->member_number,
                'branch_id' => $source->branch_id ?? $client->branch_id,
                'transfer_type' => 'savings_to_member_savings',
                'source_id' => $source->id,
                'destination_id' => $dest->id,
                'amount' => (float) $request->amount,
                'notes' => $request->notes,
            ]);
            return redirect('portal/sacco/transfers')->with('success', trans('core::general.successfully_saved'));
        } catch (\Throwable $e) {
            return redirect('portal/sacco/member-transfer/create')->with('error', $e->getMessage());
        }
    }
}
