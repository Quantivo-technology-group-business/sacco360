<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Modules\Sacco\Entities\SaccoGroup;
use Modules\Sacco\Entities\SaccoGroupContribution;
use Modules\Sacco\Entities\SaccoGroupMember;
use Modules\Sacco\Entities\SaccoMerryGoRoundCycle;
use Modules\Sacco\Services\GroupService;

/**
 * Covers the group-contribution approval queue and the rotation disbursement
 * guard.
 *
 * The status columns here are declared as real enums rather than plain strings,
 * mirroring production. The pre-existing rotation test used string columns,
 * which is why disburseCycle() writing the out-of-enum value 'paid' went
 * unnoticed: sqlite happily stored it, and so did MySQL in non-strict mode.
 */
class SaccoGroupContributionApprovalTest extends \TestCase
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

        Schema::create('clients', function ($table) {
            $table->id();
            $table->string('first_name');
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
            $table->text('notes')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('phone_number')->nullable();
            $table->unsignedBigInteger('approved_by_id')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->string('checkout_request_id')->nullable();
            $table->string('merchant_request_id')->nullable();
            $table->string('mpesa_receipt_number')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_merry_go_round_cycles', function ($table) {
            $table->id();
            $table->unsignedBigInteger('sacco_group_id');
            $table->integer('cycle_number');
            $table->unsignedBigInteger('beneficiary_client_id')->nullable();
            $table->decimal('pot_amount', 10, 2)->default(0);
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
    }

    protected function makeGroup(array $overrides = []): SaccoGroup
    {
        return SaccoGroup::create(array_merge([
            'name' => 'Test Chama',
            'type' => 'chama',
            'monthly_contribution' => 1000,
            'rotation_model' => 'fixed',
        ], $overrides));
    }

    protected function makeMembership(SaccoGroup $group, string $first = 'Ada', string $last = 'Lovelace'): SaccoGroupMember
    {
        $clientId = \DB::table('clients')->insertGetId([
            'first_name' => $first,
            'last_name' => $last,
        ]);

        return SaccoGroupMember::create([
            'sacco_group_id' => $group->id,
            'client_id' => $clientId,
            'role' => 'member',
            'joined_date' => '2024-01-10',
            'status' => 'active',
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
            'status' => 'pending',
            'payment_method' => 'cash',
        ], $overrides));
    }

    // ── Approval ─────────────────────────────────────────────────────────────

    public function test_approving_a_pending_contribution_posts_it_and_records_the_approver(): void
    {
        $group = $this->makeGroup();
        $member = $this->makeMembership($group);
        $contribution = $this->makeContribution($group, $member);

        $service = new GroupService();
        $result = $service->approveContribution($contribution, 42);

        $this->assertSame('posted', $result->status);
        $this->assertSame(42, (int) $result->approved_by_id);
        $this->assertNotNull($result->approved_at);
    }

    public function test_a_posted_contribution_cannot_be_approved_twice(): void
    {
        $group = $this->makeGroup();
        $member = $this->makeMembership($group);
        $contribution = $this->makeContribution($group, $member, ['status' => 'posted']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already been approved');

        (new GroupService())->approveContribution($contribution, 42);
    }

    public function test_only_pending_contributions_can_be_rejected(): void
    {
        $group = $this->makeGroup();
        $member = $this->makeMembership($group);
        $posted = $this->makeContribution($group, $member, ['status' => 'posted']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only a pending contribution can be rejected');

        (new GroupService())->rejectContribution($posted, 'duplicate', 42);
    }

    public function test_rejecting_records_the_reason(): void
    {
        $group = $this->makeGroup();
        $member = $this->makeMembership($group);
        $contribution = $this->makeContribution($group, $member);

        $result = (new GroupService())->rejectContribution($contribution, 'No matching receipt', 7);

        $this->assertSame('rejected', $result->status);
        $this->assertSame('No matching receipt', $result->rejection_reason);
    }

    public function test_a_pending_contribution_must_be_rejected_not_reversed(): void
    {
        $group = $this->makeGroup();
        $member = $this->makeMembership($group);
        $contribution = $this->makeContribution($group, $member);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('should be rejected, not reversed');

        (new GroupService())->reverseContribution($contribution, 'wrong', 42);
    }

    public function test_reversing_a_posted_contribution_excludes_it_from_member_totals(): void
    {
        $group = $this->makeGroup();
        $member = $this->makeMembership($group);
        $contribution = $this->makeContribution($group, $member, ['status' => 'posted']);

        $service = new GroupService();
        $this->assertSame(1000.0, $service->getMemberTotalContributions($member->client_id));

        $service->reverseContribution($contribution, 'Bounced cheque', 42);

        $this->assertSame('reversed', $contribution->fresh()->status);
        $this->assertSame(0.0, $service->getMemberTotalContributions($member->client_id));
    }

    public function test_a_reversed_contribution_cannot_be_reversed_twice(): void
    {
        $group = $this->makeGroup();
        $member = $this->makeMembership($group);
        $contribution = $this->makeContribution($group, $member, ['status' => 'reversed']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already been reversed');

        (new GroupService())->reverseContribution($contribution, 'again', 42);
    }

    // ── M-Pesa STK callback ──────────────────────────────────────────────────

    public function test_mpesa_callback_confirms_the_matching_pending_contribution(): void
    {
        $group = $this->makeGroup();
        $member = $this->makeMembership($group);
        $contribution = $this->makeContribution($group, $member, [
            'payment_method' => 'mpesa',
            'checkout_request_id' => 'ws_CO_1234567890',
        ]);

        $result = (new GroupService())->confirmMpesaContribution('ws_CO_1234567890', 'QGH81LK2MN', 1000.00);

        $this->assertNotNull($result);
        $this->assertSame('posted', $result->status);
        $this->assertSame('QGH81LK2MN', $result->mpesa_receipt_number);
        $this->assertSame($contribution->id, $result->id);
    }

    public function test_mpesa_callback_is_idempotent_on_a_repeat_delivery(): void
    {
        $group = $this->makeGroup();
        $member = $this->makeMembership($group);
        $this->makeContribution($group, $member, [
            'payment_method' => 'mpesa',
            'checkout_request_id' => 'ws_CO_REPEAT',
        ]);

        $service = new GroupService();
        $first = $service->confirmMpesaContribution('ws_CO_REPEAT', 'QGH0001', 1000.00);
        $second = $service->confirmMpesaContribution('ws_CO_REPEAT', 'QGH0001', 1000.00);

        $this->assertNotNull($first);
        $this->assertNull($second, 'A retried callback must not post a second time');
        $this->assertSame(1, SaccoGroupContribution::where('status', 'posted')->count());
    }

    public function test_mpesa_callback_with_an_unknown_checkout_id_is_a_no_op(): void
    {
        $this->assertNull((new GroupService())->confirmMpesaContribution('ws_CO_UNKNOWN', 'QGH9999', 500.00));
    }

    public function test_mpesa_amount_mismatch_leaves_the_contribution_for_manual_approval(): void
    {
        $group = $this->makeGroup();
        $member = $this->makeMembership($group);
        $this->makeContribution($group, $member, [
            'amount' => 1000,
            'payment_method' => 'mpesa',
            'checkout_request_id' => 'ws_CO_MISMATCH',
        ]);

        $result = (new GroupService())->confirmMpesaContribution('ws_CO_MISMATCH', 'QGH2222', 750.00);

        $this->assertSame('pending', $result->status, 'A short payment must not auto-post');
        $this->assertSame('QGH2222', $result->mpesa_receipt_number);
        $this->assertStringContainsString('mismatch', $result->rejection_reason);
    }

    // ── Rotation disbursement guard ──────────────────────────────────────────

    public function test_disbursing_a_cycle_marks_it_paid_and_records_the_reference(): void
    {
        $group = $this->makeGroup();
        $member = $this->makeMembership($group);

        $cycle = SaccoMerryGoRoundCycle::create([
            'sacco_group_id' => $group->id,
            'cycle_number' => 1,
            'beneficiary_client_id' => $member->client_id,
            'pot_amount' => 5000,
            'cycle_date' => '2026-10-01',
            'status' => 'completed',
        ]);

        $result = (new GroupService())->disburseCycle($cycle->id, 'MPESA-QGH777');

        $this->assertSame('paid', $result->status);
        $this->assertNotNull($result->disbursed_at);
        $this->assertSame('MPESA-QGH777', $result->disbursement_reference);
        $this->assertSame(5000.0, (float) $result->net_payout_amount);
    }

    public function test_a_cycle_cannot_be_disbursed_twice(): void
    {
        // Regression: status 'paid' was not a legal enum value, so MySQL stored
        // '' and this guard never fired — a pot could be paid out repeatedly.
        $group = $this->makeGroup();
        $member = $this->makeMembership($group);

        $cycle = SaccoMerryGoRoundCycle::create([
            'sacco_group_id' => $group->id,
            'cycle_number' => 1,
            'beneficiary_client_id' => $member->client_id,
            'pot_amount' => 5000,
            'cycle_date' => '2026-10-01',
            'status' => 'completed',
        ]);

        $service = new GroupService();
        $service->disburseCycle($cycle->id, 'FIRST');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already been disbursed');

        $service->disburseCycle($cycle->id, 'SECOND');
    }

    public function test_disbursed_cycle_appears_in_member_past_cycles(): void
    {
        // The portal filters past cycles on whereIn('status', ['completed','paid']).
        // Before the enum fix a disbursed cycle stored '' and never appeared.
        $group = $this->makeGroup(['type' => 'merry_go_round']);
        $member = $this->makeMembership($group);

        $cycle = SaccoMerryGoRoundCycle::create([
            'sacco_group_id' => $group->id,
            'cycle_number' => 1,
            'beneficiary_client_id' => $member->client_id,
            'pot_amount' => 5000,
            'cycle_date' => '2026-10-01',
            'status' => 'completed',
        ]);

        (new GroupService())->disburseCycle($cycle->id, 'REF-1');

        $past = SaccoMerryGoRoundCycle::where('sacco_group_id', $group->id)
            ->whereIn('status', ['completed', 'paid'])
            ->where('beneficiary_client_id', $member->client_id)
            ->get();

        $this->assertCount(1, $past, 'A disbursed cycle must show in the member payout history');
        $this->assertSame('paid', $past->first()->status);
    }
}
