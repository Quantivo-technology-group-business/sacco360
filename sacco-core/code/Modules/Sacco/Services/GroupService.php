<?php

namespace Modules\Sacco\Services;

use Carbon\Carbon;
use Modules\Sacco\Entities\SaccoGroup;
use Modules\Sacco\Entities\SaccoGroupContribution;
use Modules\Sacco\Entities\SaccoGroupMember;
use Modules\Sacco\Entities\SaccoGroupPenalty;
use Modules\Sacco\Entities\SaccoMerryGoRoundCycle;
use Modules\Sacco\Entities\SaccoRotationBid;

class GroupService
{
    /**
     * Add a member to a group and rebuild the upcoming schedule for merry-go-round groups.
     */
    public function addMember(SaccoGroup $group, int $clientId, string $role = 'member', ?int $createdById = null): SaccoGroupMember
    {
        $existing = SaccoGroupMember::where('sacco_group_id', $group->id)
            ->where('client_id', $clientId)
            ->whereIn('status', ['active', 'suspended'])
            ->first();

        if ($existing) {
            throw new \Exception('Member is already in this group.');
        }

        if ($group->max_members && $group->activeMembers()->count() >= $group->max_members) {
            throw new \Exception("Group has reached its maximum of {$group->max_members} members.");
        }

        $member = SaccoGroupMember::create([
            'sacco_group_id' => $group->id,
            'client_id'      => $clientId,
            'role'           => $role,
            'joined_date'    => Carbon::today(),
            'status'         => 'active',
        ]);

        if ($group->type === 'merry_go_round') {
            $this->rebuildUpcomingSchedule($group, $this->getScheduleStartDate($group), $createdById ?? auth()->id() ?? 1);
        }

        return $member;
    }

    /**
     * Remove / exit a member from a group and rebuild the upcoming schedule when needed.
     */
    public function removeMember(SaccoGroupMember $member, string $reason = null, ?int $changedById = null): void
    {
        $member->status = 'exited';
        $member->exit_date = Carbon::today();
        $member->save();

        if ($member->group && $member->group->type === 'merry_go_round') {
            $this->rebuildUpcomingSchedule($member->group, $this->getScheduleStartDate($member->group), $changedById ?? auth()->id() ?? 1);
        }
    }

    /**
     * Post group contributions for a given period.
     */
    public function postContributions(SaccoGroup $group, string $period, int $createdById): int
    {
        $posted = 0;
        foreach ($group->activeMembers as $member) {
            $exists = SaccoGroupContribution::where('sacco_group_id', $group->id)
                ->where('sacco_group_member_id', $member->id)
                ->where('period', $period)
                ->exists();

            if ($exists) {
                continue;
            }

            SaccoGroupContribution::create([
                'sacco_group_id'        => $group->id,
                'sacco_group_member_id' => $member->id,
                'client_id'             => $member->client_id,
                'amount'                => $group->monthly_contribution,
                'contribution_date'     => Carbon::today(),
                'period'                => $period,
                'created_by_id'         => $createdById,
                'status'                => 'posted',
            ]);

            $posted++;
        }

        return $posted;
    }

    /**
     * Generate merry-go-round cycle schedule for a group.
     */
    public function generateMerryGoRoundSchedule(SaccoGroup $group, Carbon $startDate, int $createdById): void
    {
        $this->rebuildUpcomingSchedule($group, $startDate, $createdById);
    }

    /**
     * Skip a turn and shift the sequence by rebuilding upcoming cycles.
     */
    public function skipMemberTurn(SaccoMerryGoRoundCycle $cycle, ?int $changedById = null): void
    {
        if (!in_array($cycle->status, ['upcoming', 'active'], true)) {
            throw new \Exception('Only upcoming turns can be skipped.');
        }

        $cycle->status = 'skipped';
        $cycle->notes = trim(($cycle->notes ? $cycle->notes . "\n" : '') . 'Skipped by admin');
        $cycle->save();

        $this->rebuildUpcomingSchedule($cycle->group, $cycle->cycle_date ?? Carbon::today(), $changedById ?? auth()->id() ?? 1);
    }

