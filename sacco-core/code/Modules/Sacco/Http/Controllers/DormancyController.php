<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoDormancyRule;
use Modules\Sacco\Entities\SaccoDormancyLog;
use Modules\Sacco\Services\DormancyService;
use Modules\Client\Entities\Client;

class DormancyController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware('permission:sacco.dormancy.index')->only(['index', 'dormantMembers', 'history']);
        $this->middleware('permission:sacco.dormancy.manage')->only(['storeRule', 'updateRule', 'runCheck', 'reactivate']);
    }

    public function index()
    {
        $rule          = SaccoDormancyRule::where('is_active', 1)->first();
        $dormantCount  = Client::where('member_status_detail', 'dormant')->count();
        $recentLogs    = SaccoDormancyLog::with('client')->orderByDesc('action_date')->limit(20)->get();
        return theme_view('sacco::dormancy.index', compact('rule', 'dormantCount', 'recentLogs'));
    }

    public function storeRule(Request $request)
    {
        $request->validate([
            'name'               => 'required|string|max:150',
            'inactivity_months'  => 'required|integer|min:1',
            'minimum_balance'    => 'nullable|numeric|min:0',
        ]);

        // Deactivate old rules before creating/updating
        SaccoDormancyRule::where('is_active', 1)->update(['is_active' => 0]);

        SaccoDormancyRule::create(array_merge(
            $request->only([
                'name', 'description', 'inactivity_months', 'minimum_balance',
                'block_withdrawals', 'block_new_loans',
                'charge_dormancy_fee', 'dormancy_fee_amount', 'fee_frequency',
                'notify_member_on_flag',
            ]),
            ['is_active' => 1]
        ));

        \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();
        return redirect('sacco/dormancy');
    }

    public function dormantMembers()
    {
        $members = Client::where('member_status_detail', 'dormant')
            ->orderBy('first_name')
            ->paginate(30);

        // Attach the most recent dormancy log for each member
        $clientIds = $members->pluck('id');
        $latestLogs = SaccoDormancyLog::whereIn('client_id', $clientIds)
            ->orderByDesc('action_date')
            ->get()
            ->groupBy('client_id')
            ->map(fn($logs) => $logs->first());

        $members->each(function ($m) use ($latestLogs) {
            $m->latestDormancyLog = $latestLogs->get($m->id);
        });

        return theme_view('sacco::dormancy.members', compact('members'));
    }

    public function runCheck(DormancyService $service)
    {
        $flagged = $service->runDormancyCheck();
        \flash("{$flagged} member(s) flagged as dormant.")->warning()->important();
        return redirect('sacco/dormancy');
    }

    public function reactivate(Request $request, $clientId, DormancyService $service)
    {
        $request->validate(['notes' => 'nullable|string']);
        $client = Client::findOrFail($clientId);
        $service->reactivate($client, Auth::id(), $request->notes ?? '');
        \flash('Member reactivated.')->success()->important();
        return back();
    }

    public function history($clientId)
    {
        $client  = Client::findOrFail($clientId);
        $history = (new DormancyService())->getClientHistory($clientId);
        return theme_view('sacco::dormancy.history', compact('client', 'history'));
    }
}
