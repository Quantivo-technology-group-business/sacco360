<?php
namespace Modules\Investment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Branch\Entities\Branch;
use Modules\Investment\Entities\InvestmentPlan;
use Modules\Investment\Entities\MemberInvestment;
use Modules\Investment\Entities\InvestmentTransaction;
use PDF;

class InvestmentReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','2fa']);
        $this->middleware(['permission:investment.reports.index']);
    }

    public function index()
    {
        $plans    = InvestmentPlan::all();
        $branches = Branch::all();
        return theme_view('investment::report.index', compact('plans','branches'));
    }

    /** Portfolio summary: total invested, total profit accrued, total profit paid, by plan */
    public function portfolio(Request $request)
    {
        $branch_id = $request->branch_id;
        $plan_id   = $request->plan_id;

        $data = MemberInvestment::with('plan','client','branch')
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->when($plan_id,   fn($q) => $q->where('investment_plan_id', $plan_id))
            ->whereIn('status', ['active','matured'])
            ->get();

        $summary = [
            'total_invested'       => $data->sum('principal_amount'),
            'total_profit_accrued' => $data->sum('total_profit_accrued'),
            'total_profit_paid'    => $data->sum('total_profit_paid'),
            'profit_due'           => $data->sum(fn($i) => $i->profit_due),
            'count'                => $data->count(),
        ];

        $byPlan = $data->groupBy('investment_plan_id')->map(function ($group) {
            return [
                'plan'          => optional($group->first()->plan)->name,
                'count'         => $group->count(),
                'invested'      => $group->sum('principal_amount'),
                'profit_accrued'=> $group->sum('total_profit_accrued'),
                'profit_paid'   => $group->sum('total_profit_paid'),
            ];
        });

        $plans    = InvestmentPlan::all();
        $branches = Branch::all();

        if ($request->download === '1') {
            $pdf = PDF::loadView(theme_view_file('investment::report.portfolio_pdf'),
                compact('data','summary','byPlan','plans','branches','branch_id','plan_id'));
            return $pdf->download('InvestmentPortfolio.pdf');
        }

        return theme_view('investment::report.portfolio',
            compact('data','summary','byPlan','plans','branches','branch_id','plan_id'));
    }

    /** Maturing investments in a date range */
    public function maturities(Request $request)
    {
        $start_date = $request->start_date ?? date('Y-m-01');
        $end_date   = $request->end_date   ?? date('Y-m-t');

        $data = MemberInvestment::with('client','plan')
            ->whereBetween('maturity_date', [$start_date, $end_date])
            ->whereIn('status', ['active','pending'])
            ->orderBy('maturity_date')
            ->get();

        return theme_view('investment::report.maturities', compact('data','start_date','end_date'));
    }

    /** Profit due (accrued but unpaid) */
    public function profitDue(Request $request)
    {
        $data = MemberInvestment::with('client','plan')
            ->where('status','active')
            ->whereRaw('total_profit_accrued > total_profit_paid')
            ->orderBy('next_profit_posting_date')
            ->get()
            ->map(function ($i) {
                $i->profit_due_amount = $i->profit_due;
                return $i;
            });

        $totalDue = $data->sum('profit_due_amount');
        return theme_view('investment::report.profit_due', compact('data','totalDue'));
    }
}
