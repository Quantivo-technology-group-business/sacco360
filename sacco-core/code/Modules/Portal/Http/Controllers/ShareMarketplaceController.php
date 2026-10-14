<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Portal\Http\Controllers\Concerns\RequiresTransactionAuth;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Entities\ShareListing;
use Modules\Sacco\Entities\ShareTransferRequest;
use Modules\Sacco\Services\ShareMarketplaceService;
use Modules\Sacco\Services\WingService;
use Modules\Savings\Entities\Savings;
use Modules\Share\Entities\Share;

class ShareMarketplaceController extends Controller
{
    use ResolvesPortalClient;
    use RequiresTransactionAuth;

    public function index(ShareMarketplaceService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $listings = $service->getActiveListings($client->id);
        $showSellerName = (bool)config('sacco.share_marketplace_show_seller_name', true);

        return theme_view('portal::share.marketplace.index', compact('listings', 'client', 'showSellerName'));
    }

    public function myListings()
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $listings = ShareListing::with(['share.share_product'])
            ->where('seller_client_id', $client->id)
            ->orderByDesc('id')
            ->get();

        return theme_view('portal::share.marketplace.listings', compact('listings', 'client'));
    }

    public function createListing(ShareMarketplaceService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
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
            ->filter(function ($share) {
                return $share->free_shares > 0;
            });

        return theme_view('portal::share.marketplace.create_listing', compact('shares', 'client'));
    }

    public function storeListing(Request $request, ShareMarketplaceService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $request->validate([
            'share_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'price_per_share' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $service->createListing($client->id, $request->only(['share_id', 'quantity', 'price_per_share', 'notes']));
            return redirect('portal/share/marketplace/listings')->with('success', trans('sacco::share_marketplace.listing_created'));
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function cancelListing($id, ShareMarketplaceService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        try {
            $service->cancelListing($client->id, (int)$id);
            return redirect('portal/share/marketplace/listings')->with('success', trans('sacco::share_marketplace.listing_cancelled'));
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function buyListing($id)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        if ($redirect = $this->requireKyc($client->id, 'portal/share/marketplace')) {
            return $redirect;
        }

        $listing = ShareListing::with(['share.share_product', 'seller'])
            ->where('id', $id)
            ->whereIn('status', ['active', 'partially_sold'])
            ->where('seller_client_id', '!=', $client->id)
            ->firstOrFail();

        $savings = Savings::where('client_id', $client->id)->where('status', 'active')->get();
        $wing = new WingService();

        return theme_view('portal::share.marketplace.buy', compact('listing', 'client', 'savings', 'wing'));
    }

    public function storePurchase(Request $request, $id, ShareMarketplaceService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        if ($redirect = $this->requireKyc($client->id, 'portal/share/marketplace')) {
            return $redirect;
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
            'buyer_savings_id' => 'required|integer',
        ]);

        $listing = ShareListing::findOrFail($id);
        $amount = (float)$listing->price_per_share * (int)$request->quantity;
        if ($redirect = $this->verifyTransactionAuth($request, $client->id, $amount, 'share_purchase')) {
            return $redirect;
        }

        try {
            $service->purchaseFromListing(
                $client->id,
                (int)$id,
                (int)$request->quantity,
                (int)$request->buyer_savings_id
            );
            return redirect('portal/share/marketplace/transfers')->with('success', trans('sacco::share_marketplace.purchase_submitted'));
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function createDirectSale(ShareMarketplaceService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
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
            ->filter(function ($share) {
                return $share->free_shares > 0;
            });

        return theme_view('portal::share.marketplace.create_direct', compact('shares', 'client'));
    }

    public function storeDirectSale(Request $request, ShareMarketplaceService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $request->validate([
            'share_id' => 'required|integer',
            'buyer_member_number' => 'required|string|max:50',
            'quantity' => 'required|integer|min:1',
            'price_per_share' => 'required|numeric|min:0.01',
        ]);

        try {
            $service->createDirectSale($client->id, $request->only([
                'share_id', 'buyer_member_number', 'quantity', 'price_per_share',
            ]));
            return redirect('portal/share/marketplace/transfers')->with('success', trans('sacco::share_marketplace.direct_sale_submitted'));
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function transfers()
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $transfers = ShareTransferRequest::with([
            'seller', 'buyer', 'seller_share.share_product', 'listing',
        ])
            ->where(function ($q) use ($client) {
                $q->where('seller_client_id', $client->id)
                    ->orWhere('buyer_client_id', $client->id);
            })
            ->orderByDesc('id')
            ->get();

        return theme_view('portal::share.marketplace.transfers', compact('transfers', 'client'));
    }

    public function acceptDirect($id)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        if ($redirect = $this->requireKyc($client->id, 'portal/share/marketplace/transfers')) {
            return $redirect;
        }

        $transfer = ShareTransferRequest::with(['seller', 'seller_share.share_product'])
            ->where('id', $id)
            ->where('buyer_client_id', $client->id)
            ->where('type', 'direct')
            ->where('status', 'pending_buyer_acceptance')
            ->firstOrFail();

        $savings = Savings::where('client_id', $client->id)->where('status', 'active')->get();
        $wing = new WingService();

        return theme_view('portal::share.marketplace.accept_direct', compact('transfer', 'client', 'savings', 'wing'));
    }

    public function storeAcceptDirect(Request $request, $id, ShareMarketplaceService $service)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        if ($redirect = $this->requireKyc($client->id, 'portal/share/marketplace/transfers')) {
            return $redirect;
        }

        $request->validate([
            'buyer_savings_id' => 'required|integer',
        ]);

        $transfer = ShareTransferRequest::where('id', $id)
            ->where('buyer_client_id', $client->id)
            ->firstOrFail();
        $amount = (float)$transfer->price_per_share * (int)$transfer->quantity;
        if ($redirect = $this->verifyTransactionAuth($request, $client->id, $amount, 'share_accept')) {
            return $redirect;
        }

        try {
            $service->acceptDirectSale($client->id, (int)$id, (int)$request->buyer_savings_id);
            return redirect('portal/share/marketplace/transfers')->with('success', trans('sacco::share_marketplace.direct_sale_accepted'));
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
