<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Sacco\Entities\ShareTransferRequest;
use Modules\Sacco\Services\ShareMarketplaceService;
use Modules\Sacco\Services\ShareRegulatoryService;
use Modules\Savings\Entities\Savings;

class ShareTransferApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.share_transfers.index'])->only(['index', 'show']);
        $this->middleware(['permission:sacco.share_transfers.approve'])->only(['approve', 'reject']);
    }

    public function index()
    {
        $pending = ShareTransferRequest::with([
            'seller', 'buyer', 'seller_share.share_product', 'buyer_share.share_product', 'listing',
        ])
            ->where('status', 'pending_admin')
            ->orderByDesc('id')
            ->paginate(20);

        return theme_view('sacco::share_transfer.index', compact('pending'));
    }

    public function show($id)
    {
        $transfer = ShareTransferRequest::with([
            'seller', 'buyer', 'seller_share.share_product', 'buyer_share.share_product',
            'listing', 'buyer_savings', 'buyer_hold_transaction',
        ])->findOrFail($id);

        $sellerShare = $transfer->seller_share;
        $nominal = $sellerShare->nominal_price ?? ($sellerShare->share_product->nominal_price ?? 0);
        $regulatory = (new ShareRegulatoryService())->validateAdditionalPurchase(
            (int)$transfer->buyer_client_id,
            (int)$transfer->quantity * (float)$nominal,
            $sellerShare->share_product ?? null
        );

        $sellerSavings = Savings::where('client_id', $transfer->seller_client_id)
            ->where('status', 'active')
            ->get();

        return theme_view('sacco::share_transfer.show', compact('transfer', 'regulatory', 'sellerSavings'));
    }

    public function approve(Request $request, $id, ShareMarketplaceService $service)
    {
        try {
            $service->approveTransfer((int)$id, $request->seller_savings_id ? (int)$request->seller_savings_id : null);
            \flash(trans('sacco::share_marketplace.transfer_approved'))->success()->important();
            return redirect('sacco/share-transfer');
        } catch (\Exception $e) {
            \flash($e->getMessage())->error()->important();
            return redirect('sacco/share-transfer/' . $id . '/show');
        }
    }

    public function reject(Request $request, $id, ShareMarketplaceService $service)
    {
        $request->validate([
            'rejected_notes' => 'nullable|string|max:500',
        ]);

        try {
            $service->rejectTransfer((int)$id, $request->rejected_notes);
            \flash(trans('sacco::share_marketplace.transfer_rejected'))->warning()->important();
            return redirect('sacco/share-transfer');
        } catch (\Exception $e) {
            \flash($e->getMessage())->error()->important();
            return redirect('sacco/share-transfer/' . $id . '/show');
        }
    }
}
