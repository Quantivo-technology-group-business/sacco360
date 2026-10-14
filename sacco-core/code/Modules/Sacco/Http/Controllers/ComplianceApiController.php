<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sacco\Services\CaelsClassificationService;
use Modules\Sacco\Services\PrudentialRatioService;
use Modules\Sacco\Services\SasraReportingService;

class ComplianceApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function ratios(Request $request)
    {
        $asOfDate = $request->get('as_of', date('Y-m-d'));
        return response()->json((new PrudentialRatioService())->calculateAll($asOfDate));
    }

    public function report(Request $request, string $form)
    {
        $asOfDate = $request->get('as_of', date('Y-m-d'));
        $service = new SasraReportingService();
        $method = 'form' . str_replace(['form_', '/'], ['', ''], $form);
        $map = [
            '1001' => 'form3CapitalAdequacy',
            '1002' => 'form4Liquidity',
            '1003' => 'form7DepositReturn',
            '1004' => 'form5AssetQuality',
            '1006' => 'form1StatementOfFinancialPosition',
            '1007' => 'form2StatementOfComprehensiveIncome',
            '3' => 'form3CapitalAdequacy',
            '4' => 'form4Liquidity',
            '5' => 'form5AssetQuality',
        ];
        $call = $map[$form] ?? null;
        if (!$call || !method_exists($service, $call)) {
            return response()->json(['error' => 'Unknown form'], 404);
        }
        return response()->json($service->{$call}($asOfDate));
    }

    public function classifications(Request $request)
    {
        $asOfDate = $request->get('as_of', date('Y-m-d'));
        return response()->json((new CaelsClassificationService())->classifyPortfolio($asOfDate));
    }

    public function memberExport(Request $request, int $clientId)
    {
        if (!class_exists(\Modules\Sacco\Services\Member360Service::class)) {
            return response()->json(['error' => 'Member service unavailable'], 503);
        }
        $data = (new \Modules\Sacco\Services\Member360Service())->getSummary($clientId);
        return response()->json([
            'exported_at' => date('c'),
            'client_id' => $clientId,
            'data' => $data,
        ]);
    }
}
