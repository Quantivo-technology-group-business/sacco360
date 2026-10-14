<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sacco\Services\GlobalSearchService;

class GlobalSearchController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.search.index']);
    }

    public function index(Request $request)
    {
        $query = $request->get('q', '');
        $results = [];
        if ($query) {
            $results = (new GlobalSearchService())->search($query);
        }
        return theme_view('sacco::search.index', compact('query', 'results'));
    }

    public function ajax(Request $request)
    {
        $query = $request->get('q', '');
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        return response()->json((new GlobalSearchService())->search($query, 10));
    }
}
