<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Sacco\Entities\SaccoMpesaC2bTransaction;
use Modules\ActivityLog\Services\AuditLogger;
use Modules\Sacco\Services\MpesaC2bService;

class MpesaC2bController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa'])->only(['index']);
        $this->middleware(['permission:sacco.mpesa_c2b.index'])->only(['index']);
    }

    public function validate(Request $request, MpesaC2bService $service)
    {
        $result = $service->validate($request->all());
        return response()->json([
            'ResultCode' => $result['ResultCode'],
            'ResultDesc' => $result['ResultDesc'],
        ]);
    }

    public function confirm(Request $request, MpesaC2bService $service)
    {
        $record = $service->confirm($request->all());
        AuditLogger::log('M-Pesa C2B Received', $record, ['id' => $record->id, 'amount' => $record->TransAmount ?? null, 'msisdn' => $record->MSISDN ?? null, 'status' => $record->status], 'sacco', 'create');
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode([
            'ResultCode' => $record->status === 'posted' ? 0 : 1,
            'ResultDesc' => $record->response_message,
        ]));
        return $response;
    }

    public function index()
    {
        $transactions = SaccoMpesaC2bTransaction::orderByDesc('id')->paginate(30);
        return theme_view('sacco::mpesa_c2b.index', compact('transactions'));
    }
}
