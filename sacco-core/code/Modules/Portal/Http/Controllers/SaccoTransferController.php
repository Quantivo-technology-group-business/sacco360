<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Loan\Entities\Loan;
use Modules\Portal\Http\Controllers\Concerns\RequiresTransactionAuth;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Services\CrossSellService;
use Modules\Sacco\Services\TransferService;
use Modules\Sacco\Services\WingService;
use Modules\Savings\Entities\Savings;
use Modules\Share\Entities\Share;

class SaccoTransferController extends Controller
{
    use ResolvesPortalClient;
    use RequiresTransactionAuth;

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
        $loans = Loan::where('client_id', $client->id)->whereIn('status', ['active', 'disbursed'])->get();
        $shares = Share::where('client_id', $client->id)->where('status', 'active')->get();
        $suggestions = class_exists(CrossSellService::class)
            ? (new CrossSellService())->suggestForMember($client->id)
            : [];

        $transferFormData = [
            'bosa' => $bosa ? ['id' => $bosa->id, 'label' => 'BOSA #' . ($bosa->account_number ?? $bosa->id)] : null,
            'fosa' => $fosa ? ['id' => $fosa->id, 'label' => 'FOSA #' . ($fosa->account_number ?? $fosa->id)] : null,
            'loans' => $loans->map(function ($l) {
                return ['id' => $l->id, 'label' => 'Loan #' . $l->id . ' - ' . number_format($l->principal, 2)];
            })->values(),
            'shares' => $shares->map(function ($s) {
                return ['id' => $s->id, 'label' => 'Share #' . $s->id];
            })->values(),
        ];

        return theme_view('portal::sacco.transfer', compact('client', 'bosa', 'fosa', 'loans', 'shares', 'suggestions', 'transferFormData'));
    }

    public function store(Request $request, TransferService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        if ($redirect = $this->requireKyc($client->id)) {
            return $redirect;
        }

        $request->validate([
            'transfer_type' => 'required|in:savings_to_savings,savings_to_loan,share_to_savings,savings_to_share',
            'source_id' => 'required',
            'destination_id' => 'required',
            'amount' => 'required|numeric|min:1',
        ]);

        if ($redirect = $this->verifyTransactionAuth($request, $client->id, (float) $request->amount, 'transfer')) {
            return $redirect;
        }

        $type = $request->transfer_type;

        if (in_array($type, ['savings_to_savings', 'savings_to_loan', 'savings_to_share'], true)) {
            Savings::where('id', $request->source_id)->where('client_id', $client->id)->firstOrFail();
        }
        if ($type === 'share_to_savings') {
            Share::where('id', $request->source_id)->where('client_id', $client->id)->firstOrFail();
        }
        if (in_array($type, ['savings_to_savings', 'share_to_savings'], true)) {
            Savings::where('id', $request->destination_id)->where('client_id', $client->id)->firstOrFail();
        }
        if ($type === 'savings_to_loan') {
            Loan::where('id', $request->destination_id)->where('client_id', $client->id)->firstOrFail();
        }
        if ($type === 'savings_to_share') {
            Share::where('id', $request->destination_id)->where('client_id', $client->id)->firstOrFail();
        }

        $source = in_array($type, ['savings_to_savings', 'savings_to_loan', 'savings_to_share'], true)
            ? Savings::findOrFail($request->source_id)
            : Share::findOrFail($request->source_id);

        $service->execute([
            'client_id' => $client->id,
            'branch_id' => $source->branch_id ?? $client->branch_id,
            'transfer_type' => $type,
            'source_id' => $request->source_id,
            'destination_id' => $request->destination_id,
            'amount' => $request->amount,
        ]);

        return redirect('portal/sacco/transfer')->with('success', 'Transfer completed successfully.');
    }
}