    /**
     * Swap beneficiaries between two upcoming cycles.
     */
    public function swapMemberTurns(int $cycleIdA, int $cycleIdB): void
    {
        $cycleA = SaccoMerryGoRoundCycle::findOrFail($cycleIdA);
        $cycleB = SaccoMerryGoRoundCycle::findOrFail($cycleIdB);

        if ($cycleA->sacco_group_id !== $cycleB->sacco_group_id) {
            throw new \Exception('The selected cycles must belong to the same group.');
        }

        if (!in_array($cycleA->status, ['upcoming', 'active'], true) || !in_array($cycleB->status, ['upcoming', 'active'], true)) {
            throw new \Exception('Only upcoming turns can be swapped.');
        }

        $beneficiaryA = $cycleA->beneficiary_client_id;
        $beneficiaryB = $cycleB->beneficiary_client_id;

        $cycleA->beneficiary_client_id = $beneficiaryB;
        $cycleB->beneficiary_client_id = $beneficiaryA;

        $cycleA->save();
        $cycleB->save();
    }

    /**
     * Submit or update a bidding amount for a cycle.
     */
    public function submitBid(int $groupId, int $cycleNo, int $clientId, float $bidAmount): SaccoRotationBid
    {
        $group = SaccoGroup::findOrFail($groupId);
        if ($group->rotation_model !== 'bidding') {
            throw new \Exception('This group does not use bidding rotation.');
        }

        $bid = SaccoRotationBid::where('sacco_group_id', $groupId)
            ->where('cycle_number', $cycleNo)
            ->where('client_id', $clientId)
            ->first();

        if ($bid) {
            $bid->bid_amount = $bidAmount;
            $bid->status = 'pending';
            $bid->save();
            return $bid;
        }

        return SaccoRotationBid::create([
            'sacco_group_id' => $groupId,
            'cycle_number'   => $cycleNo,
            'client_id'      => $clientId,
            'bid_amount'     => $bidAmount,
            'status'         => 'pending',
        ]);
    }

    /**
     * Resolve the highest bid for a bidding cycle, then complete it.
     *
     * Winner selection stays here because it is specific to the bidding model;
     * the payout arithmetic is shared with fixed/random via completeCycle().
     *
     * @throws \Exception if the cycle was already resolved — re-running it would
     *         find no pending bids and silently wipe the beneficiary and the
     *         winning discount, which is how a resolved cycle lost its winner.
     */
    public function resolveBiddingCycle(int $cycleId, ?int $completedById = null): SaccoMerryGoRoundCycle
    {
        $cycle = SaccoMerryGoRoundCycle::findOrFail($cycleId);
        $cycle->load('group');

        if (in_array($cycle->status, ['completed', 'paid'], true)) {
            throw new \Exception('This cycle has already been resolved.');
        }

        $winningBid = SaccoRotationBid::where('sacco_group_id', $cycle->sacco_group_id)
            ->where('cycle_number', $cycle->cycle_number)
            ->where('status', 'pending')
            ->orderByDesc('bid_amount')
            ->first();

        if (!$winningBid) {
            // Nothing to resolve. Fail loudly rather than completing a cycle with
            // a null beneficiary and a zero discount.
            throw new \Exception('There are no pending bids for this cycle.');
        }

        SaccoRotationBid::where('sacco_group_id', $cycle->sacco_group_id)
            ->where('cycle_number', $cycle->cycle_number)
            ->where('status', 'pending')
            ->update(['status' => 'lost']);

        $winningBid->status = 'won';
        $winningBid->save();

        $cycle->beneficiary_client_id = $winningBid->client_id;
        $cycle->bidding_discount      = $winningBid->bid_amount;
        $cycle->save();

        return $this->completeCycle($cycle->id, $completedById);
    }

    /**
     * Complete a rotation cycle for any rotation model and compute its payout.
     *
     * Before this existed, resolveBiddingCycle() was the only code in the whole
     * Sacco module that ever wrote status 'completed'. Groups using the `fixed`
     * or `random` rotation models therefore had no way to close a turn at all,
     * and their late_payment_penalty was never applied.
     *
     * @throws \Exception if the cycle is already closed, was skipped, or has no
     *         beneficiary to pay.
     */
    public function completeCycle(int $cycleId, ?int $completedById = null): SaccoMerryGoRoundCycle
    {
        $cycle = SaccoMerryGoRoundCycle::findOrFail($cycleId);
        $cycle->loadMissing('group');

        if (in_array($cycle->status, ['completed', 'paid'], true)) {
            throw new \Exception('This cycle has already been completed.');
        }

        if ($cycle->status === 'skipped') {
            throw new \Exception('A skipped turn cannot be completed.');
        }

        if (!$cycle->beneficiary_client_id) {
            throw new \Exception('This cycle has no beneficiary, so there is nothing to pay out.');
        }

        $penalties = $this->assessCyclePenalties($cycle, $completedById);

        // Audit only: record what was actually posted for this cycle's period so
        // the gap against the theoretical pot is visible. Deliberately not used
        // in the payout maths yet — see Part G10.
        $collected = $this->collectedForCycle($cycle);

        $cycle->collected_amount   = $collected;
        $cycle->penalties_deducted = $penalties;
        $cycle->net_payout_amount  = max(
            0,
            (float) $cycle->pot_amount - (float) $cycle->bidding_discount - $penalties
        );
        $cycle->status = 'completed';
        $cycle->save();

        return $cycle;
    }

