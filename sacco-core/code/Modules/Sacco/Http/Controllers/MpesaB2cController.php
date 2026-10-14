<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Sacco\Entities\SaccoMpesaB2cTransaction;
use Modules\Sacco\Services\MpesaB2cService;

class MpesaB2cController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa'])->only(['index']);
        $this->middleware(['permission:sacco.mpesa_b2c.index'])->only(['index']);
    }

    public function index(Request $request)
    {
        $status = $request->status;
        $query = SaccoMpesaB2cTransaction::orderByDesc('id');
        if ($status) {
            $query->where('status', $status);
        }
        $transactions = $query->paginate(30)->appends($request->input());
        $simulation = (new MpesaB2cService())->isSimulationMode();

        return theme_view('sacco::mpesa_b2c.index', compact('transactions', 'simulation', 'status'));
    }

    public function result(Request $request, MpesaB2cService $service)
    {
        $payload = $request->all();
        if (empty($payload)) {
            $payload = json_decode($request->getContent(), true) ?? [];
        }
        $result = $service->handleResultCallback($payload);
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($result));
        return $response;
    }

    public function timeout(Request $request, MpesaB2cService $service)
    {
        $payload = $request->all();
        if (empty($payload)) {
            $payload = json_decode($request->getContent(), true) ?? [];
        }
        $result = $service->handleTimeoutCallback($payload);
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($result));
        return $response;
    }
}
