<?php
namespace Modules\Investment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Investment\Entities\InvestmentPlan;
use Modules\Investment\Services\ProfitCalculatorService;

class CalculatorController extends Controller
{
    public function __construct() { $this->middleware('auth'); }

    /** AJAX: calculate projected returns for given inputs */
    public function calculate(Request $request)
    {
        $request->validate([
            'plan_id'         => ['required','exists:investment_plans,id'],
            'principal'       => ['required','numeric','min:1'],
            'duration_months' => ['required','integer','min:1'],
            'start_date'      => ['required','date'],
        ]);

        $plan      = InvestmentPlan::findOrFail($request->plan_id);
        $principal = (float)$request->principal;
        $rate      = $plan->effectiveRate($principal);

        // Build a temporary investment object for the schedule
        $inv = new \Modules\Investment\Entities\MemberInvestment([
            'principal_amount'   => $principal,
            'effective_rate'     => $rate,
            'start_date'         => $request->start_date,
            'maturity_date'      => \Carbon\Carbon::parse($request->start_date)->addMonths($request->duration_months)->format('Y-m-d'),
            'duration_months'    => $request->duration_months,
            'investment_plan_id' => $plan->id,
        ]);
        $inv->setRelation('plan', $plan);

        $svc      = app(ProfitCalculatorService::class);
        $schedule = $svc->projectedSchedule($inv);

        $totalProfit = collect($schedule)->sum('profit');
        $finalBalance = $principal + $totalProfit;

        return response()->json([
            'rate'          => $rate,
            'method'        => $plan->interest_method,
            'total_profit'  => round($totalProfit, 2),
            'final_balance' => round($finalBalance, 2),
            'schedule'      => $schedule,
        ]);
    }
}
