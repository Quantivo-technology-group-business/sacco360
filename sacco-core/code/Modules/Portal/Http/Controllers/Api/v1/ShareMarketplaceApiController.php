<?php

namespace Modules\Portal\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Portal\Http\Controllers\Concerns\ResolvesApiPortalClient;
use Modules\Sacco\Entities\ShareListing;
use Modules\Sacco\Entities\ShareTransferRequest;
use Modules\Sacco\Services\ShareMarketplaceService;
use Modules\Sacco\Services\WingService;
use Modules\Savings\Entities\Savings;
use Modules\Share\Entities\Share;

class ShareMarketplaceApiController extends Controller
{
    use ResolvesApiPortalClient;

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request, ShareMarketplaceService $service)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        return response()->json([
            'data' => $service->getActiveListings($client->id),
            'show_seller_name' => (bool)config('sacco.share_marketplace_show_seller_name', true),
        ]);
    }

    public function myListings(Request $request)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $listings = ShareListing::with(['share.share_product'])
            ->where('seller_client_id', $client->id)
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $listings]);
    }

    public function listingForm(Request $request, ShareMarketplaceService $service)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $shares = Share::with('share_product')
            ->where('client_id', $client->id)
            ->where('status', 'active')
            ->get()
            ->map(function ($share) use ($service) {
                $share->free_shares = $service->getFreeShares($share);
                return $share;
            })
            ->filter(fn ($share) => $share->free_shares > 0)
            ->values();

        return response()->json(['data' => $shares]);
    }

    public function storeListing(Request $request, ShareMarketplaceService $service)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $request->validate([
            'share_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'price_per_share' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $listing = $service->createListing($client->id, $request->only(['share_id', 'quantity', 'price_per_share', 'notes']));
            return response()->json(['message' => 'Listing created', 'data' => $listing], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function cancelListing(Request $request, $id, ShareMarketplaceService $service)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        try {
            $listing = $service->cancelListing($client->id, (int)$id);
            return response()->json(['message' => 'Listing cancelled', 'data' => $listing]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function buyListing(Request $request, $id, ShareMarketplaceService $service)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $listing = ShareListing::with('share')->findOrFail($id);

        return response()->json([
            'data' => [
                'listing' => $listing,
                'total_cost' => $listing->quantity * $listing->price_per_share,
            ],
        ]);
    }

    public function storePurchase(Request $request, $id, ShareMarketplaceService $service)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
            'buyer_savings_id' => 'required|integer',
        ]);

        Savings::where('id', $request->buyer_savings_id)->where('client_id', $client->id)->firstOrFail();

        try {
            $transfer = $service->purchaseFromListing(
                $client->id,
                (int)$id,
                (int)$request->quantity,
                (int)$request->buyer_savings_id
            );
            return response()->json(['message' => 'Purchase submitted', 'data' => $transfer], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function directSaleForm(Request $request, ShareMarketplaceService $service)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $shares = Share::with('share_product')
            ->where('client_id', $client->id)
            ->where('status', 'active')
            ->get()
            ->map(function ($share) use ($service) {
                $share->free_shares = $service->getFreeShares($share);
                return $share;
            })
            ->filter(fn ($share) => $share->free_shares > 0)
            ->values();

        return response()->json(['data' => $shares]);
    }

    public function storeDirectSale(Request $request, ShareMarketplaceService $service)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $request->validate([
            'share_id' => 'required|integer',
            'buyer_member_number' => 'required|string|max:50',
            'quantity' => 'required|integer|min:1',
            'price_per_share' => 'required|numeric|min:0.01',
        ]);

        try {
            $transfer = $service->createDirectSale($client->id, $request->only([
                'share_id', 'buyer_member_number', 'quantity', 'price_per_share',
            ]));
            return response()->json(['message' => 'Direct sale request sent', 'data' => $transfer], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function transfers(Request $request)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $transfers = ShareTransferRequest::with(['share.share_product', 'seller', 'buyer'])
            ->where(function ($q) use ($client) {
                $q->where('seller_client_id', $client->id)->orWhere('buyer_client_id', $client->id);
            })
            ->orderByDesc('id')
            ->paginate($request->limit ?: 20);

        return response()->json([$transfers]);
    }

    public function acceptDirect(Request $request, $id, ShareMarketplaceService $service)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $transfer = ShareTransferRequest::where('id', $id)->where('buyer_client_id', $client->id)->firstOrFail();
        $wing = new WingService();
        $fosa = $wing->getFosaAccount($client->id);

        return response()->json([
            'data' => [
                'transfer' => $transfer,
                'fosa' => $fosa ? ['id' => $fosa->id, 'balance' => $wing->getAccountBalance($fosa->id)] : null,
                'total_cost' => $transfer->quantity * $transfer->price_per_share,
            ],
        ]);
    }

    public function storeAcceptDirect(Request $request, $id, ShareMarketplaceService $service)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $request->validate(['buyer_savings_id' => 'required|integer']);

        Savings::where('id', $request->buyer_savings_id)->where('client_id', $client->id)->firstOrFail();

        try {
            $result = $service->acceptDirectSale($client->id, (int)$id, (int)$request->buyer_savings_id);
            return response()->json(['message' => 'Direct sale accepted', 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