    /**
     * Assess per-member late-payment fines for a cycle and return their total.
     *
     * This replaces the previous behaviour of deducting the group's
     * late_payment_penalty once per cycle as a flat amount, regardless of how
     * many members were late, whether anyone was, or who they were — which both
     * underpaid the beneficiary when nobody was late and underpaid the group
     * when several members were, with no record of either.
     *
     * A member is fined when, for the cycle's period, they have either no posted
     * contribution at all (missed) or one dated after the cycle date (late).
     *
     * Idempotent: re-assessing a cycle adds nothing for a member who already has
     * a fine, and the unique index on (cycle, member) enforces the same at the
     * database level.
     *
     * @return float total of this cycle's non-waived fines
     */
    public function assessCyclePenalties(SaccoMerryGoRoundCycle $cycle, ?int $assessedById = null): float
    {
        $cycle->loadMissing('group');
        $group = $cycle->group;

        $rate = (float) ($group->late_payment_penalty ?? 0);

        // Nothing to assess, and no cycle_date to judge lateness against. Return
        // whatever was previously assessed rather than silently zeroing it.
        if ($rate <= 0 || !$cycle->cycle_date) {
            return $this->activePenaltyTotal($cycle);
        }

        $cycleDate = Carbon::parse($cycle->cycle_date)->endOfDay();
        $period    = Carbon::parse($cycle->cycle_date)->format('Y-m');

        foreach ($group->activeMembers as $member) {
            $alreadyFined = SaccoGroupPenalty::where('sacco_merry_go_round_cycle_id', $cycle->id)
                ->where('sacco_group_member_id', $member->id)
                ->exists();

            if ($alreadyFined) {
                continue;
            }

            $contribution = SaccoGroupContribution::where('sacco_group_id', $group->id)
                ->where('sacco_group_member_id', $member->id)
                ->where('period', $period)
                ->where('status', 'posted')
                ->first();

            if ($contribution) {
                $paidOn = Carbon::parse($contribution->contribution_date)->endOfDay();

                if (!$paidOn->greaterThan($cycleDate)) {
                    continue; // paid on time
                }

                $type   = 'late_contribution';
                $reason = sprintf(
                    'Contribution for %s dated %s, after the cycle due date %s.',
                    $period,
                    $paidOn->toDateString(),
                    $cycleDate->toDateString()
                );
            } else {
                $type   = 'missed_contribution';
                $reason = sprintf(
                    'No posted contribution for period %s as at %s.',
                    $period,
                    Carbon::today()->toDateString()
                );
            }

            SaccoGroupPenalty::create([
                'sacco_group_id'                  => $group->id,
                'sacco_group_member_id'           => $member->id,
                'client_id'                       => $member->client_id,
                'sacco_merry_go_round_cycle_id'   => $cycle->id,
                'penalty_type'                    => $type,
                'period'                          => $period,
                'amount'                          => $rate,
                'status'                          => 'assessed',
                'reason'                          => $reason,
                'assessed_by_id'                  => $assessedById,
            ]);
        }

        return $this->activePenaltyTotal($cycle);
    }

    /**
     * Waive a fine so it stops counting against the cycle.
     *
     * Kept as a row with status 'waived' rather than deleted, so the fact that
     * it was raised and then cancelled by an officer remains on the record.
     */
    public function waivePenalty(SaccoGroupPenalty $penalty, string $reason, ?int $waivedById = null): SaccoGroupPenalty
    {
        if ($penalty->status === 'waived') {
            throw new \Exception('This penalty has already been waived.');
        }

        if ($penalty->status === 'collected') {
            throw new \Exception('A collected penalty cannot be waived.');
        }

        $penalty->status      = 'waived';
        $penalty->waived_by_id = $waivedById;
        $penalty->waived_at   = Carbon::now();
        $penalty->reason      = trim(($penalty->reason ? $penalty->reason . "\n" : '') . 'Waived: ' . $reason);
        $penalty->save();

        // The cycle's deduction must follow, or the beneficiary would still be
        // charged for a fine that no longer exists.
        if ($penalty->sacco_merry_go_round_cycle_id) {
            $cycle = SaccoMerryGoRoundCycle::find($penalty->sacco_merry_go_round_cycle_id);

            if ($cycle && !in_array($cycle->status, ['paid'], true)) {
                $cycle->penalties_deducted = $this->activePenaltyTotal($cycle);
                $cycle->net_payout_amount  = max(
                    0,
                    (float) $cycle->pot_amount - (float) $cycle->bidding_discount - (float) $cycle->penalties_deducted
                );
                $cycle->save();
            }
        }

        return $penalty;
    }

