<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Modules\Sacco\Entities\SaccoGroup;
use Modules\Sacco\Entities\SaccoGroupContribution;
use Modules\Sacco\Entities\SaccoGroupMember;
use Modules\Sacco\Entities\SaccoGroupPenalty;
use Modules\Sacco\Entities\SaccoMerryGoRoundCycle;
use Modules\Sacco\Entities\SaccoRotationBid;
use Modules\Sacco\Services\GroupService;

/**
 * Covers per-member late-payment fines and rotation-cycle completion (P0 item A4).
 *
 * The defect this locks down: `sacco_groups.late_payment_penalty` was read in
 * exactly one place, GroupService::resolveBiddingCycle(), where it was deducted
 * from the pot as a single flat amount every cycle — regardless of how many
 * members were late, whether anyone was, or who they were. Groups on the
 * `fixed` or `random` rotation models never applied it at all, because no code
 * path other than the bidding one ever wrote status 'completed'.
 *
 * Status columns are declared as real enums, mirroring production. The
 * pre-existing rotation test used plain strings, which is how disburseCycle()
 * writing the out-of-enum value 'paid' went unnoticed.
 */
class SaccoGroupCyclePenaltyTest extends \TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        // middle_name is required: Client::$appends builds name/full_name/name_id
        // from first + middle + last, so omitting it breaks any Client hydration.
        Schema::create('clients', function ($table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->timestamps();
        });

        Schema::create('sacco_groups', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('merry_go_round');
            $table->decimal('monthly_contribution', 10, 2)->default(0);
            $table->string('status')->default('active');
            $table->string('rotation_model')->default('fixed');
            $table->decimal('late_payment_penalty', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('sacco_group_members', function ($table) {
            $table->id();
            $table->unsignedBigInteger('sacco_group_id');
            $table->unsignedBigInteger('client_id');
            $table->string('role')->default('member');
            $table->date('joined_date');
            $table->date('exit_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('sacco_group_contributions', function ($table) {
            $table->id();
            $table->unsignedBigInteger('sacco_group_id');
            $table->unsignedBigInteger('sacco_group_member_id');
            $table->unsignedBigInteger('client_id');
            $table->decimal('amount', 10, 2);
            $table->date('contribution_date');
            $table->string('period')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->enum('status', ['pending', 'posted', 'rejected', 'reversed'])->default('posted');
            $table->string('payment_method')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_merry_go_round_cycles', function ($table) {
            $table->id();
            $table->unsignedBigInteger('sacco_group_id');
            $table->integer('cycle_number');
            $table->unsignedBigInteger('beneficiary_client_id')->nullable();
            $table->decimal('pot_amount', 10, 2)->default(0);
            $table->decimal('collected_amount', 10, 2)->nullable();
            $table->date('cycle_date');
            $table->enum('status', ['upcoming', 'active', 'completed', 'skipped', 'paid'])->default('upcoming');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('bidding_discount', 10, 2)->default(0);
            $table->decimal('penalties_deducted', 10, 2)->default(0);
            $table->decimal('net_payout_amount', 10, 2)->default(0);
            $table->dateTime('disbursed_at')->nullable();
            $table->string('disbursement_reference')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_group_penalties', function ($table) {
            $table->id();
            $table->unsignedBigInteger('sacco_group_id');
            $table->unsignedBigInteger('sacco_group_member_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('sacco_merry_go_round_cycle_id')->nullable();
            $table->enum('penalty_type', ['late_contribution', 'missed_contribution'])->default('late_contribution');
            $table->string('period', 7)->nullable();
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['assessed', 'waived', 'collected'])->default('assessed');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('assessed_by_id')->nullable();
            $table->unsignedBigInteger('waived_by_id')->nullable();
            $table->dateTime('waived_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_rotation_bids', function ($table) {
            $table->id();
            $table->unsignedBigInteger('sacco_group_id');
            $table->integer('cycle_number');
            $table->unsignedBigInteger('client_id');
            $table->decimal('bid_amount', 10, 2);
            $table->enum('status', ['pending', 'won', 'lost'])->default('pending');
            $table->timestamps();
        });
    }

    // ── Fixtures ─────────────────────────────────────────────────────────────

    protected function makeGroup(array $overrides = []): SaccoGroup
    {
        return SaccoGroup::create(array_merge([
            'name' => 'Test Chama',
            'type' => 'merry_go_round',
            'monthly_contribution' => 1000,
            'rotation_model' => 'fixed',
            'late_payment_penalty' => 100,
        ], $overrides));
    }

    protected function makeMembership(SaccoGroup $group, string $first, string $last, string $status = 'active'): SaccoGroupMember
    {
        $clientId = \DB::table('clients')->insertGetId([
            'first_name' => $first,
            'middle_name' => null,
            'last_name' => $last,
        ]);

        return SaccoGroupMember::create([
            'sacco_group_id' => $group->id,
            'client_id' => $clientId,
            'role' => 'member',
            'joined_date' => '2024-01-10',
            'status' => $status,
        ]);
    }

    protected function makeContribution(SaccoGroup $group, SaccoGroupMember $member, array $overrides = []): SaccoGroupContribution
    {
        return SaccoGroupContribution::create(array_merge([
            'sacco_group_id' => $group->id,
            'sacco_group_member_id' => $member->id,
            'client_id' => $member->client_id,
            'amount' => 1000,
            'contribution_date' => '2026-10-01',
            'period' => '2026-10',
            'status' => 'posted',
            'payment_method' => 'cash',
        ], $overrides));
    }

    protected function makeCycle(SaccoGroup $group, array $overrides = []): SaccoMerryGoRoundCycle
    {
        return SaccoMerryGoRoundCycle::create(array_merge([
            'sacco_group_id' => $group->id,
            'cycle_number' => 1,
            'beneficiary_client_id' => null,
            'pot_amount' => 3000,
            'cycle_date' => '2026-10-15',
            'status' => 'active',
        ], $overrides));
    }

    /**
     * Three members; $lateCount of them pay after the cycle date, the rest on time.
     *
     * @return array{0:SaccoGroup,1:SaccoMerryGoRoundCycle}
     */
    protected function makeGroupWithLateMembers(int $lateCount, array $groupOverrides = []): array
    {
        $group = $this->makeGroup($groupOverrides);

        $members = [
            $this->makeMembership($group, 'Ada', 'Lovelace'),
            $this->makeMembership($group, 'Grace', 'Hopper'),
            $this->makeMembership($group, 'Katherine', 'Johnson'),
        ];

        foreach ($members as $i => $member) {
            $this->makeContribution($group, $member, [
                // cycle_date is 2026-10-15; anything after that is late.
                'contribution_date' => $i < $lateCount ? '2026-10-20' : '2026-10-01',
                'period' => '2026-10',
            ]);
        }

        $cycle = $this->makeCycle($group, ['beneficiary_client_id' => $members[0]->client_id]);

        return [$group, $cycle];
    }

    // ── Fine assessment ──────────────────────────────────────────────────────

    public function test_everyone_paying_on_time_produces_no_fines_and_pays_the_full_pot(): void
    {
        [$group, $cycle] = $this->makeGroupWithLateMembers(0);

        $completed = (new GroupService())->completeCycle($cycle->id);

        $this->assertSame(0, SaccoGroupPenalty::count());
        $this->assertSame(0.0, (float) $completed->penalties_deducted);
        $this->assertSame(3000.0, (float) $completed->net_payout_amount);
    }

    public function test_a_contribution_dated_after_the_cycle_date_is_fined_as_late(): void
    {
        [$group, $cycle] = $this->makeGroupWithLateMembers(1);

        $completed = (new GroupService())->completeCycle($cycle->id);

        $this->assertSame(1, SaccoGroupPenalty::count());

        $penalty = SaccoGroupPenalty::first();
        $this->assertSame('late_contribution', $penalty->penalty_type);
        $this->assertSame(100.0, (float) $penalty->amount);
        $this->assertSame('2026-10', $penalty->period);
        $this->assertSame($cycle->id, (int) $penalty->sacco_merry_go_round_cycle_id);
        $this->assertSame(100.0, (float) $completed->penalties_deducted);
        $this->assertSame(2900.0, (float) $completed->net_payout_amount);
    }

    public function test_a_member_with_no_posted_contribution_is_fined_as_missing(): void
    {
        $group = $this->makeGroup();
        $paid = $this->makeMembership($group, 'Ada', 'Lovelace');
        $unpaid = $this->makeMembership($group, 'Grace', 'Hopper');

        $this->makeContribution($group, $paid, ['contribution_date' => '2026-10-01']);
        // $unpaid deliberately contributes nothing.

        $cycle = $this->makeCycle($group, ['beneficiary_client_id' => $paid->client_id]);

        (new GroupService())->completeCycle($cycle->id);

        $this->assertSame(1, SaccoGroupPenalty::count());
        $penalty = SaccoGroupPenalty::first();
        $this->assertSame('missed_contribution', $penalty->penalty_type);
        $this->assertSame($unpaid->client_id, (int) $penalty->client_id);
    }

    /**
     * The core A4 regression: the old code deducted one flat penalty per cycle
     * no matter how many members were late, so three late members cost the pot
     * 100 instead of 300.
     */
    public function test_the_fine_is_per_member_not_a_flat_once_per_cycle_deduction(): void
    {
        [$group, $cycle] = $this->makeGroupWithLateMembers(3);

        $completed = (new GroupService())->completeCycle($cycle->id);

        $this->assertSame(3, SaccoGroupPenalty::count());
        $this->assertSame(300.0, (float) $completed->penalties_deducted);
        $this->assertSame(2700.0, (float) $completed->net_payout_amount);
    }

    public function test_a_zero_configured_penalty_assesses_no_fines(): void
    {
        [$group, $cycle] = $this->makeGroupWithLateMembers(3, ['late_payment_penalty' => 0]);

        $completed = (new GroupService())->completeCycle($cycle->id);

        $this->assertSame(0, SaccoGroupPenalty::count());
        $this->assertSame(0.0, (float) $completed->penalties_deducted);
        $this->assertSame(3000.0, (float) $completed->net_payout_amount);
    }

    public function test_re_assessing_a_cycle_does_not_duplicate_fines(): void
    {
        [$group, $cycle] = $this->makeGroupWithLateMembers(2);

        $service = new GroupService();
        $first = $service->assessCyclePenalties($cycle);
        $second = $service->assessCyclePenalties($cycle);

        $this->assertSame(200.0, $first);
        $this->assertSame(200.0, $second);
        $this->assertSame(2, SaccoGroupPenalty::count());
    }

    public function test_exited_members_are_not_fined(): void
    {
        $group = $this->makeGroup();
        $active = $this->makeMembership($group, 'Ada', 'Lovelace');
        $exited = $this->makeMembership($group, 'Grace', 'Hopper', 'exited');

        $this->makeContribution($group, $active, ['contribution_date' => '2026-10-01']);

        $cycle = $this->makeCycle($group, ['beneficiary_client_id' => $active->client_id]);

        (new GroupService())->completeCycle($cycle->id);

        $this->assertSame(0, SaccoGroupPenalty::count());
        $this->assertSame(0, SaccoGroupPenalty::where('client_id', $exited->client_id)->count());
    }

    public function test_a_pending_contribution_does_not_count_as_paid(): void
    {
        // Only 'posted' money is real. A contribution still awaiting approval
        // must not shield a member from a missed-contribution fine.
        $group = $this->makeGroup();
        $member = $this->makeMembership($group, 'Ada', 'Lovelace');
        $this->makeContribution($group, $member, ['status' => 'pending']);

        $cycle = $this->makeCycle($group, ['beneficiary_client_id' => $member->client_id]);

        (new GroupService())->completeCycle($cycle->id);

        $this->assertSame(1, SaccoGroupPenalty::count());
        $this->assertSame('missed_contribution', SaccoGroupPenalty::first()->penalty_type);
    }

    // ── Cycle completion across rotation models ──────────────────────────────

    /**
     * Previously resolveBiddingCycle() was the only code in the module that ever
     * wrote status 'completed', so fixed/random groups could never close a turn.
     */
    public function test_a_fixed_rotation_cycle_can_be_completed(): void
    {
        [$group, $cycle] = $this->makeGroupWithLateMembers(0, ['rotation_model' => 'fixed']);

        $completed = (new GroupService())->completeCycle($cycle->id);

        $this->assertSame('completed', $completed->status);
        $this->assertSame(3000.0, (float) $completed->net_payout_amount);
    }

    public function test_a_random_rotation_cycle_can_be_completed(): void
    {
        [$group, $cycle] = $this->makeGroupWithLateMembers(0, ['rotation_model' => 'random']);

        $completed = (new GroupService())->completeCycle($cycle->id);

        $this->assertSame('completed', $completed->status);
    }

    public function test_completing_a_cycle_twice_throws(): void
    {
        [$group, $cycle] = $this->makeGroupWithLateMembers(0);

        $service = new GroupService();
        $service->completeCycle($cycle->id);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already been completed');

        $service->completeCycle($cycle->id);
    }

    public function test_a_skipped_cycle_cannot_be_completed(): void
    {
        [$group, $cycle] = $this->makeGroupWithLateMembers(0);
        $cycle->status = 'skipped';
        $cycle->save();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('skipped turn cannot be completed');

        (new GroupService())->completeCycle($cycle->id);
    }

    public function test_a_cycle_without_a_beneficiary_cannot_be_completed(): void
    {
        [$group, $cycle] = $this->makeGroupWithLateMembers(0);
        $cycle->beneficiary_client_id = null;
        $cycle->save();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('no beneficiary');

        (new GroupService())->completeCycle($cycle->id);
    }

    public function test_an_already_disbursed_cycle_cannot_be_reopened_by_completion(): void
    {
        [$group, $cycle] = $this->makeGroupWithLateMembers(0);
        $cycle->status = 'paid';
        $cycle->save();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already been completed');

        (new GroupService())->completeCycle($cycle->id);
    }

    // ── Bidding ──────────────────────────────────────────────────────────────

    protected function makeBiddingGroup(): array
    {
        $group = $this->makeGroup(['rotation_model' => 'bidding']);

        $members = [
            $this->makeMembership($group, 'Ada', 'Lovelace'),
            $this->makeMembership($group, 'Grace', 'Hopper'),
        ];

        foreach ($members as $member) {
            $this->makeContribution($group, $member, ['contribution_date' => '2026-10-01']);
        }

        $cycle = $this->makeCycle($group, [
            'beneficiary_client_id' => null,
            'pot_amount' => 2000,
        ]);

        return [$group, $cycle, $members];
    }

    public function test_bidding_resolution_picks_the_highest_bid_and_applies_the_discount(): void
    {
        [$group, $cycle, $members] = $this->makeBiddingGroup();

        SaccoRotationBid::create([
            'sacco_group_id' => $group->id, 'cycle_number' => 1,
            'client_id' => $members[0]->client_id, 'bid_amount' => 50, 'status' => 'pending',
        ]);
        SaccoRotationBid::create([
            'sacco_group_id' => $group->id, 'cycle_number' => 1,
            'client_id' => $members[1]->client_id, 'bid_amount' => 120, 'status' => 'pending',
        ]);

        $resolved = (new GroupService())->resolveBiddingCycle($cycle->id);

        $this->assertSame($members[1]->client_id, (int) $resolved->beneficiary_client_id);
        $this->assertSame(120.0, (float) $resolved->bidding_discount);
        $this->assertSame('completed', $resolved->status);
        // 2000 pot - 120 discount - 0 fines
        $this->assertSame(1880.0, (float) $resolved->net_payout_amount);

        $this->assertSame('won', SaccoRotationBid::where('client_id', $members[1]->client_id)->first()->status);
        $this->assertSame('lost', SaccoRotationBid::where('client_id', $members[0]->client_id)->first()->status);
    }

    /**
     * Re-running resolution used to find no pending bids, then set the
     * beneficiary to null and the discount to 0 — silently destroying the
     * recorded winner of an already-resolved cycle.
     */
    public function test_resolving_a_bidding_cycle_twice_throws_instead_of_wiping_the_winner(): void
    {
        [$group, $cycle, $members] = $this->makeBiddingGroup();

        SaccoRotationBid::create([
            'sacco_group_id' => $group->id, 'cycle_number' => 1,
            'client_id' => $members[0]->client_id, 'bid_amount' => 75, 'status' => 'pending',
        ]);

        $service = new GroupService();
        $first = $service->resolveBiddingCycle($cycle->id);
        $winnerId = (int) $first->beneficiary_client_id;

        try {
            $service->resolveBiddingCycle($cycle->id);
            $this->fail('Expected a second resolution to be rejected.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('already been resolved', $e->getMessage());
        }

        $reloaded = SaccoMerryGoRoundCycle::find($cycle->id);
        $this->assertSame($winnerId, (int) $reloaded->beneficiary_client_id);
        $this->assertSame(75.0, (float) $reloaded->bidding_discount);
    }

    public function test_resolving_a_bidding_cycle_with_no_pending_bids_throws(): void
    {
        [$group, $cycle, $members] = $this->makeBiddingGroup();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('no pending bids');

        (new GroupService())->resolveBiddingCycle($cycle->id);
    }

    public function test_bidding_resolution_also_assesses_late_payment_fines(): void
    {
        $group = $this->makeGroup(['rotation_model' => 'bidding']);

        $a = $this->makeMembership($group, 'Ada', 'Lovelace');
        $b = $this->makeMembership($group, 'Grace', 'Hopper');

        // Both pay late: cycle date is 2026-10-15.
        $this->makeContribution($group, $a, ['contribution_date' => '2026-10-20']);
        $this->makeContribution($group, $b, ['contribution_date' => '2026-10-18']);

        $cycle = $this->makeCycle($group, ['beneficiary_client_id' => null, 'pot_amount' => 2000]);

        SaccoRotationBid::create([
            'sacco_group_id' => $group->id, 'cycle_number' => 1,
            'client_id' => $a->client_id, 'bid_amount' => 100, 'status' => 'pending',
        ]);

        $resolved = (new GroupService())->resolveBiddingCycle($cycle->id);

        $this->assertSame(2, SaccoGroupPenalty::count());
        $this->assertSame(200.0, (float) $resolved->penalties_deducted);
        // 2000 pot - 100 discount - 200 fines
        $this->assertSame(1700.0, (float) $resolved->net_payout_amount);
    }

    // ── Waivers ──────────────────────────────────────────────────────────────

    public function test_waiving_a_fine_recomputes_the_cycle_deduction_and_payout(): void
    {
        [$group, $cycle] = $this->makeGroupWithLateMembers(2);

        $service = new GroupService();
        $completed = $service->completeCycle($cycle->id);

        $this->assertSame(200.0, (float) $completed->penalties_deducted);
        $this->assertSame(2800.0, (float) $completed->net_payout_amount);

        $service->waivePenalty(SaccoGroupPenalty::first(), 'Member was hospitalised', 7);

        $reloaded = SaccoMerryGoRoundCycle::find($cycle->id);
        $this->assertSame(100.0, (float) $reloaded->penalties_deducted);
        $this->assertSame(2900.0, (float) $reloaded->net_payout_amount);

        // The row is kept, not deleted, so the waiver stays on the record.
        $waived = SaccoGroupPenalty::where('status', 'waived')->first();
        $this->assertNotNull($waived);
        $this->assertSame(7, (int) $waived->waived_by_id);
        $this->assertNotNull($waived->waived_at);
        $this->assertStringContainsString('hospitalised', $waived->reason);
    }

    public function test_a_waived_fine_cannot_be_waived_twice(): void
    {
        [$group, $cycle] = $this->makeGroupWithLateMembers(1);

        $service = new GroupService();
        $service->completeCycle($cycle->id);

        $penalty = SaccoGroupPenalty::first();
        $service->waivePenalty($penalty, 'Goodwill', 7);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already been waived');

        $service->waivePenalty($penalty, 'Again', 7);
    }

    public function test_a_waived_fine_no_longer_counts_towards_the_deduction(): void
    {
        [$group, $cycle] = $this->makeGroupWithLateMembers(1);

        $service = new GroupService();
        $service->completeCycle($cycle->id);
        $service->waivePenalty(SaccoGroupPenalty::first(), 'Goodwill', 7);

        $reassessed = $service->assessCyclePenalties($cycle);

        $this->assertSame(0.0, $reassessed);
    }

    // ── Cash actually collected ──────────────────────────────────────────────

    public function test_collected_amount_records_posted_contributions_and_exposes_a_shortfall(): void
    {
        $group = $this->makeGroup();

        $a = $this->makeMembership($group, 'Ada', 'Lovelace');
        $b = $this->makeMembership($group, 'Grace', 'Hopper');
        $c = $this->makeMembership($group, 'Katherine', 'Johnson');

        // Pot is scheduled for 3 x 1000 = 3000, but only two members pay.
        $this->makeContribution($group, $a, ['contribution_date' => '2026-10-01']);
        $this->makeContribution($group, $b, ['contribution_date' => '2026-10-01']);

        $cycle = $this->makeCycle($group, ['beneficiary_client_id' => $a->client_id]);

        $completed = (new GroupService())->completeCycle($cycle->id);

        $this->assertSame(2000.0, (float) $completed->collected_amount);
        $this->assertSame(3000.0, (float) $completed->pot_amount);
        $this->assertLessThan((float) $completed->pot_amount, (float) $completed->collected_amount);
    }

    public function test_reversed_contributions_do_not_count_towards_collected_amount(): void
    {
        $group = $this->makeGroup();

        $a = $this->makeMembership($group, 'Ada', 'Lovelace');
        $b = $this->makeMembership($group, 'Grace', 'Hopper');

        $this->makeContribution($group, $a, ['contribution_date' => '2026-10-01']);
        $this->makeContribution($group, $b, ['contribution_date' => '2026-10-01', 'status' => 'reversed']);

        $cycle = $this->makeCycle($group, ['beneficiary_client_id' => $a->client_id]);

        $completed = (new GroupService())->completeCycle($cycle->id);

        $this->assertSame(1000.0, (float) $completed->collected_amount);
    }

    // ── Presentation ─────────────────────────────────────────────────────────

    public function test_penalty_type_label_is_human_readable(): void
    {
        [$group, $cycle] = $this->makeGroupWithLateMembers(1);

        (new GroupService())->completeCycle($cycle->id);

        $penalty = SaccoGroupPenalty::first();
        $this->assertSame('Late contribution', $penalty->type_label);

        // Assert the other label through a fresh instance rather than mutating
        // this one, so the test does not depend on get-mutator caching.
        $missed = new SaccoGroupPenalty(['penalty_type' => 'missed_contribution']);
        $this->assertSame('Missed contribution', $missed->type_label);
    }

    public function test_the_active_scope_excludes_waived_fines(): void
    {
        [$group, $cycle] = $this->makeGroupWithLateMembers(2);

        $service = new GroupService();
        $service->completeCycle($cycle->id);

        $this->assertSame(2, SaccoGroupPenalty::active()->count());

        $service->waivePenalty(SaccoGroupPenalty::first(), 'Goodwill', 7);

        $this->assertSame(1, SaccoGroupPenalty::active()->count());
        $this->assertSame(2, SaccoGroupPenalty::count());
    }
}
