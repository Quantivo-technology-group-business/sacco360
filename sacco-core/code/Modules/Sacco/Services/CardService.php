<?php

namespace Modules\Sacco\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Sacco\Entities\SaccoDebitCard;
use Modules\Sacco\Entities\SaccoCardTransaction;
use Modules\Sacco\Entities\SaccoCardDispute;
use Modules\Sacco\Entities\SaccoCardPinLog;
use Modules\Sacco\Entities\SaccoCardRequest;

class CardService
{
    // ─── Card Lifecycle ───────────────────────────────────────────────────────

    /**
     * Create a card from an approved card request.
     */
    public function issueFromRequest(SaccoCardRequest $req, int $issuedById): SaccoDebitCard
    {
        // Block any previous active card for replacement requests
        if ($req->request_type === 'replacement' && $req->replaced_card_id) {
            $old = SaccoDebitCard::find($req->replaced_card_id);
            if ($old && $old->status === 'active') {
                $old->status = 'cancelled';
                $old->cancelled_reason = 'Replaced by new card via request #' . $req->id;
                $old->save();
            }
        }

        $card = SaccoDebitCard::create([
            'client_id'      => $req->client_id,
            'savings_id'     => $req->savings_id,
            'card_type'      => $req->card_type,
            'card_network'   => $req->card_network,
            'card_name'      => $req->preferred_name,
            'card_number'    => $this->generateCardNumber(),
            'card_token'     => Str::random(64),
            'issue_date'     => Carbon::today(),
            'expiry_date'    => Carbon::today()->addYears(3),
            'status'         => 'pending',
            'daily_limit'    => 50000,
            'atm_daily_limit'=> 20000,
            'pos_daily_limit'=> 100000,
            'online_daily_limit' => 50000,
            'contactless_limit'  => 5000,
            'delivery_status'=> $req->card_type === 'virtual' ? 'delivered' : 'not_dispatched',
            'delivery_address'   => $req->delivery_address,
            'issued_by_id'   => $issuedById,
        ]);

        // Link card back to request
        $req->sacco_debit_card_id = $card->id;
        $req->status              = 'processing';
        $req->processed_by_id     = $issuedById;
        $req->processed_date      = Carbon::today();
        $req->save();

        // Virtual cards are immediately active
        if ($card->card_type === 'virtual') {
            $card->status = 'active';
            $card->save();
        }

        return $card;
    }

    /**
     * Block a card (lost/stolen/admin).
     */
    public function block(SaccoDebitCard $card, string $reason, int $blockedById): void
    {
        $card->status         = 'blocked';
        $card->blocked_reason = $reason;
        $card->save();

        if (function_exists('sacco_compliance_log')) {
            sacco_compliance_log('card_blocked', 'sacco', 'debit_card', $card->id, $reason);
        }
    }

    /**
     * Activate a pending or blocked card.
     */
    public function activate(SaccoDebitCard $card, int $activatedById): void
    {
        $card->status = 'active';
        $card->blocked_reason = null;
        $card->save();
    }

    /**
     * Permanently cancel a card.
     */
    public function cancel(SaccoDebitCard $card, string $reason, int $cancelledById): void
    {
        $card->status           = 'cancelled';
        $card->cancelled_reason = $reason;
        $card->save();
    }

    /**
     * Report card lost or stolen — blocks it and creates a replacement request.
     */
    public function reportLostStolen(SaccoDebitCard $card, string $reason, int $reportedById): SaccoCardRequest
    {
        // Block the card immediately
        $this->block($card, "Reported {$reason}", $reportedById);

        // Create replacement request automatically
        $request = SaccoCardRequest::create([
            'client_id'         => $card->client_id,
            'savings_id'        => $card->savings_id,
            'created_by_id'     => $reportedById,
            'request_type'      => 'replacement',
            'replacement_reason'=> $reason,
            'replaced_card_id'  => $card->id,
            'card_network'      => $card->card_network,
            'card_type'         => $card->card_type,
            'preferred_name'    => $card->card_name,
            'delivery_address'  => $card->delivery_address,
            'delivery_method'   => 'branch_pickup',
            'requested_date'    => Carbon::today(),
            'status'            => 'pending',
        ]);

        return $request;
    }

