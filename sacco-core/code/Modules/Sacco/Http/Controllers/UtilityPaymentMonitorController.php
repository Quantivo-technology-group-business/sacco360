<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Sacco\Entities\SaccoUtilityPayment;

class UtilityPaymentMonitorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.integrations.index']);
    }

    public function index()
    {
        $payments = SaccoUtilityPayment::with(['provider'])->orderByDesc('id')->paginate(50);
        return theme_view('sacco::utility.index', compact('payments'));
    }
}
