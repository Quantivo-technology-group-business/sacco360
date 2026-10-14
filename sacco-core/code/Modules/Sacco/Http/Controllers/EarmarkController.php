<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Client\Entities\Client;
use Modules\Sacco\Entities\SaccoMemberEarmark;
use Modules\Sacco\Services\EarmarkService;

class EarmarkController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.earmarks.index'])->only(['index', 'ajaxForClient']);
        $this->middleware(['permission:sacco.earmarks.create'])->only(['create', 'store']);
    }

    public function index(Request $request)
    {
        $query = SaccoMemberEarmark::with('client')->orderByDesc('id');
        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'active');
        }
        $earmarks = $query->paginate(20);
        return theme_view('sacco::earmark.index', compact('earmarks'));
    }

    public function create(Request $request)
    {
        $client = $request->client_id ? Client::find($request->client_id) : null;
        $member360 = $client && class_exists(\Modules\Sacco\Services\Member360Service::class)
            ? (new \Modules\Sacco\Services\Member360Service())->getSummary($client->id)
            : null;
        return theme_view('sacco::earmark.create', compact('client', 'member360'));
    }

    public function store(Request $request, EarmarkService $service)
    {
        $request->validate([
            'client_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'severity' => 'required|in:info,warning,critical',
            'restriction_type' => 'required|in:alert,block_withdrawal,block_deposit,block_all_transactions,block_loans',
        ]);

        $service->create($request->only([
            'client_id', 'title', 'message', 'severity', 'restriction_type', 'show_at_teller', 'expires_at',
        ]));

        \flash('Member earmark created')->success()->important();
        return redirect('sacco/earmark?client_id=' . $request->client_id);
    }

    public function revoke($id)
    {
        $earmark = SaccoMemberEarmark::findOrFail($id);
        (new EarmarkService())->revoke($earmark);
        \flash('Earmark revoked')->success()->important();
        return redirect()->back();
    }

    public function ajaxForClient($clientId)
    {
        return response()->json((new EarmarkService())->getActiveForClient((int)$clientId, true));
    }
}
