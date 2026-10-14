<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Entities\JournalEntry;
use Modules\Client\Entities\Client;
use Modules\Core\Entities\PaymentDetail;
use Modules\Sacco\Entities\ShareListing;
use Modules\Sacco\Entities\ShareTransferRequest;
use Modules\Savings\Entities\Savings;
use Modules\Savings\Entities\SavingsTransaction;
use Modules\Savings\Events\TransactionUpdated as SavingsTransactionUpdated;
use Modules\Share\Entities\Share;
use Modules\Share\Entities\ShareTransaction;

class ShareMarketplaceService
{
    protected function transferTransactionTypeId(): int
    {
        $id = DB::table('share_transaction_types')->where('name', 'Transfer')->value('id');
        return (int)($id ?: 6);
    }

    public function isEnabled(): bool
    {
        return (bool)config('sacco.share_marketplace_enabled', true);
    }

    public function getFreeShares(Share $share, ?int $excludeListingId = null): int
    {
        $locked = (int)($share->locked_shares ?? 0);
        $total = (int)($share->total_shares ?? 0);

        $listedQuery = ShareListing::where('share_id', $share->id)
            ->whereIn('status', ['active', 'partially_sold']);
        if ($excludeListingId) {
            $listedQuery->where('id', '!=', $excludeListingId);
        }
        $listed = (int)$listedQuery->sum('remaining_quantity');

        $pendingDirect = (int)ShareTransferRequest::where('seller_share_id', $share->id)
            ->where('type', 'direct')
            ->whereIn('status', ['pending_buyer_acceptance', 'pending_admin'])
            ->sum('quantity');

        return max(0, $total - $locked - $listed - $pendingDirect);
    }

    public function createListing(int $sellerClientId, array $data): ShareListing
    {
        $this->assertEnabled();

        return DB::transaction(function () use ($sellerClientId, $data) {
            $share = Share::with('share_product')
                ->where('id', $data['share_id'])
                ->where('client_id', $sellerClientId)
                ->where('status', 'active')
                ->lockForUpdate()
                ->firstOrFail();

            $quantity = (int)$data['quantity'];
            $pricePerShare = (float)$data['price_per_share'];

            if ($quantity <= 0 || $pricePerShare <= 0) {
                throw new \RuntimeException('Quantity and price must be greater than zero.');
            }

            $free = $this->getFreeShares($share);
            if ($quantity > $free) {
                throw new \RuntimeException(sprintf(
                    'Insufficient free shares. Available: %d (total %d, locked %d).',
                    $free,
                    $share->total_shares ?? 0,
                    $share->locked_shares ?? 0
                ));
            }

            $expiryDays = (int)config('sacco.share_marketplace_listing_expiry_days', 0);
            $expiresAt = $expiryDays > 0 ? now()->addDays($expiryDays) : null;

            $listing = ShareListing::create([
                'created_by_id' => Auth::id(),
                'share_id' => $share->id,
                'seller_client_id' => $sellerClientId,
                'quantity' => $quantity,
                'remaining_quantity' => $quantity,
                'price_per_share' => $pricePerShare,
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
                'expires_at' => $expiresAt,
            ]);

            if (function_exists('activity')) {
                activity()->on($listing)->log('Share listing created');
            }

            return $listing;
        });
    }

    public function cancelListing(int $sellerClientId, int $listingId): ShareListing
    {
        return DB::transaction(function () use ($sellerClientId, $listingId) {
            $listing = ShareListing::where('id', $listingId)
                ->where('seller_client_id', $sellerClientId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($listing->status, ['active', 'partially_sold'], true)) {
                throw new \RuntimeException('This listing cannot be cancelled.');
            }

            $pending = ShareTransferRequest::where('listing_id', $listing->id)
                ->where('status', 'pending_admin')
                ->exists();
            if ($pending) {
                throw new \RuntimeException('Cannot cancel listing with pending transfer requests.');
            }

            $listing->status = 'cancelled';
            $listing->save();

            return $listing;
        });
    }