    /**
     * Update delivery status of a physical card.
     */
    public function updateDelivery(SaccoDebitCard $card, string $status, ?string $date = null): void
    {
        $card->delivery_status = $status;
        if ($status === 'dispatched') {
            $card->dispatched_date = $date ?? Carbon::today();
        }
        if ($status === 'delivered') {
            $card->delivered_date = $date ?? Carbon::today();
            // Auto-activate on delivery confirmation
            if ($card->status === 'pending') {
                $card->status = 'active';
            }
        }
        $card->save();
    }

    // ─── Limits & Controls ────────────────────────────────────────────────────

    /**
     * Update spending limits for a card.
     */
    public function updateLimits(SaccoDebitCard $card, array $limits): void
    {
        $card->fill(array_intersect_key($limits, array_flip([
            'daily_limit', 'atm_daily_limit', 'pos_daily_limit',
            'online_daily_limit', 'contactless_limit', 'monthly_limit',
        ])));
        $card->save();
    }

    /**
     * Toggle individual channels on/off.
     */
    public function updateChannels(SaccoDebitCard $card, array $channels): void
    {
        $card->fill(array_intersect_key($channels, array_flip([
            'atm_enabled', 'pos_enabled', 'online_enabled',
            'contactless_enabled', 'international_enabled',
        ])));
        $card->save();
    }

    // ─── PIN Management ───────────────────────────────────────────────────────

    /**
     * Mark PIN as set by member (self-service or branch).
     */
    public function markPinSet(SaccoDebitCard $card, int $actionedById = null, string $channel = 'branch'): void
    {
        $card->pin_set           = 1;
        $card->pin_failed_attempts = 0;
        $card->pin_locked        = 0;
        $card->save();

        SaccoCardPinLog::create([
            'sacco_debit_card_id' => $card->id,
            'client_id'           => $card->client_id,
            'action'              => $card->pin_set ? 'changed' : 'set',
            'actioned_by_id'      => $actionedById,
            'channel'             => $channel,
        ]);
    }

    /**
     * Reset PIN (staff action) — clears lock and failed attempts.
     */
    public function resetPin(SaccoDebitCard $card, int $staffId): void
    {
        $card->pin_set             = 0;
        $card->pin_failed_attempts = 0;
        $card->pin_locked          = 0;
        $card->save();

        SaccoCardPinLog::create([
            'sacco_debit_card_id' => $card->id,
            'client_id'           => $card->client_id,
            'action'              => 'reset_by_staff',
            'actioned_by_id'      => $staffId,
            'channel'             => 'branch',
        ]);
    }

    /**
     * Unlock a PIN-locked card.
     */
    public function unlockPin(SaccoDebitCard $card, int $staffId): void
    {
        $card->pin_locked          = 0;
        $card->pin_failed_attempts = 0;
        $card->save();

        SaccoCardPinLog::create([
            'sacco_debit_card_id' => $card->id,
            'client_id'           => $card->client_id,
            'action'              => 'unlocked',
            'actioned_by_id'      => $staffId,
            'channel'             => 'branch',
        ]);
    }

    // ─── Transactions ─────────────────────────────────────────────────────────

    /**
     * Record a card transaction (from ATM/POS switch or manual entry).
     */
    public function recordTransaction(SaccoDebitCard $card, array $data): SaccoCardTransaction
    {
        $data['sacco_debit_card_id']   = $card->id;
        $data['client_id']             = $card->client_id;
        $data['transaction_reference'] = $data['transaction_reference']
            ?? 'TXN-' . strtoupper(Str::random(12));
        $data['transaction_at']        = $data['transaction_at'] ?? Carbon::now();

        return SaccoCardTransaction::create($data);
    }

