<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sacco\Services\LoanAppraisalService;

class AppraisalController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'client_id' => 'required|integer',
            'loan_product_id' => 'required|integer',
            'amount' => 'nullable|numeric',
        ]);

        $service = new LoanAppraisalService();
        $appraisal = $service->calculateMaxLoanAmount(
            (int)$request->client_id,
            (int)$request->loan_product_id
        );

        if ($request->filled('amount')) {
            $appraisal['validation'] = $service->validateApplicationAmount(
                (int)$request->client_id,
                (int)$request->loan_product_id,
                (float)$request->amount
            );
        }

        return response()->json($appraisal);
    }
}
