<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sacco\Entities\SaccoCrossSellCampaign;
use Modules\Sacco\Services\CrossSellService;

class CrossSellController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.cross_sell.index']);
    }

    public function index(CrossSellService $service)
    {
        $campaigns = SaccoCrossSellCampaign::orderByDesc('id')->get();
        $eligible = $service->getEligibleMembers();
        return theme_view('sacco::cross_sell.index', compact('campaigns', 'eligible'));
    }

    public function create()
    {
        return theme_view('sacco::cross_sell.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'source_wing' => 'required|in:fosa,bosa,any',
            'target_wing' => 'required|in:bosa,fosa,share',
            'min_balance' => 'required|numeric|min:0',
        ]);
        SaccoCrossSellCampaign::create($request->only([
            'name', 'source_wing', 'target_wing', 'min_balance', 'message', 'start_date', 'end_date', 'active',
        ]));
        return redirect('sacco/cross_sell')->with('success', 'Campaign created.');
    }
}
