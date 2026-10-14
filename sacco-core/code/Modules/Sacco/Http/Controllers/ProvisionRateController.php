<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sacco\Entities\SaccoProvisionRate;

class ProvisionRateController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.regulatory.index']);
    }

    public function index()
    {
        $rates = SaccoProvisionRate::orderBy('days_in_arrears_min')->get();
        return theme_view('sacco::provision_rate.index', compact('rates'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'classification' => 'required|string|max:100',
            'days_in_arrears_min' => 'required|integer|min:0',
            'days_in_arrears_max' => 'nullable|integer|min:0',
            'provision_rate' => 'required|numeric|min:0|max:100',
        ]);

        SaccoProvisionRate::create($request->only([
            'classification', 'days_in_arrears_min', 'days_in_arrears_max', 'provision_rate',
        ]));

        \flash('Provision rate saved')->success()->important();
        return redirect('sacco/regulatory/provision-rates');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'classification' => 'required|string|max:100',
            'days_in_arrears_min' => 'required|integer|min:0',
            'days_in_arrears_max' => 'nullable|integer|min:0',
            'provision_rate' => 'required|numeric|min:0|max:100',
        ]);

        $rate = SaccoProvisionRate::findOrFail($id);
        $rate->fill($request->only([
            'classification', 'days_in_arrears_min', 'days_in_arrears_max', 'provision_rate',
        ]));
        $rate->save();

        \flash('Provision rate updated')->success()->important();
        return redirect('sacco/regulatory/provision-rates');
    }
}
