<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoDividend;
use Modules\ActivityLog\Services\AuditLogger;
use Modules\Sacco\Services\DividendService;
use Modules\Share\Entities\ShareProduct;

class DividendController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.dividends.index'])->only(['index', 'show']);
        $this->middleware(['permission:sacco.dividends.create'])->only(['create', 'store', 'declare', 'process']);
    }

    public function index()
    {
        $dividends = SaccoDividend::orderByDesc('id')->paginate(20);
        return theme_view('sacco::dividend.index', compact('dividends'));
    }

    public function create()
    {
        $products = ShareProduct::where('active', 1)->get();
        return theme_view('sacco::dividend.create', compact('products'));
    }

    public function store(Request $request, DividendService $service)
    {
        $request->validate([
            'share_product_id' => 'required',
            'name' => 'required',
            'dividend_per_share' => 'required|numeric|min:0',
            'declaration_date' => 'required|date',
            'payout_method' => 'required|in:cash,reinvest,savings',
        ]);

        $dividend = new SaccoDividend();
        $dividend->created_by_id = Auth::id();
        $dividend->fill($request->only(['share_product_id', 'name', 'dividend_per_share', 'declaration_date', 'payment_date', 'payout_method', 'agm_resolution']));
        $dividend->status = 'draft';
        $dividend->save();

        $service->declare($dividend);
        AuditLogger::log('Dividend Declared', $dividend, ['id' => $dividend->id, 'name' => $dividend->name, 'dividend_per_share' => $dividend->dividend_per_share, 'declaration_date' => $dividend->declaration_date], 'sacco', 'create');
        \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();
        return redirect('sacco/dividend/' . $dividend->id . '/show');
    }

    public function show($id)
    {
        $dividend = SaccoDividend::with('allocations')->findOrFail($id);
        return theme_view('sacco::dividend.show', compact('dividend'));
    }

    public function process($id, DividendService $service)
    {
        $dividend = SaccoDividend::findOrFail($id);
        if (function_exists('sacco_requires_maker_checker_for') && sacco_requires_maker_checker_for((float) ($dividend->total_pool ?? 0), 'dividend')) {
            if (function_exists('sacco_queue_transaction_approval')) {
                sacco_queue_transaction_approval('dividend', $dividend->id, (float) ($dividend->total_pool ?? 0), 'Dividend processing');
            }
        }
        if (function_exists('sacco_compliance_log')) {
            sacco_compliance_log('dividend_process', 'sacco', 'dividend', $dividend->id, 'Dividend processing initiated');
        }
        $service->process($dividend);
        AuditLogger::log('Dividend Processed', $dividend, ['id' => $dividend->id, 'name' => $dividend->name, 'total_pool' => $dividend->total_pool ?? null], 'sacco', 'approve');
        \flash('Dividend processed successfully')->success()->important();
        return redirect('sacco/dividend/' . $id . '/show');
    }
}
