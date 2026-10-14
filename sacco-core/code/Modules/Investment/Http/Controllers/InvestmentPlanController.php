<?php
namespace Modules\Investment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Investment\Entities\InvestmentPlan;
use Modules\Investment\Entities\InvestmentPlanTier;

class InvestmentPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','2fa']);
        $this->middleware(['permission:investment.plans.index'])->only(['index','show']);
        $this->middleware(['permission:investment.plans.create'])->only(['create','store']);
        $this->middleware(['permission:investment.plans.edit'])->only(['edit','update']);
        $this->middleware(['permission:investment.plans.destroy'])->only(['destroy']);
    }

    public function index()
    {
        $plans = InvestmentPlan::withCount('investments')->orderBy('name')->get();
        return theme_view('investment::plan.index', compact('plans'));
    }

    public function create()
    {
        $accounts = ChartOfAccount::active()->orderBy('gl_code')->get();
        return theme_view('investment::plan.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => ['required','string','max:255'],
            'code'        => ['required','string','unique:investment_plans,code'],
            'plan_type'   => ['required'],
            'return_rate' => ['required','numeric','min:0'],
            'min_amount'  => ['required','numeric','min:0'],
        ]);

        $plan = InvestmentPlan::create(array_merge($request->except('_token','tiers'), [
            'created_by_id' => Auth::id(),
        ]));

        // Save tiers if tiered rate
        if ($request->return_rate_type === 'tiered' && $request->has('tiers')) {
            foreach ($request->tiers as $tier) {
                if (empty($tier['return_rate'])) continue;
                InvestmentPlanTier::create([
                    'investment_plan_id' => $plan->id,
                    'min_amount'  => $tier['min_amount']  ?? 0,
                    'max_amount'  => $tier['max_amount']  ?: null,
                    'return_rate' => $tier['return_rate'],
                    'label'       => $tier['label']       ?? null,
                ]);
            }
        }

        activity()->on($plan)->log('Create Investment Plan');
        \flash('Investment plan created successfully.')->success()->important();
        return redirect('investment/plan');
    }

    public function show($id)
    {
        $plan        = InvestmentPlan::with('tiers','investments.client')->findOrFail($id);
        $investments = $plan->investments()->with('client')->latest()->paginate(20);
        return theme_view('investment::plan.show', compact('plan','investments'));
    }

    public function edit($id)
    {
        $plan     = InvestmentPlan::with('tiers')->findOrFail($id);
        $accounts = ChartOfAccount::active()->orderBy('gl_code')->get();
        return theme_view('investment::plan.edit', compact('plan','accounts'));
    }

    public function update(Request $request, $id)
    {
        $plan = InvestmentPlan::findOrFail($id);
        $request->validate([
            'name'        => ['required','string','max:255'],
            'return_rate' => ['required','numeric','min:0'],
            'min_amount'  => ['required','numeric','min:0'],
        ]);

        $plan->update($request->except('_token','tiers'));

        // Rebuild tiers
        if ($request->return_rate_type === 'tiered') {
            $plan->tiers()->delete();
            foreach ((array)$request->tiers as $tier) {
                if (empty($tier['return_rate'])) continue;
                InvestmentPlanTier::create([
                    'investment_plan_id' => $plan->id,
                    'min_amount'  => $tier['min_amount']  ?? 0,
                    'max_amount'  => $tier['max_amount']  ?: null,
                    'return_rate' => $tier['return_rate'],
                    'label'       => $tier['label']       ?? null,
                ]);
            }
        }

        activity()->on($plan)->log('Update Investment Plan');
        \flash('Investment plan updated.')->success()->important();
        return redirect('investment/plan');
    }

    public function destroy($id)
    {
        $plan = InvestmentPlan::findOrFail($id);
        if ($plan->investments()->whereIn('status',['active','pending'])->exists()) {
            \flash('Cannot delete — this plan has active investments.')->error()->important();
            return redirect()->back();
        }
        $plan->delete();
        activity()->log('Delete Investment Plan '.$id);
        \flash('Plan deleted.')->success()->important();
        return redirect('investment/plan');
    }
}
