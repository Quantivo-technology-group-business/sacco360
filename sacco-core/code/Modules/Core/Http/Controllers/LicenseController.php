<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LicenseController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
    }

    public function index()
    {
        return view('core::index');
    }

    public function verify(Request $request)
    {
        ensure_local_license($request->ip());
        return redirect('dashboard');
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        return view('core::show');
    }

    public function edit($id)
    {
        return view('core::edit');
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