    /**
     * Check whether a proposed transaction is within limits.
     * Returns ['allowed' => bool, 'reason' => string|null].
     */
    public function checkLimits(SaccoDebitCard $card, float $amount, string $channel): array
    {
        if (!$card->isActive()) {
            return ['allowed' => false, 'reason' => 'Card is not active.'];
        }

        $channelMap = [
            'atm'         => ['enabled_flag' => 'atm_enabled',         'limit_col' => 'atm_daily_limit'],
            'pos'         => ['enabled_flag' => 'pos_enabled',         'limit_col' => 'pos_daily_limit'],
            'online'      => ['enabled_flag' => 'online_enabled',      'limit_col' => 'online_daily_limit'],
            'contactless' => ['enabled_flag' => 'contactless_enabled', 'limit_col' => 'contactless_limit'],
        ];

        if (isset($channelMap[$channel])) {
            $cfg = $channelMap[$channel];
            if (!$card->{$cfg['enabled_flag']}) {
                return ['allowed' => false, 'reason' => ucfirst($channel) . ' transactions are disabled on this card.'];
            }
            $todayChannelSpend = $card->getTodaySpend($channel);
            if (($todayChannelSpend + $amount) > $card->{$cfg['limit_col']}) {
                return ['allowed' => false, 'reason' => "Daily {$channel} limit of " . number_format($card->{$cfg['limit_col']}, 2) . ' would be exceeded.'];
            }
        }

        // Check overall daily limit
        if (($card->getTodaySpend() + $amount) > $card->daily_limit) {
            return ['allowed' => false, 'reason' => 'Overall daily spend limit would be exceeded.'];
        }

        // Check monthly limit
        if ($card->monthly_limit && ($card->getMonthlySpend() + $amount) > $card->monthly_limit) {
            return ['allowed' => false, 'reason' => 'Monthly spend limit would be exceeded.'];
        }

        return ['allowed' => true, 'reason' => null];
    }

    // ─── Disputes ─────────────────────────────────────────────────────────────

    /**
     * Submit a dispute for a card transaction.
     */
    public function submitDispute(SaccoDebitCard $card, array $data): SaccoCardDispute
    {
        $data['sacco_debit_card_id'] = $card->id;
        $data['client_id']           = $card->client_id;
        $data['dispute_reference']   = 'DISP-' . strtoupper(Str::random(8));
        $data['submitted_date']      = Carbon::today();
        $data['status']              = 'submitted';

        return SaccoCardDispute::create($data);
    }

    /**
     * Resolve a dispute (in favour or against member).
     */
    public function resolveDispute(SaccoCardDispute $dispute, string $outcome, ?float $settled, string $notes, int $resolvedById): void
    {
        $dispute->status           = $outcome; // 'resolved_in_favour' | 'resolved_against'
        $dispute->settled_amount   = $settled;
        $dispute->resolution_notes = $notes;
        $dispute->resolved_by_id   = $resolvedById;
        $dispute->resolution_date  = Carbon::today();
        $dispute->resolution_days  = Carbon::parse($dispute->submitted_date)->diffInDays(Carbon::today());
        $dispute->save();
    }

    // ─── Card Requests ────────────────────────────────────────────────────────

    /**
     * Submit a new card application or replacement request.
     */
    public function submitRequest(array $data): SaccoCardRequest
    {
        $data['requested_date'] = Carbon::today();
        $data['status']         = 'pending';
        return SaccoCardRequest::create($data);
    }

    /**
     * Approve a card request and issue the card.
     */
    public function approveRequest(SaccoCardRequest $req, int $staffId): SaccoDebitCard
    {
        $req->status          = 'approved';
        $req->processed_by_id = $staffId;
        $req->processed_date  = Carbon::today();
        $req->save();

        return $this->issueFromRequest($req, $staffId);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    protected function generateCardNumber(): string
    {
        do {
            $number = '4' . str_pad((string) random_int(0, 999999999999999), 15, '0', STR_PAD_LEFT);
        } while (SaccoDebitCard::where('card_number', $number)->exists());
        return $number;
    }
}
