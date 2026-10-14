<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoWelfareClaim;
use Modules\Sacco\Entities\SaccoWelfareContribution;
use Modules\Sacco\Entities\SaccoWelfareProduct;
use Modules\Sacco\Services\WelfareService;
use Modules\Client\Entities\Client;

class WelfareController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware('permission:sacco.welfare.index')->only(['index', 'products', 'contributions']);
        $this->middleware('permission:sacco.welfare.manage')->only(['createProduct', 'storeProduct', 'editProduct', 'updateProduct']);
        $this->middleware('permission:sacco.welfare.claims')->only(['claims', 'createClaim', 'storeClaim', 'showClaim']);
        $this->middleware('permission:sacco.welfare.approve')->only(['approveClaim', 'rejectClaim', 'markPaid']);
    }

    // ---- Products ----

    public function products()
    {
        $products = SaccoWelfareProduct::orderByDesc('id')->paginate(20);
        return theme_view('sacco::welfare.products', compact('products'));
    }

    public function createProduct()
    {
        return theme_view('sacco::welfare.create_product');
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'name'               => 'required|string|max:150',
            'code'               => 'required|string|max:30|unique:sacco_welfare_products,code',
            'type'               => 'required|in:death,hospitalization,education,disability,maternity,general',
            'member_contribution'=> 'required|numeric|min:0',
            'qualifying_months'  => 'required|integer|min:1',
        ]);

        SaccoWelfareProduct::create($request->only([
            'name', 'code', 'description', 'type',
            'member_contribution', 'max_claim_amount',
            'qualifying_months', 'waiting_period_days',
            'requires_documents', 'is_active',
        ]));

        \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();
        return redirect('sacco/welfare/products');
    }

    public function editProduct($id)
    {
        $product = SaccoWelfareProduct::findOrFail($id);
        return theme_view('sacco::welfare.edit_product', compact('product'));
    }

    public function updateProduct(Request $request, $id)
    {
        $product = SaccoWelfareProduct::findOrFail($id);
        $request->validate([
            'name'               => 'required|string|max:150',
            'code'               => 'required|string|max:30|unique:sacco_welfare_products,code,' . $id,
            'member_contribution'=> 'required|numeric|min:0',
        ]);
        $product->update($request->only([
            'name', 'code', 'description', 'type',
            'member_contribution', 'max_claim_amount',
            'qualifying_months', 'waiting_period_days',
            'requires_documents', 'is_active',
        ]));
        \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();
        return redirect('sacco/welfare/products');
    }

    // ---- Fund Overview ----

    public function index(WelfareService $service)
    {
        $products = SaccoWelfareProduct::where('is_active', 1)->get()->map(function ($p) use ($service) {
            $p->fund_balance = $service->getFundBalance($p);
            return $p;
        });

        $pendingClaims   = SaccoWelfareClaim::whereIn('status', ['submitted', 'under_review'])->count();
        $approvedClaims  = SaccoWelfareClaim::where('status', 'approved')->count();
        $paidThisYear    = SaccoWelfareClaim::where('status', 'paid')
            ->whereYear('payment_date', now()->year)->sum('amount_approved');

        return theme_view('sacco::welfare.index', compact(
            'products', 'pendingClaims', 'approvedClaims', 'paidThisYear'
        ));
    }

    // ---- Claims ----

    public function claims(Request $request)
    {
        $status = $request->get('status', 'all');
        $query  = SaccoWelfareClaim::with(['client', 'product'])->orderByDesc('id');
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        $claims = $query->paginate(20);
        return theme_view('sacco::welfare.claims', compact('claims', 'status'));
    }

    public function createClaim()
    {
        $products = SaccoWelfareProduct::where('is_active', 1)->get();
        $clients  = Client::where('status', 1)->orderBy('first_name')->get();
        return theme_view('sacco::welfare.create_claim', compact('products', 'clients'));
    }

    public function storeClaim(Request $request, WelfareService $service)
    {
        $request->validate([
            'client_id'               => 'required|exists:clients,id',
            'sacco_welfare_product_id'=> 'required|exists:sacco_welfare_products,id',
            'event_date'              => 'required|date',
            'claim_date'              => 'required|date',
            'amount_claimed'          => 'required|numeric|min:1',
            'beneficiary_name'        => 'required|string',
        ]);

        $client  = Client::findOrFail($request->client_id);
        $product = SaccoWelfareProduct::findOrFail($request->sacco_welfare_product_id);

        $eligibility = $service->checkEligibility($client, $product);
        if (!$eligibility['eligible']) {
            \flash($eligibility['reason'])->error()->important();
            return back()->withInput();
        }

        $data = array_merge($request->only([
            'client_id', 'sacco_welfare_product_id',
            'event_date', 'claim_date',
            'beneficiary_name', 'beneficiary_relationship', 'beneficiary_phone',
            'beneficiary_id_number', 'beneficiary_account',
            'amount_claimed', 'remarks',
        ]), ['created_by_id' => Auth::id()]);

        $claim = $service->submitClaim($data);

        \flash('Welfare claim ' . $claim->claim_reference . ' submitted successfully.')->success()->important();
        return redirect('sacco/welfare/claims/' . $claim->id . '/show');
    }

    public function showClaim($id)
    {
        $claim = SaccoWelfareClaim::with(['client', 'product', 'documents'])->findOrFail($id);
        return theme_view('sacco::welfare.show_claim', compact('claim'));
    }

    public function approveClaim(Request $request, $id, WelfareService $service)
    {
        $request->validate(['amount_approved' => 'required|numeric|min:1']);
        $claim = SaccoWelfareClaim::findOrFail($id);
        $service->approveClaim($claim, (float) $request->amount_approved, Auth::id());
        \flash('Claim approved.')->success()->important();
        return redirect('sacco/welfare/claims/' . $id . '/show');
    }

    public function rejectClaim(Request $request, $id, WelfareService $service)
    {
        $request->validate(['rejection_reason' => 'required|string']);
        $claim = SaccoWelfareClaim::findOrFail($id);
        $service->rejectClaim($claim, $request->rejection_reason, Auth::id());
        \flash('Claim rejected.')->warning()->important();
        return redirect('sacco/welfare/claims/' . $id . '/show');
    }

    public function markPaid(Request $request, $id, WelfareService $service)
    {
        $request->validate(['payment_reference' => 'required|string']);
        $claim = SaccoWelfareClaim::findOrFail($id);
        $service->markPaid($claim, $request->payment_reference);
        \flash('Claim marked as paid.')->success()->important();
        return redirect('sacco/welfare/claims/' . $id . '/show');
    }

    // ---- Contributions ----

    public function contributions(Request $request)
    {
        $contributions = SaccoWelfareContribution::with(['client', 'product'])
            ->orderByDesc('id')
            ->paginate(30);
        return theme_view('sacco::welfare.contributions', compact('contributions'));
    }
}