    /**
     * Total of a cycle's fines that still count (assessed or collected).
     */
    private function activePenaltyTotal(SaccoMerryGoRoundCycle $cycle): float
    {
        return (float) SaccoGroupPenalty::where('sacco_merry_go_round_cycle_id', $cycle->id)
            ->active()
            ->sum('amount');
    }

    /**
     * Sum of posted contributions for the period a cycle falls in.
     */
    private function collectedForCycle(SaccoMerryGoRoundCycle $cycle): ?float
    {
        if (!$cycle->cycle_date) {
            return null;
        }

        $period = Carbon::parse($cycle->cycle_date)->format('Y-m');

        return (float) SaccoGroupContribution::where('sacco_group_id', $cycle->sacco_group_id)
            ->where('period', $period)
            ->where('status', 'posted')
            ->sum('amount');
    }

    /**
     * Mark a cycle as disbursed and record the payout reference.
     */
    public function disburseCycle(int $cycleId, string $reference): SaccoMerryGoRoundCycle
    {
        $cycle = SaccoMerryGoRoundCycle::findOrFail($cycleId);
        if ($cycle->status === 'paid') {
            throw new \Exception('This cycle has already been disbursed.');
        }

        $cycle->net_payout_amount = (float) $cycle->net_payout_amount ?: max(0, (float) $cycle->pot_amount - (float) $cycle->bidding_discount - (float) $cycle->penalties_deducted);
        $cycle->status = 'paid';
        $cycle->disbursed_at = Carbon::now();
        $cycle->disbursement_reference = $reference;
        $cycle->save();

        return $cycle;
    }

    /**
     * Get total contributions by a member across all groups.
     */
    public function getMemberTotalContributions(int $clientId): float
    {
        return (float) SaccoGroupContribution::where('client_id', $clientId)
            ->where('status', 'posted')
            ->sum('amount');
    }

    /**
     * Approve a member-submitted contribution, moving it from 'pending' to 'posted'.
     *
     * This is the only sanctioned path out of 'pending'. Before it existed, the
     * portal recorded cash/bank/M-Pesa contributions as pending and nothing in
     * the codebase ever confirmed them.
     *
     * @param int|null $approvedById the officer approving, or null when the
     *                               approval is automated (M-Pesa STK callback).
     *                               Null is recorded as-is rather than defaulted
     *                               to a real user, so the audit trail never
     *                               attributes a system action to a person.
     */
    public function approveContribution(SaccoGroupContribution $contribution, ?int $approvedById = null): SaccoGroupContribution
    {
        if ($contribution->status === 'posted') {
            throw new \Exception('This contribution has already been approved.');
        }

        if ($contribution->status === 'reversed') {
            throw new \Exception('A reversed contribution cannot be approved.');
        }

        $contribution->status         = 'posted';
        $contribution->approved_by_id = $approvedById;
        $contribution->approved_at    = Carbon::now();
        $contribution->rejection_reason = null;
        $contribution->save();

        return $contribution;
    }

    /**
     * Reject a pending contribution. The row is kept for audit rather than deleted.
     */
    public function rejectContribution(SaccoGroupContribution $contribution, string $reason, int $approvedById): SaccoGroupContribution
    {
        if ($contribution->status !== 'pending') {
            throw new \Exception('Only a pending contribution can be rejected.');
        }

        $contribution->status           = 'rejected';
        $contribution->approved_by_id   = $approvedById;
        $contribution->approved_at      = Carbon::now();
        $contribution->rejection_reason = $reason;
        $contribution->save();

        return $contribution;
    }

    /**
     * Reverse a posted contribution (wrong amount, duplicate, bounced payment).
     *
     * The row is retained with status 'reversed' so the audit trail survives;
     * getMemberTotalContributions() and every report already filter on 'posted'.
     */
    public function reverseContribution(SaccoGroupContribution $contribution, string $reason, int $approvedById): SaccoGroupContribution
    {
        if ($contribution->status === 'reversed') {
            throw new \Exception('This contribution has already been reversed.');
        }

        if ($contribution->status === 'pending') {
            throw new \Exception('A pending contribution should be rejected, not reversed.');
        }

        $contribution->status           = 'reversed';
        $contribution->approved_by_id   = $approvedById;
        $contribution->approved_at      = Carbon::now();
        $contribution->rejection_reason = $reason;
        $contribution->save();

        return $contribution;
    }

