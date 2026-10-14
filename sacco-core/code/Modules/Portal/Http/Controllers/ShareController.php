<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Laracasts\Flash\Flash;
use Modules\Share\Entities\Share;
use Yajra\DataTables\Facades\DataTables;

class ShareController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->status ?: '';
        return theme_view('portal::share.index', compact('status'));
    }

    public function get_shares(Request $request)
    {
        $clientId = session('client_id');
        $query = DB::table('shares')
            ->leftJoin('share_products', 'share_products.id', '=', 'shares.share_product_id')
            ->leftJoin('branches', 'branches.id', '=', 'shares.branch_id')
            ->selectRaw("shares.id, shares.status, shares.total_shares, shares.decimals, share_products.name share_product, branches.name branch, COALESCE(shares.total_shares * COALESCE(shares.nominal_price, share_products.nominal_price, 0), 0) as share_value")
            ->when($request->status, function ($q) use ($request) {
                $q->where('shares.status', $request->status);
            })
            ->when($clientId, function ($q) use ($clientId) {
                $q->where('shares.client_id', $clientId);
            })
            ->groupBy('shares.id');

        return DataTables::of($query)
            ->editColumn('total_shares', function ($data) {
                return number_format($data->total_shares);
            })
            ->editColumn('share_value', function ($data) {
                return number_format($data->share_value, $data->decimals ?? 2);
            })
            ->editColumn('status', function ($data) {
                return '<span class="label label-info">' . ucfirst($data->status) . '</span>';
            })
            ->editColumn('action', function ($data) {
                return '<a href="' . url('portal/share/' . $data->id . '/show') . '" class="btn btn-info">' . trans_choice('general.detail', 2) . '</a>';
            })
            ->editColumn('id', function ($data) {
                return '<a href="' . url('portal/share/' . $data->id . '/show') . '">' . $data->id . '</a>';
            })
            ->rawColumns(['id', 'action', 'status'])
            ->make(true);
    }

    public function show($id)
    {
        $share = Share::with(['transactions', 'share_product', 'client'])->find($id);
        if (!$share || $share->client_id != session('client_id')) {
            Flash::warning(trans('core::general.permission_denied'));
            return redirect()->back();
        }
        return theme_view('portal::share.show', compact('share'));
    }
}