    public function createDirectSale(int $sellerClientId, array $data): ShareTransferRequest
    {
        $this->assertEnabled();

        return DB::transaction(function () use ($sellerClientId, $data) {
            $buyer = $this->resolveBuyerByMemberNumber($data['buyer_member_number'] ?? '');
            if ($buyer->id === $sellerClientId) {
                throw new \RuntimeException('You cannot sell shares to yourself.');
            }

            $share = Share::with('share_product')
                ->where('id', $data['share_id'])
                ->where('client_id', $sellerClientId)
                ->where('status', 'active')
                ->lockForUpdate()
                ->firstOrFail();

            $quantity = (int)$data['quantity'];
            $pricePerShare = (float)$data['price_per_share'];

            if ($quantity <= 0 || $pricePerShare <= 0) {
                throw new \RuntimeException('Quantity and price must be greater than zero.');
            }

            $free = $this->getFreeShares($share);
            if ($quantity > $free) {
                throw new \RuntimeException(sprintf('Insufficient free shares. Available: %d.', $free));
            }

            $buyerShare = $this->resolveBuyerShareAccount($buyer->id, $share->share_product_id);

            $request = ShareTransferRequest::create([
                'reference' => $this->generateReference(),
                'type' => 'direct',
                'listing_id' => null,
                'seller_client_id' => $sellerClientId,
                'buyer_client_id' => $buyer->id,
                'seller_share_id' => $share->id,
                'buyer_share_id' => $buyerShare->id,
                'quantity' => $quantity,
                'price_per_share' => $pricePerShare,
                'total_amount' => $quantity * $pricePerShare,
                'status' => 'pending_buyer_acceptance',
                'created_by_id' => Auth::id(),
            ]);

            if (function_exists('activity')) {
                activity()->on($request)->log('Direct share sale initiated');
            }

            return $request;
        });
    }

    public function acceptDirectSale(int $buyerClientId, int $requestId, int $buyerSavingsId): ShareTransferRequest
    {
        return DB::transaction(function () use ($buyerClientId, $requestId, $buyerSavingsId) {
            $transfer = ShareTransferRequest::with('seller_share.share_product')
                ->where('id', $requestId)
                ->where('buyer_client_id', $buyerClientId)
                ->where('type', 'direct')
                ->where('status', 'pending_buyer_acceptance')
                ->lockForUpdate()
                ->firstOrFail();

            $this->finalizeBuyerCommitment($transfer, $buyerSavingsId);

            return $transfer->fresh();
        });
    }