    /**
     * Record a successful M-Pesa STK callback against a contribution.
     *
     * Matched on the Daraja CheckoutRequestID stored at push time. Idempotent:
     * Safaricom retries callbacks, and a second delivery must not double-post.
     *
     * @return SaccoGroupContribution|null null when no contribution matches
     */
    public function confirmMpesaContribution(string $checkoutRequestId, string $receiptNumber, ?float $paidAmount = null): ?SaccoGroupContribution
    {
        $contribution = SaccoGroupContribution::where('checkout_request_id', $checkoutRequestId)
            ->whereIn('status', ['pending', 'rejected'])
            ->first();

        if (!$contribution) {
            // Already confirmed by an earlier callback delivery, or unknown id.
            return null;
        }

        // Amount mismatch is a genuine exception — record it rather than
        // silently posting a different figure than the member authorised.
        if ($paidAmount !== null && abs($paidAmount - (float) $contribution->amount) > 0.01) {
            $contribution->status           = 'pending';
            $contribution->rejection_reason = sprintf(
                'M-Pesa amount mismatch: expected %.2f, received %.2f (receipt %s). Requires manual approval.',
                (float) $contribution->amount,
                $paidAmount,
                $receiptNumber
            );
            $contribution->mpesa_receipt_number = $receiptNumber;
            $contribution->save();

            return $contribution;
        }

        $contribution->mpesa_receipt_number = $receiptNumber;
        $contribution->payment_reference    = $contribution->payment_reference ?: $receiptNumber;
        $contribution->notes                = trim(($contribution->notes ? $contribution->notes . "\n" : '')
            . 'Confirmed automatically by M-Pesa STK callback (receipt ' . $receiptNumber . ').');

        // No human approver: this is a system confirmation. Passing a fallback
        // user id here would make an automated posting look like an officer
        // approval in the audit trail.
        return $this->approveContribution($contribution, null);
    }

    /**
     * Rebuild upcoming merry-go-round cycles using the configured rotation model.
     */
    private function rebuildUpcomingSchedule(SaccoGroup $group, Carbon $startDate, int $createdById): void
    {
        $members = $group->activeMembers()->with('client')->orderBy('joined_date')->get();

        if ($members->isEmpty()) {
            throw new \Exception('No active members to generate schedule for.');
        }

        SaccoMerryGoRoundCycle::where('sacco_group_id', $group->id)
            ->whereIn('status', ['upcoming', 'active'])
            ->delete();

        $orderedMembers = $this->orderMembers($members, $group->rotation_model);
        $cycleDate = $startDate->copy();
        $existingMax = SaccoMerryGoRoundCycle::where('sacco_group_id', $group->id)->max('cycle_number') ?? 0;
        $cycleNumber = $existingMax + 1;
        $potAmount = $group->monthly_contribution * $orderedMembers->count();

        foreach ($orderedMembers as $member) {
            SaccoMerryGoRoundCycle::create([
                'sacco_group_id'        => $group->id,
                'cycle_number'          => $cycleNumber++,
                'beneficiary_client_id' => $group->rotation_model === 'bidding' ? null : $member->client_id,
                'pot_amount'            => $potAmount,
                'cycle_date'            => $cycleDate->toDateString(),
                'status'                => 'upcoming',
                'created_by_id'         => $createdById,
            ]);

            $cycleDate->addMonth();
        }
    }

    /**
     * Order members according to the configured rotation model.
     */
    private function orderMembers($members, string $rotationModel)
    {
        $ordered = $members->values();

        if ($rotationModel === 'random') {
            return $ordered->shuffle();
        }

        return $ordered->sortBy('joined_date')->values();
    }

    /**
     * Determine the start date for a rebuilt schedule.
     */
    private function getScheduleStartDate(SaccoGroup $group): Carbon
    {
        $nextCycle = SaccoMerryGoRoundCycle::where('sacco_group_id', $group->id)
            ->whereIn('status', ['upcoming', 'active'])
            ->orderBy('cycle_number')
            ->first();

        if ($nextCycle && $nextCycle->cycle_date) {
            return Carbon::parse($nextCycle->cycle_date);
        }

        $latestCycle = SaccoMerryGoRoundCycle::where('sacco_group_id', $group->id)
            ->orderByDesc('cycle_number')
            ->first();

        if ($latestCycle && $latestCycle->cycle_date) {
            return Carbon::parse($latestCycle->cycle_date)->addMonth();
        }

        return Carbon::today();
    }
}
