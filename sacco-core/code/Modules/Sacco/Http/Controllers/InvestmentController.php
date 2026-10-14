<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoInvestment;
use Modules\Sacco\Entities\SaccoInvestmentValuation;
use Modules\Sacco\Services\InvestmentService;
use Yajra\DataTables\Facades\DataTables;

class InvestmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware('permission:sacco.investments.index')->only(['index', 'show', 'portfolio', 'get_investments']);
        $this->middleware('permission:sacco.investments.create')->only(['create', 'store', 'addValuation', 'markMatured']);
    }

    public function index(InvestmentService $service)
    {
        $summary      = $service->getPortfolioSummary();
        $maturingSoon = $service->getUpcomingMaturities(30);
        return theme_view('sacco::investment.index', compact('summary', 'maturingSoon'));
    }

    public function get_investments(Request $request)
    {
        $query = SaccoInvestment::orderByDesc('id');

        return DataTables::of($query)
            ->addColumn('type_label', fn($i) => ucfirst(str_replace('_', ' ', $i->type)))
            ->addColumn('principal_fmt', fn($i) => number_format($i->principal_amount, 2))
            ->addColumn('value_fmt', fn($i) => number_format($i->current_value ?? $i->principal_amount, 2))
            ->addColumn('rate_fmt', fn($i) => $i->interest_rate ? $i->interest_rate . '%' : '—')
            ->addColumn('status_badge', function ($i) {
                $c = ['active' => 'success', 'matured' => 'info', 'liquidated' => 'secondary'][$i->status] ?? 'secondary';
                return '<span class="badge badge-' . $c . '">' . ucfirst($i->status) . '</span>';
            })
            ->addColumn('action', fn($i) =>
                '<a href="' . url('sacco/investment/' . $i->id . '/show') . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View</a>'
            )
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    public function create()
    {
        return theme_view('sacco::investment.create');
    }

    public function store(Request $request, InvestmentService $service)
    {
        $request->validate([
            'name'             => 'required|string|max:150',
            'type'             => 'required|string',
            'principal_amount' => 'required|numeric|min:1',
            'investment_date'  => 'required|date',
        ]);

        $service->create(array_merge(
            $request->only([
                'name', 'reference_number', 'type', 'issuer',
                'principal_amount', 'interest_rate',
                'investment_date', 'maturity_date', 'tenure_days',
                'expected_return', 'chart_of_account_id',
                'branch_id', 'notes',
            ]),
            ['created_by_id' => Auth::id(), 'status' => 'active']
        ));

        \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();
        return redirect('sacco/investment');
    }

    public function show($id)
    {
        $investment = SaccoInvestment::with('valuations')->findOrFail($id);
        return theme_view('sacco::investment.show', compact('investment'));
    }

    public function addValuation(Request $request, $id, InvestmentService $service)
    {
        $request->validate([
            'valuation_date' => 'required|date',
            'market_value'   => 'required|numeric|min:0',
        ]);

        $investment = SaccoInvestment::findOrFail($id);
        $service->addValuation($investment, array_merge(
            $request->only(['valuation_date', 'market_value', 'accrued_interest', 'notes']),
            ['created_by_id' => Auth::id()]
        ));

        \flash('Valuation recorded.')->success()->important();
        return redirect('sacco/investment/' . $id . '/show');
    }

    public function markMatured(Request $request, $id, InvestmentService $service)
    {
        $request->validate(['actual_return' => 'required|numeric|min:0']);
        $investment = SaccoInvestment::findOrFail($id);
        $service->markMatured($investment, (float) $request->actual_return);
        \flash('Investment marked as matured.')->success()->important();
        return redirect('sacco/investment/' . $id . '/show');
    }
}