    public function purchaseFromListing(int $buyerClientId, int $listingId, int $quantity, int $buyerSavingsId): ShareTransferRequest
    {
        $this->assertEnabled();

        return DB::transaction(function () use ($buyerClientId, $listingId, $quantity, $buyerSavingsId) {
            $listing = ShareListing::with(['share.share_product', 'seller'])
                ->where('id', $listingId)
                ->whereIn('status', ['active', 'partially_sold'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($listing->seller_client_id === $buyerClientId) {
                throw new \RuntimeException('You cannot buy your own listing.');
            }

            if ($listing->expires_at && $listing->expires_at->isPast()) {
                throw new \RuntimeException('This listing has expired.');
            }

            if ($quantity <= 0 || $quantity > $listing->remaining_quantity) {
                throw new \RuntimeException(sprintf(
                    'Invalid quantity. Available: %d.',
                    $listing->remaining_quantity
                ));
            }

            $share = $listing->share;
            $buyerShare = $this->resolveBuyerShareAccount($buyerClientId, $share->share_product_id);
            $totalAmount = $quantity * (float)$listing->price_per_share;

            $nominal = $share->nominal_price ?? ($share->share_product->nominal_price ?? 0);
            $regulatory = (new ShareRegulatoryService())->validateAdditionalPurchase(
                $buyerClientId,
                $quantity * (float)$nominal,
                $share->share_product
            );
            if (!$regulatory['passed']) {
                throw new \RuntimeException(implode(' ', $regulatory['violations']));
            }

            $transfer = ShareTransferRequest::create([
                'reference' => $this->generateReference(),
                'type' => 'marketplace',
                'listing_id' => $listing->id,
                'seller_client_id' => $listing->seller_client_id,
                'buyer_client_id' => $buyerClientId,
                'seller_share_id' => $share->id,
                'buyer_share_id' => $buyerShare->id,
                'quantity' => $quantity,
                'price_per_share' => $listing->price_per_share,
                'total_amount' => $totalAmount,
                'status' => 'pending_admin',
                'created_by_id' => Auth::id(),
            ]);

            $this->createBuyerHold($transfer, $buyerSavingsId);
            $this->reserveListingQuantity($listing, $quantity);

            if (function_exists('activity')) {
                activity()->on($transfer)->log('Share purchase request submitted');
            }

            return $transfer->fresh();
        });
    }

    public function approveTransfer(int $requestId, ?int $sellerSavingsId = null): ShareTransferRequest
    {
        return DB::transaction(function () use ($requestId, $sellerSavingsId) {
            $transfer = ShareTransferRequest::with([
                'seller_share.share_product',
                'buyer_share.share_product',
                'listing',
            ])
                ->where('id', $requestId)
                ->where('status', 'pending_admin')
                ->lockForUpdate()
                ->firstOrFail();

            $sellerShare = $transfer->seller_share;
            $buyerShare = $transfer->buyer_share;
            $quantity = (int)$transfer->quantity;
            $totalAmount = (float)$transfer->total_amount;
            $date = date('Y-m-d');

            if ($quantity > ($sellerShare->total_shares ?? 0) - ($sellerShare->locked_shares ?? 0)) {
                throw new \RuntimeException('Seller no longer has sufficient free shares.');
            }

            $additionalValue = $quantity * ($sellerShare->nominal_price ?? $sellerShare->share_product->nominal_price ?? 0);
            $regulatory = (new ShareRegulatoryService())->validateAdditionalPurchase(
                (int)$transfer->buyer_client_id,
                $additionalValue,
                $sellerShare->share_product
            );
            if (!$regulatory['passed']) {
                throw new \RuntimeException(implode(' ', $regulatory['violations']));
            }

            if (!$transfer->buyer_hold_transaction_id) {
                throw new \RuntimeException('Buyer payment hold is missing.');
            }

            $this->approveBuyerHold($transfer);
            $transfer->seller_savings_id = $this->creditSellerSavings($transfer, $sellerSavingsId, $date);

            $sellerShare->total_shares = max(0, ($sellerShare->total_shares ?? 0) - $quantity);
            $sellerShare->save();

            $sellerTxn = $this->createShareTransaction(
                $sellerShare,
                'Share transfer to member',
                $this->transferTransactionTypeId(),
                $date,
                $totalAmount,
                $quantity,
                'debit'
            );
            $this->postShareJournal($sellerShare, $sellerTxn, 'redeem', $date);

            $buyerShare->total_shares = ($buyerShare->total_shares ?? 0) + $quantity;
            $buyerShare->save();

            $buyerTxn = $this->createShareTransaction(
                $buyerShare,
                'Share transfer from member',
                $this->transferTransactionTypeId(),
                $date,
                $totalAmount,
                $quantity,
                'credit'
            );
            $this->postShareJournal($buyerShare, $buyerTxn, 'purchase', $date);

            $transfer->seller_share_transaction_id = $sellerTxn->id;
            $transfer->buyer_share_transaction_id = $buyerTxn->id;
            $transfer->status = 'approved';
            $transfer->approved_by_id = Auth::id();
            $transfer->approved_at = now();
            $transfer->save();

            if ($transfer->listing_id && $transfer->listing) {
                $this->updateListingAfterSale($transfer->listing, $quantity);
            }

            if (function_exists('activity')) {
                activity()->on($transfer)->log('Share transfer approved');
            }

            return $transfer->fresh();
        });
    }

    public function rejectTransfer(int $requestId, ?string $notes = null): ShareTransferRequest
    {
        return DB::transaction(function () use ($requestId, $notes) {
            $transfer = ShareTransferRequest::with('listing')
                ->where('id', $requestId)
                ->whereIn('status', ['pending_admin', 'pending_buyer_acceptance'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($transfer->buyer_hold_transaction_id) {
                $this->reverseBuyerHold($transfer);
            }

            if ($transfer->listing_id && $transfer->listing && $transfer->status === 'pending_admin') {
                $this->restoreListingQuantity($transfer->listing, (int)$transfer->quantity);
            }

            $transfer->status = 'rejected';
            $transfer->rejected_by_id = Auth::id();
            $transfer->rejected_notes = $notes;
            $transfer->rejected_at = now();
            $transfer->save();

            if (function_exists('activity')) {
                activity()->on($transfer)->log('Share transfer rejected');
            }

            return $transfer->fresh();
        });
    }

    public function getActiveListings(?int $excludeSellerClientId = null)
    {
        $query = ShareListing::with(['share.share_product', 'seller'])
            ->whereIn('status', ['active', 'partially_sold'])
            ->where('remaining_quantity', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('id');

        if ($excludeSellerClientId) {
            $query->where('seller_client_id', '!=', $excludeSellerClientId);
        }

        return $query->get();
    }

    protected function finalizeBuyerCommitment(ShareTransferRequest $transfer, int $buyerSavingsId): void
    {
        $sellerShare = $transfer->seller_share;
        $nominal = $sellerShare->nominal_price ?? ($sellerShare->share_product->nominal_price ?? 0);
        $regulatory = (new ShareRegulatoryService())->validateAdditionalPurchase(
            (int)$transfer->buyer_client_id,
            (int)$transfer->quantity * (float)$nominal,
            $sellerShare->share_product ?? null
        );
        if (!$regulatory['passed']) {
            throw new \RuntimeException(implode(' ', $regulatory['violations']));
        }

        $this->createBuyerHold($transfer, $buyerSavingsId);

        $transfer->status = 'pending_admin';
        $transfer->save();

        if (function_exists('activity')) {
            activity()->on($transfer)->log('Direct share sale accepted by buyer');
        }
    }

    protected function createBuyerHold(ShareTransferRequest $transfer, int $buyerSavingsId): void
    {
        $savings = Savings::where('id', $buyerSavingsId)
            ->where('client_id', $transfer->buyer_client_id)
            ->where('status', 'active')
            ->firstOrFail();

        $balance = (new WingService())->getAccountBalance($savings->id);
        if ($balance < (float)$transfer->total_amount) {
            throw new \RuntimeException('Insufficient savings balance for this purchase.');
        }

        if (function_exists('sacco_validate_member_transaction')) {
            $check = sacco_validate_member_transaction($transfer->buyer_client_id);
            if (!$check['allowed']) {
                throw new \RuntimeException($check['reason']);
            }
        }

        $date = date('Y-m-d');
        $paymentDetail = new PaymentDetail();
        $paymentDetail->created_by_id = Auth::id();
        $paymentDetail->payment_type_id = 1;
        $paymentDetail->transaction_type = 'share_marketplace_hold';
        $paymentDetail->description = 'Share purchase hold - ' . $transfer->reference;
        $paymentDetail->save();

        $hold = new SavingsTransaction();
        $hold->created_by_id = Auth::id();
        $hold->savings_id = $savings->id;
        $hold->branch_id = $savings->branch_id;
        $hold->payment_detail_id = $paymentDetail->id;
        $hold->name = 'Share Purchase Hold - ' . $transfer->reference;
        $hold->savings_transaction_type_id = 7;
        $hold->submitted_on = $date;
        $hold->created_on = $date;
        $hold->amount = $transfer->total_amount;
        $hold->debit = $transfer->total_amount;
        $hold->status = 'pending';
        $hold->reversible = 1;
        $hold->save();

        $transfer->buyer_savings_id = $savings->id;
        $transfer->buyer_hold_transaction_id = $hold->id;
        $transfer->save();
    }

    protected function approveBuyerHold(ShareTransferRequest $transfer): void
    {
        $hold = SavingsTransaction::with('payment_detail')->findOrFail($transfer->buyer_hold_transaction_id);
        $hold->status = 'approved';
        $hold->save();

        $savings = Savings::with('savings_product')->findOrFail($transfer->buyer_savings_id);
        $this->postSavingsJournal($savings, $hold, 'withdrawal');
        event(new SavingsTransactionUpdated($savings));
    }

    protected function reverseBuyerHold(ShareTransferRequest $transfer): void
    {
        $hold = SavingsTransaction::find($transfer->buyer_hold_transaction_id);
        if (!$hold) {
            return;
        }
        $hold->reversed = 1;
        $hold->status = 'declined';
        $hold->save();

        $savings = Savings::find($transfer->buyer_savings_id);
        if ($savings) {
            event(new SavingsTransactionUpdated($savings));
        }
    }

    protected function creditSellerSavings(ShareTransferRequest $transfer, ?int $sellerSavingsId, string $date): int
    {
        if ($sellerSavingsId) {
            $savings = Savings::where('id', $sellerSavingsId)
                ->where('client_id', $transfer->seller_client_id)
                ->where('status', 'active')
                ->firstOrFail();
        } else {
            $wing = new WingService();
            $savings = $wing->getBosaAccount($transfer->seller_client_id)
                ?? $wing->getFosaAccount($transfer->seller_client_id);
            if (!$savings) {
                throw new \RuntimeException('Seller has no active savings account to receive payment.');
            }
        }

        $paymentDetail = new PaymentDetail();
        $paymentDetail->created_by_id = Auth::id();
        $paymentDetail->payment_type_id = 1;
        $paymentDetail->transaction_type = 'share_marketplace_credit';
        $paymentDetail->description = 'Share sale proceeds - ' . $transfer->reference;
        $paymentDetail->save();

        $deposit = new SavingsTransaction();
        $deposit->created_by_id = Auth::id();
        $deposit->savings_id = $savings->id;
        $deposit->branch_id = $savings->branch_id;
        $deposit->payment_detail_id = $paymentDetail->id;
        $deposit->name = 'Share Sale Proceeds - ' . $transfer->reference;
        $deposit->savings_transaction_type_id = 8;
        $deposit->submitted_on = $date;
        $deposit->created_on = $date;
        $deposit->amount = $transfer->total_amount;
        $deposit->credit = $transfer->total_amount;
        $deposit->status = 'approved';
        $deposit->save();

        $this->postSavingsJournal($savings, $deposit, 'deposit');
        event(new SavingsTransactionUpdated($savings));

        $transfer->seller_credit_transaction_id = $deposit->id;
        $transfer->save();

        return $savings->id;
    }

    protected function reserveListingQuantity(ShareListing $listing, int $quantity): void
    {
        $listing->remaining_quantity -= $quantity;
        if ($listing->remaining_quantity <= 0) {
            $listing->remaining_quantity = 0;
            $listing->status = 'sold_out';
        } else {
            $listing->status = 'partially_sold';
        }
        $listing->save();
    }

    protected function restoreListingQuantity(ShareListing $listing, int $quantity): void
    {
        $listing = ShareListing::where('id', $listing->id)->lockForUpdate()->firstOrFail();
        $listing->remaining_quantity += $quantity;
        if ($listing->remaining_quantity >= $listing->quantity) {
            $listing->remaining_quantity = $listing->quantity;
            $listing->status = 'active';
        } elseif ($listing->remaining_quantity > 0) {
            $listing->status = 'partially_sold';
        }
        $listing->save();
    }

    protected function updateListingAfterSale(ShareListing $listing, int $quantity): void
    {
        // Quantity already reserved at purchase time; ensure sold_out status if needed.
        if ($listing->remaining_quantity <= 0 && $listing->status !== 'sold_out') {
            $listing->status = 'sold_out';
            $listing->save();
        }
    }

    protected function resolveBuyerByMemberNumber(string $memberNumber): Client
    {
        $memberNumber = trim($memberNumber);
        if ($memberNumber === '') {
            throw new \RuntimeException('Buyer member number is required.');
        }

        $buyer = Client::where('member_number', $memberNumber)
            ->orWhere('external_id', $memberNumber)
            ->first();

        if (!$buyer) {
            throw new \RuntimeException('Buyer member not found.');
        }

        if ($buyer->status !== 'active') {
            throw new \RuntimeException('Buyer member is not active.');
        }

        return $buyer;
    }

    protected function resolveBuyerShareAccount(int $buyerClientId, int $shareProductId): Share
    {
        $share = Share::where('client_id', $buyerClientId)
            ->where('share_product_id', $shareProductId)
            ->where('status', 'active')
            ->first();

        if (!$share) {
            throw new \RuntimeException(
                'You must have an active share account for this product before purchasing shares.'
            );
        }

        return $share;
    }

    protected function createShareTransaction(
        Share $share,
        string $name,
        int $typeId,
        string $date,
        float $amount,
        int $totalShares,
        string $leg
    ): ShareTransaction {
        $txn = new ShareTransaction();
        $txn->created_by_id = Auth::id();
        $txn->branch_id = $share->branch_id;
        $txn->share_id = $share->id;
        $txn->name = $name;
        $txn->share_transaction_type_id = $typeId;
        $txn->submitted_on = $date;
        $txn->created_on = $date;
        $txn->amount = $amount;
        $txn->total_shares = $totalShares;
        if ($leg === 'credit') {
            $txn->credit = $amount;
        } else {
            $txn->debit = $amount;
        }
        $txn->reversible = 1;
        $txn->save();

        return $txn;
    }

    protected function postShareJournal(Share $share, ShareTransaction $txn, string $type, string $date): void
    {
        $product = $share->share_product;
        if (!$product || $product->accounting_rule !== 'cash') {
            return;
        }

        $dateParts = explode('-', $date);
        $prefix = 'Sh' . $txn->id;

        if ($type === 'purchase') {
            $this->createJournalEntry($prefix, $share, $product->share_suspense_control_chart_of_account_id, 0, $txn->amount, 'share_purchase', $date, $dateParts);
            $this->createJournalEntry($prefix, $share, $product->share_reference_chart_of_account_id, $txn->amount, 0, 'share_purchase', $date, $dateParts);
        } else {
            $this->createJournalEntry($prefix, $share, $product->share_reference_chart_of_account_id, 0, $txn->amount, 'share_redeem', $date, $dateParts);
            $this->createJournalEntry($prefix, $share, $product->share_suspense_control_chart_of_account_id, $txn->amount, 0, 'share_redeem', $date, $dateParts);
        }
    }

    protected function postSavingsJournal(Savings $savings, SavingsTransaction $txn, string $type): void
    {
        if ($savings->savings_product->accounting_rule !== 'cash') {
            return;
        }
        $date = $txn->submitted_on;
        $dateParts = explode('-', $date);
        $prefix = 'S' . $txn->id;

        if ($type === 'deposit') {
            $this->createSavingsJournalEntry($prefix, $savings, $savings->savings_product->savings_control_chart_of_account_id, 0, $txn->amount, 'savings_deposit', $date, $dateParts);
            $this->createSavingsJournalEntry($prefix, $savings, $savings->savings_product->savings_reference_chart_of_account_id, $txn->amount, 0, 'savings_deposit', $date, $dateParts);
        } else {
            $this->createSavingsJournalEntry($prefix, $savings, $savings->savings_product->savings_reference_chart_of_account_id, 0, $txn->amount, 'savings_withdrawal', $date, $dateParts);
            $this->createSavingsJournalEntry($prefix, $savings, $savings->savings_product->savings_control_chart_of_account_id, $txn->amount, 0, 'savings_withdrawal', $date, $dateParts);
        }
    }

    protected function createJournalEntry($prefix, Share $share, $coaId, $debit, $credit, $type, $date, $dateParts): void
    {
        $entry = new JournalEntry();
        $entry->created_by_id = Auth::id();
        $entry->transaction_number = $prefix;
        $entry->branch_id = $share->branch_id;
        $entry->currency_id = $share->currency_id;
        $entry->chart_of_account_id = $coaId;
        $entry->transaction_type = $type;
        $entry->date = $date;
        $entry->month = $dateParts[1] ?? date('m');
        $entry->year = $dateParts[0] ?? date('Y');
        $entry->debit = $debit;
        $entry->credit = $credit;
        $entry->reference = $share->id;
        $entry->save();
    }

    protected function createSavingsJournalEntry($prefix, Savings $savings, $coaId, $debit, $credit, $type, $date, $dateParts): void
    {
        $entry = new JournalEntry();
        $entry->created_by_id = Auth::id();
        $entry->transaction_number = $prefix;
        $entry->branch_id = $savings->branch_id;
        $entry->currency_id = $savings->currency_id;
        $entry->chart_of_account_id = $coaId;
        $entry->transaction_type = $type;
        $entry->date = $date;
        $entry->month = $dateParts[1] ?? date('m');
        $entry->year = $dateParts[0] ?? date('Y');
        $entry->debit = $debit;
        $entry->credit = $credit;
        $entry->reference = $savings->id;
        $entry->save();
    }

    protected function generateReference(): string
    {
        return 'STX-' . strtoupper(substr(uniqid(), -8)) . '-' . time();
    }

    protected function assertEnabled(): void
    {
        if (!$this->isEnabled()) {
            throw new \RuntimeException('Share marketplace is currently disabled.');
        }
    }
}
