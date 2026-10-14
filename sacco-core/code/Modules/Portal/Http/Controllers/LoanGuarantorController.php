<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Entities\PaymentDetail;
use Modules\Core\Entities\PaymentType;
use Modules\Loan\Entities\LoanGuarantor;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;

class LoanGuarantorController extends Controller
{
    use ResolvesPortalClient;

    public function show($id)
    {
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $guarantor = LoanGuarantor::with(['loan', 'client'])->findOrFail($id);
        $loan = $guarantor->loan;

        $allowed = $loan && (int) $loan->client_id === (int) $client->id;
        $allowed = $allowed || ((int) ($guarantor->client_id ?? 0) === (int) $client->id);

        if (!$allowed) {
            abort(403);
        }

        return theme_view('portal::loan.guarantor.show', compact('guarantor', 'loan'));
    }
}
