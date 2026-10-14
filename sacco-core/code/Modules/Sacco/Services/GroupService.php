<?php

namespace Modules\Sacco\Services;

use Carbon\Carbon;
use Modules\Sacco\Entities\SaccoGroup;
use Modules\Sacco\Entities\SaccoGroupContribution;
use Modules\Sacco\Entities\SaccoGroupMember;
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
     * Resolve the highest bid for a bidding cycle.
     */
    public function resolveBiddingCycle(int $cycleId): SaccoMerryGoRoundCycle
    {
        $cycle = SaccoMerryGoRoundCycle::findOrFail($cycleId);
        $cycle->load('group');

        $winningBid = SaccoRotationBid::where('sacco_group_id', $cycle->sacco_group_id)
            ->where('cycle_number', $cycle->cycle_number)
            ->where('status', 'pending')
            ->orderByDesc('bid_amount')
            ->first();

        SaccoRotationBid::where('sacco_group_id', $cycle->sacco_group_id)
            ->where('cycle_number', $cycle->cycle_number)
            ->where('status', 'pending')
            ->update(['status' => 'lost']);

        if ($winningBid) {
            $winningBid->status = 'won';
            $winningBid->save();
            $cycle->beneficiary_client_id = $winningBid->client_id;
            $cycle->bidding_discount = $winningBid->bid_amount;
        } else {
            $cycle->beneficiary_client_id = null;
            $cycle->bidding_discount = 0;
        }

        $cycle->penalties_deducted = (float) ($cycle->group->late_payment_penalty ?? 0);
        $cycle->net_payout_amount = max(0, (float) $cycle->pot_amount - (float) $cycle->bidding_discount - (float) $cycle->penalties_deducted);
        $cycle->status = 'completed';
        $cycle->save();

        return $cycle;
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
