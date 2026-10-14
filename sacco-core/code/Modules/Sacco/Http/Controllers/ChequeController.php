<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoCheque;
use Modules\Sacco\Services\ChequeService;

class ChequeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.cheques.index']);
    }

    public function index()
    {
        $cheques = SaccoCheque::orderByDesc('id')->paginate(20);
        return theme_view('sacco::cheque.index', compact('cheques'));
    }

    public function create()
    {
        return theme_view('sacco::cheque.create');
    }

    public function store(Request $request, ChequeService $service)
    {
        $request->validate([
            'cheque_number' => 'required',
            'amount' => 'required|numeric',
            'cheque_date' => 'required|date',
        ]);

        $micr = $service->parseMicr($request->micr_code);

        $cheque = new SaccoCheque();
        $cheque->created_by_id = Auth::id();
        $cheque->client_id = $request->client_id;
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

        \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();
        return redirect('sacco/cheque');
    }

    public function present($id, ChequeService $service)
    {
        $service->present(SaccoCheque::findOrFail($id));
        return back();
    }

    public function clear($id, ChequeService $service)
    {
        $service->clear(SaccoCheque::findOrFail($id));
        return back();
    }

    public function bounce($id, ChequeService $service)
    {
        $service->bounce(SaccoCheque::findOrFail($id));
        return back();
    }
}
