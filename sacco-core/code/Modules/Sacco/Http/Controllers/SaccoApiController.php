<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sacco\Services\EarmarkService;
use Modules\Sacco\Services\GlobalSearchService;
use Modules\Sacco\Services\LoanAppraisalService;
use Modules\Sacco\Services\Member360Service;
use Modules\Sacco\Services\MemberNumberService;

class SaccoApiController extends Controller
{
    public function member360($identifier)
    {
        $client = (new MemberNumberService())->resolve($identifier);
        if (!$client) {
            return response()->json(['error' => 'Member not found'], 404);
        }
        return response()->json((new Member360Service())->getSummary($client->id));
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        return response()->json((new GlobalSearchService())->search($query, (int)$request->get('limit', 20)));
    }

    public function appraisal(Request $request)
    {
        $request->validate([
            'client_id' => 'required|integer',
            'loan_product_id' => 'required|integer',
            'loan_id' => 'nullable|integer',
            'amount' => 'nullable|numeric',
        ]);

        $service = new LoanAppraisalService();
        $appraisal = $service->calculateMaxLoanAmount(
            (int)$request->client_id,
            (int)$request->loan_product_id,
            $request->loan_id ? (int)$request->loan_id : null
        );

        if ($request->filled('amount') && $request->loan_id) {
            $loan = \Modules\Loan\Entities\Loan::find($request->loan_id);
            if ($loan) {
                $appraisal['validation'] = $service->validateLoanAmount($loan, (float)$request->amount);
            }
        }

        return response()->json($appraisal);
    }

    public function earmarks($clientId)
    {
        return response()->json((new EarmarkService())->getActiveForClient((int)$clientId));
    }
}
