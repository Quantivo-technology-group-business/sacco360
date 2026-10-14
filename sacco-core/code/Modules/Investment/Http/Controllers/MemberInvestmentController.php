<?php
namespace Modules\Investment\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Branch\Entities\Branch;
use Modules\Client\Entities\Client;
use Modules\Core\Entities\Currency;
use Modules\Investment\Entities\InvestmentPlan;
use Modules\Investment\Entities\MemberInvestment;
use Modules\Investment\Entities\InvestmentTransaction;
use Modules\Investment\Services\InvestmentGLService;
use Modules\Investment\Services\ProfitCalculatorService;

class MemberInvestmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','2fa']);
        $this->middleware(['permission:investment.investments.index'])->only(['index','show']);
        $this->middleware(['permission:investment.investments.create'])->only(['create','store']);
        $this->middleware(['permission:investment.investments.edit'])->only(['edit','update']);
        $this->middleware(['permission:investment.investments.approve'])->only(['approve','activate']);
        $this->middleware(['permission:investment.investments.profit'])->only(['postProfit','payProfit']);
        $this->middleware(['permission:investment.investments.close'])->only(['close','withdraw']);
    }

    public function index(Request $request)
    {
        $status    = $request->status;
        $client_id = $request->client_id;
        $plan_id   = $request->plan_id;

        $investments = MemberInvestment::with('client','plan','branch')
            ->when($status,    fn($q) => $q->where('status', $status))
            ->when($client_id, fn($q) => $q->where('client_id', $client_id))
            ->when($plan_id,   fn($q) => $q->where('investment_plan_id', $plan_id))
            ->latest()->paginate(25)->appends($request->all());

        $plans    = InvestmentPlan::active()->get();
        $statuses = ['pending','active','matured','closed','withdrawn'];

        return theme_view('investment::investment.index', compact('investments','plans','statuses','status','plan_id'));
    }

    public function create()
    {
        $plans      = InvestmentPlan::active()->where('open_to_members',1)->get();
        $clients    = Client::orderBy('first_name')->get();
        $branches   = Branch::all();
        $currencies = Currency::all();
        return theme_view('investment::investment.create', compact('plans','clients','branches','currencies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id'           => ['required','exists:clients,id'],
            'investment_plan_id'  => ['required','exists:investment_plans,id'],
            'principal_amount'    => ['required','numeric','min:1'],
            'start_date'          => ['required','date'],
            'duration_months'     => ['nullable','integer','min:1'],
        ]);

        $plan      = InvestmentPlan::findOrFail($request->investment_plan_id);
        $principal = (float)$request->principal_amount;

        if ($plan->min_amount && $principal < $plan->min_amount) {
            return back()->withErrors(['principal_amount' => "Minimum investment is {$plan->min_amount}."])->withInput();
        }
        if ($plan->max_amount && $principal > $plan->max_amount) {
            return back()->withErrors(['principal_amount' => "Maximum investment is {$plan->max_amount}."])->withInput();
        }

        $duration     = $request->duration_months ?: $plan->min_duration_months;
        $start        = Carbon::parse($request->start_date);
        $maturity     = $plan->max_duration_months ? $start->copy()->addMonths($duration) : null;
        $effectiveRate= $plan->effectiveRate($principal);

        // Next profit posting date
        $nextPost = match($plan->profit_posting_frequency) {
            'monthly'     => $start->copy()->addMonth(),
            'quarterly'   => $start->copy()->addMonths(3),
            'semi_annual' => $start->copy()->addMonths(6),
            'annual'      => $start->copy()->addYear(),
            default       => $maturity,  // on_maturity
        };

        $inv = MemberInvestment::create([
            'account_number'           => 'INV-' . strtoupper(uniqid()),
            'client_id'                => $request->client_id,
            'investment_plan_id'       => $plan->id,
            'branch_id'                => $request->branch_id,
            'currency_id'              => $request->currency_id ?? 1,
            'created_by_id'            => Auth::id(),
            'principal_amount'         => $principal,
            'units_held'               => $plan->unit_price ? (int)floor($principal / $plan->unit_price) : null,
            'unit_price_at_purchase'   => $plan->unit_price,
            'effective_rate'           => $effectiveRate,
            'start_date'               => $request->start_date,
            'maturity_date'            => $maturity?->format('Y-m-d'),
            'duration_months'          => $duration,
            'next_profit_posting_date' => $nextPost?->format('Y-m-d'),
            'status'                   => 'pending',
            'notes'                    => $request->notes,
        ]);

        // Record transaction
        InvestmentTransaction::create([
            'member_investment_id' => $inv->id,
            'created_by_id'        => Auth::id(),
            'transaction_type'     => 'investment',
            'amount'               => $principal,
            'date'                 => $request->start_date,
            'reference'            => $inv->account_number,
            'notes'                => 'Initial investment',
            'transaction_number'   => uniqid('INV-'),
        ]);

        activity()->on($inv)->log('Create Member Investment');
        \flash('Investment created. Activate it to start accruing profit.')->success()->important();
        return redirect('investment/'.$inv->id.'/show');
    }

    public function show($id)
    {
        $inv         = MemberInvestment::with('client','plan','branch','currency','transactions')->findOrFail($id);
        $calculator  = app(ProfitCalculatorService::class);
        $schedule    = $calculator->projectedSchedule($inv);
        $maturityCalc= $calculator->maturityPayout($inv);

        return theme_view('investment::investment.show', compact('inv','schedule','maturityCalc'));
    }

    // ── Activate (pending → active) ─────────────────────────────────────────
    public function activate(Request $request, $id)
    {
        $inv = MemberInvestment::findOrFail($id);
        if ($inv->status !== 'pending') {
            \flash('Only pending investments can be activated.')->error()->important();
            return redirect('investment/'.$id.'/show');
        }

        $inv->update(['status' => 'active']);

        // Post GL entries
        app(InvestmentGLService::class)->postInvestment($inv, $inv->start_date->format('Y-m-d'), (float)$inv->principal_amount);

        activity()->on($inv)->log('Activate Investment');
        \flash('Investment activated. Profit will accrue from '.$inv->start_date->format('d M Y').'.')->success()->important();
        return redirect('investment/'.$id.'/show');
    }

    // ── Post Profit (accrue + optional pay) ─────────────────────────────────
    public function postProfit(Request $request, $id)
    {
        $request->validate([
            'profit_amount' => ['required','numeric','min:0.01'],
            'date'          => ['required','date'],
            'pay_now'       => ['nullable','boolean'],
        ]);

        $inv    = MemberInvestment::findOrFail($id);
        $amount = (float)$request->profit_amount;
        $date   = $request->date;
        $gl     = app(InvestmentGLService::class);

        // Accrue
        $txnRef = uniqid('INV-ACC-');
        InvestmentTransaction::create([
            'member_investment_id' => $inv->id,
            'created_by_id'        => Auth::id(),
            'transaction_type'     => 'profit_accrual',
            'amount'               => $amount,
            'date'                 => $date,
            'reference'            => $txnRef,
            'notes'                => $request->notes ?? 'Profit accrual',
            'transaction_number'   => $txnRef,
        ]);

        $gl->postProfitAccrual($inv, $date, $amount);

        $plan = $inv->plan;
        $nextPost = match($plan->profit_posting_frequency) {
            'monthly'     => Carbon::parse($date)->addMonth(),
            'quarterly'   => Carbon::parse($date)->addMonths(3),
            'semi_annual' => Carbon::parse($date)->addMonths(6),
            'annual'      => Carbon::parse($date)->addYear(),
            default       => $inv->maturity_date,
        };

        $inv->increment('total_profit_accrued', $amount);
        $inv->update([
            'last_profit_posting_date' => $date,
            'next_profit_posting_date' => $nextPost?->format('Y-m-d'),
        ]);

        // Optional: pay immediately
        if ($request->pay_now) {
            $payRef = uniqid('INV-PAY-');
            InvestmentTransaction::create([
                'member_investment_id' => $inv->id,
                'created_by_id'        => Auth::id(),
                'transaction_type'     => 'profit_payment',
                'amount'               => $amount,
                'date'                 => $date,
                'reference'            => $payRef,
                'notes'                => 'Profit paid to member',
                'transaction_number'   => $payRef,
            ]);
            $gl->postProfitPayment($inv, $date, $amount);
            $inv->increment('total_profit_paid', $amount);
        }

        activity()->on($inv)->log('Post Profit '.$amount);
        \flash('Profit of '.number_format($amount,2).' posted'.($request->pay_now ? ' and paid.' : ' (accrued).').'.')->success()->important();
        return redirect('investment/'.$id.'/show');
    }

    // ── Pay outstanding profit ───────────────────────────────────────────────
    public function payProfit(Request $request, $id)
    {
        $request->validate(['amount'=>['required','numeric','min:0.01'],'date'=>['required','date']]);
        $inv    = MemberInvestment::findOrFail($id);
        $amount = min((float)$request->amount, $inv->profit_due);

        $payRef = uniqid('INV-PAY-');
        InvestmentTransaction::create([
            'member_investment_id' => $inv->id, 'created_by_id' => Auth::id(),
            'transaction_type' => 'profit_payment', 'amount' => $amount,
            'date' => $request->date, 'transaction_number' => $payRef, 'notes' => 'Profit payment',
        ]);
        app(InvestmentGLService::class)->postProfitPayment($inv, $request->date, $amount);
        $inv->increment('total_profit_paid', $amount);

        \flash('Profit payment of '.number_format($amount,2).' processed.')->success()->important();
        return redirect('investment/'.$id.'/show');
    }

    // ── Maturity Payout ──────────────────────────────────────────────────────
    public function mature(Request $request, $id)
    {
        $request->validate(['date'=>['required','date']]);
        $inv      = MemberInvestment::findOrFail($id);
        $calc     = app(ProfitCalculatorService::class)->maturityPayout($inv);
        $gl       = app(InvestmentGLService::class);

        $txnRef = uniqid('INV-MAT-');
        InvestmentTransaction::create([
            'member_investment_id' => $inv->id, 'created_by_id' => Auth::id(),
            'transaction_type' => 'maturity_payout', 'amount' => $calc['payout_total'],
            'date' => $request->date, 'transaction_number' => $txnRef,
            'notes' => 'Maturity payout — principal: '.number_format($calc['principal'],2).' + profit: '.number_format($calc['remaining_profit'],2),
        ]);
        $gl->postMaturityPayout($inv, $request->date, $calc['principal'], $calc['remaining_profit']);

        $inv->update([
            'status'               => 'matured',
            'closed_date'          => $request->date,
            'payout_amount'        => $calc['payout_total'],
            'total_profit_accrued' => $inv->total_profit_accrued + $calc['remaining_profit'],
            'total_profit_paid'    => $inv->total_profit_paid    + $calc['remaining_profit'],
        ]);

        activity()->on($inv)->log('Maturity Payout');
        \flash('Maturity payout of '.number_format($calc['payout_total'],2).' processed.')->success()->important();
        return redirect('investment/'.$id.'/show');
    }

    // ── Early Withdrawal ─────────────────────────────────────────────────────
    public function withdraw(Request $request, $id)
    {
        $request->validate(['date'=>['required','date']]);
        $inv   = MemberInvestment::findOrFail($id);
        if (!$inv->plan->allow_early_withdrawal) {
            \flash('This plan does not allow early withdrawal.')->error()->important();
            return redirect('investment/'.$id.'/show');
        }
        $calc   = app(ProfitCalculatorService::class)->earlyWithdrawalPayout($inv);
        $gl     = app(InvestmentGLService::class);

        $txnRef = uniqid('INV-WD-');
        InvestmentTransaction::create([
            'member_investment_id' => $inv->id, 'created_by_id' => Auth::id(),
            'transaction_type' => 'withdrawal', 'amount' => $calc['net_payout'],
            'date' => $request->date, 'transaction_number' => $txnRef,
            'notes' => 'Early withdrawal — penalty: '.number_format($calc['penalty'],2),
        ]);
        if ($calc['penalty'] > 0) {
            InvestmentTransaction::create([
                'member_investment_id' => $inv->id, 'created_by_id' => Auth::id(),
                'transaction_type' => 'penalty', 'amount' => $calc['penalty'],
                'date' => $request->date, 'transaction_number' => $txnRef,
                'notes' => 'Early withdrawal penalty',
            ]);
        }
        $gl->postEarlyWithdrawal($inv, $request->date, $calc['principal'], $calc['penalty']);

        $inv->update(['status' => 'withdrawn', 'closed_date' => $request->date, 'payout_amount' => $calc['net_payout']]);
        \flash('Early withdrawal processed. Net payout: '.number_format($calc['net_payout'],2).'.')->success()->important();
        return redirect('investment/'.$id.'/show');
    }
}
