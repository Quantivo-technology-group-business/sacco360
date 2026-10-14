<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Portal\Http\Controllers\Concerns\RequiresTransactionAuth;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Entities\SaccoCheque;
use Modules\Sacco\Services\ChequeService;

class SaccoChequeController extends Controller
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

        $cheques = SaccoCheque::where('client_id', $client->id)->orderByDesc('id')->paginate(20);

        return theme_view('portal::sacco.cheques.index', compact('cheques'));
    }

    public function create()
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        return theme_view('portal::sacco.cheques.create');
    }

    public function store(Request $request, ChequeService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $request->validate([
            'cheque_number' => 'required',
            'amount' => 'required|numeric|min:1',
            'cheque_date' => 'required|date',
            'bank_name' => 'nullable|string|max:100',
        ]);

        if ($redirect = $this->verifyTransactionAuth($request, $client->id, (float) $request->amount, 'cheque')) {
            return $redirect;
        }

        $micr = $service->parseMicr($request->micr_code);

        $cheque = new SaccoCheque();
        $cheque->created_by_id = Auth::id();
        $cheque->client_id = $client->id;
        $cheque->cheque_number = $request->cheque_number ?: $micr['cheque_number'];
        $cheque->bank_name = $request->bank_name;
        $cheque->micr_code = $request->micr_code;
        $cheque->amount = $request->amount;
        $cheque->cheque_date = $request->cheque_date;
        $cheque->status = 'received';
        if ($request->hasFile('image')) {
            $cheque->image_path = basename($request->file('image')->store('public/uploads/cheques'));
        }
        $cheque->save();

        return redirect('portal/sacco/cheques')->with('success', trans('core::general.successfully_saved'));
    }
}
