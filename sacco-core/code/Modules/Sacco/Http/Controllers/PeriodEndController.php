<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sacco\Entities\SaccoFinancialPeriod;
use Modules\Sacco\Services\PeriodEndService;

class PeriodEndController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.period_end.index']);
    }

    public function index()
    {
        $periods = SaccoFinancialPeriod::orderByDesc('id')->paginate(20);
        return theme_view('sacco::period_end.index', compact('periods'));
    }

    public function create()
    {
        return theme_view('sacco::period_end.create');
    }

    public function store(Request $request, PeriodEndService $service)
    {
        $request->validate([
            'name' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);
        $service->createPeriod($request->name, $request->start_date, $request->end_date);
        \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();
        return redirect('sacco/period_end');
    }

    public function close($id, PeriodEndService $service)
    {
        $period = SaccoFinancialPeriod::findOrFail($id);
        try {
            $service->closePeriod($period);
            \flash('Period closed successfully')->success()->important();
        } catch (\Exception $e) {
            \flash($e->getMessage())->error()->important();
        }
        return redirect('sacco/period_end');
    }
}
