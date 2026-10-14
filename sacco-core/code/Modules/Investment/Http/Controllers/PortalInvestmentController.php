<?php
namespace Modules\Investment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Investment\Entities\InvestmentPlan;
use Modules\Investment\Entities\MemberInvestment;
use Modules\Investment\Services\ProfitCalculatorService;

class PortalInvestmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'portal.permission']);
    }

    private function clientId(): int
    {
        return session('client_id');
    }

    /** Member's own investment accounts */
    public function index()
    {
        $clientId    = $this->clientId();
        $investments = MemberInvestment::with('plan')
            ->where('client_id', $clientId)
            ->latest()->paginate(15);

        $summary = [
            'total_invested'       => MemberInvestment::where('client_id',$clientId)->sum('principal_amount'),
            'total_profit_accrued' => MemberInvestment::where('client_id',$clientId)->sum('total_profit_accrued'),
            'total_profit_paid'    => MemberInvestment::where('client_id',$clientId)->sum('total_profit_paid'),
            'profit_due'           => MemberInvestment::where('client_id',$clientId)->where('status','active')
                                          ->selectRaw('SUM(total_profit_accrued - total_profit_paid) as due')->value('due') ?? 0,
            'current_value'        => MemberInvestment::where('client_id',$clientId)->where('status','active')
                                          ->selectRaw('SUM(principal_amount + total_profit_accrued) as val')->value('val') ?? 0,
        ];

        return theme_view('investment::portal.index', compact('investments','summary'));
    }

    /** All available investment plans — public showcase */
    public function plans()
    {
        $plans = InvestmentPlan::with('tiers')
            ->where('active', 1)
            ->where('open_to_members', 1)
            ->orderByDesc('return_rate')
            ->get();

        return theme_view('investment::portal.plans', compact('plans'));
    }

    /** Single plan detail with live calculator */
    public function planDetail($id)
    {
        $plan = InvestmentPlan::with('tiers')->where('active',1)->where('open_to_members',1)->findOrFail($id);
        return theme_view('investment::portal.plan_detail', compact('plan'));
    }

    /** Single investment account detail for the member */
    public function show($id)
    {
        $inv = MemberInvestment::with('plan','transactions')
            ->where('client_id', $this->clientId())
            ->findOrFail($id);

        $schedule    = app(ProfitCalculatorService::class)->projectedSchedule($inv);
        $maturityCalc= app(ProfitCalculatorService::class)->maturityPayout($inv);

        return theme_view('investment::portal.show', compact('inv','schedule','maturityCalc'));
    }
}
