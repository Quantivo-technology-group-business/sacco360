<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Client\Entities\Client;
use Modules\Sacco\Entities\SaccoExitSettlement;
use Modules\ActivityLog\Services\AuditLogger;
use Modules\Sacco\Services\ExitSettlementService;

class ExitSettlementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.exit.index'])->only(['index', 'show', 'calculate']);
        $this->middleware(['permission:sacco.exit.create'])->only(['create', 'store', 'approve', 'process']);
    }

    public function index()
    {
        $settlements = SaccoExitSettlement::orderByDesc('id')->paginate(20);
        return theme_view('sacco::exit.index', compact('settlements'));
    }

    public function create(Request $request)
    {
        $client = $request->client_id ? Client::find($request->client_id) : null;
        $calculation = $client ? (new ExitSettlementService())->calculate($client->id) : null;
        return theme_view('sacco::exit.create', compact('client', 'calculation'));
    }

    public function calculate(Request $request)
    {
        $request->validate(['client_id' => 'required']);
        $calculation = (new ExitSettlementService())->calculate((int)$request->client_id);
        return response()->json($calculation);
    }

    public function store(Request $request, ExitSettlementService $service)
    {
        $request->validate([
            'client_id' => 'required',
            'exit_reason' => 'required|in:resignation,retirement,death,expulsion,other',
            'exit_date' => 'required|date',
        ]);

        $settlement = $service->createDraft((int)$request->client_id, $request->all());
        AuditLogger::log('Exit Settlement Created', $settlement, ['id' => $settlement->id, 'client_id' => $request->client_id, 'exit_reason' => $request->exit_reason, 'exit_date' => $request->exit_date], 'sacco', 'create');
        return redirect('sacco/exit/' . $settlement->id . '/show')->with('success', 'Exit settlement draft created.');
    }

    public function show($id)
    {
        $settlement = SaccoExitSettlement::findOrFail($id);
        $client = Client::find($settlement->client_id);
        return theme_view('sacco::exit.show', compact('settlement', 'client'));
    }

    public function approve($id, ExitSettlementService $service)
    {
        $settlement = SaccoExitSettlement::findOrFail($id);
        $service->approve($settlement);
        AuditLogger::log('Exit Settlement Approved', $settlement, ['id' => $settlement->id, 'client_id' => $settlement->client_id], 'sacco', 'approve');
        return redirect('sacco/exit/' . $id . '/show')->with('success', 'Settlement approved.');
    }

    public function process($id, ExitSettlementService $service)
    {
        $settlement = SaccoExitSettlement::findOrFail($id);
        $service->process($settlement);
        AuditLogger::log('Exit Settlement Processed', $settlement, ['id' => $settlement->id, 'client_id' => $settlement->client_id], 'sacco', 'update');
        return redirect('sacco/exit/' . $id . '/show')->with('success', 'Settlement processed.');
    }
}
