<?php

namespace Modules\Sacco\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoStatementSchedule;
use Modules\Sacco\Entities\SaccoStatementLog;
use Modules\Sacco\Services\EStatementService;
use Modules\Client\Entities\Client;

class EStatementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware('permission:sacco.estatements.index')->only(['index', 'logs']);
        $this->middleware('permission:sacco.estatements.create')->only(['create', 'store', 'sendOnDemand']);
        $this->middleware('permission:sacco.estatements.run')->only(['runSchedules']);
    }

    public function index()
    {
        $schedules = SaccoStatementSchedule::with('client')
            ->orderByDesc('id')
            ->paginate(20);
        return theme_view('sacco::estatement.index', compact('schedules'));
    }

    public function create()
    {
        $clients = Client::where('status', 1)->orderBy('first_name')->get();
        return theme_view('sacco::estatement.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:150',
            'statement_type'   => 'required|in:full,savings,loans,shares',
            'frequency'        => 'required|in:monthly,quarterly,annually',
            'delivery_channel' => 'required|in:email,sms,portal,print',
            'send_day_of_month'=> 'required|integer|min:1|max:28',
        ]);

        $schedule = SaccoStatementSchedule::create(array_merge(
            $request->only([
                'client_id', 'name', 'statement_type', 'frequency',
                'delivery_channel', 'send_day_of_month', 'lookback_months',
                'include_loans', 'include_savings', 'include_shares', 'include_summary',
            ]),
            [
                'created_by_id' => Auth::id(),
                'is_active'     => 1,
                'next_run_date' => Carbon::today()->startOfMonth()->addDays(
                    min($request->send_day_of_month, 28) - 1
                ),
            ]
        ));

        \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();
        return redirect('sacco/estatement');
    }

    public function logs(Request $request)
    {
        $query = SaccoStatementLog::with('client')
            ->orderByDesc('id');

        if ($request->get('status')) {
            $query->where('status', $request->status);
        }
        if ($request->get('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $logs = $query->paginate(30);
        return theme_view('sacco::estatement.logs', compact('logs'));
    }

    public function sendOnDemand(Request $request, EStatementService $service)
    {
        $request->validate([
            'client_id'      => 'required|exists:clients,id',
            'statement_type' => 'required|in:full,savings,loans,shares',
            'from_date'      => 'required|date',
            'to_date'        => 'required|date|after_or_equal:from_date',
        ]);

        $client = Client::findOrFail($request->client_id);
        $log    = $service->sendOnDemand(
            $client,
            $request->statement_type,
            Carbon::parse($request->from_date),
            Carbon::parse($request->to_date),
            Auth::id()
        );

        if ($log->status === 'sent') {
            \flash('Statement queued for delivery.')->success()->important();
        } else {
            \flash('Statement delivery failed: ' . $log->error_message)->error()->important();
        }

        return redirect('sacco/estatement/logs');
    }

    public function showSendDemand()
    {
        $clients = Client::where('status', 1)->orderBy('first_name')->get();
        return theme_view('sacco::estatement.send_demand', compact('clients'));
    }

    public function runSchedules(EStatementService $service)
    {
        $count = $service->runDueSchedules();
        \flash("{$count} statement(s) processed.")->success()->important();
        return redirect('sacco/estatement');
    }
}
