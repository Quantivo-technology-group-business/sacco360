<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sacco\Services\UssdService;

class UssdController extends Controller
{
    public function handle(Request $request, UssdService $service)
    {
        $response = $service->handleRequest($request->all());
        return response($response, 200)->header('Content-Type', 'text/plain');
    }
}
