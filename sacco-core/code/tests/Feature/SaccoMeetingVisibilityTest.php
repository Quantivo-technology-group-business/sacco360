<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Modules\Sacco\Entities\SaccoGroup;
use Modules\Sacco\Entities\SaccoGroupMember;
use Modules\Sacco\Entities\SaccoMeeting;

/**
 * Covers the member-portal meeting visibility scope.
 *
 * Portal\ChamaController::meetings() previously returned every SaccoMeeting with
 * no filter, so any member could read board, credit-committee and
 * supervisory-committee minutes. sacco_meetings had no group column at all,
 * which made filtering impossible.
 */
class SaccoMeetingVisibilityTest extends \TestCase
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
            $table->string('type')->default('chama');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('sacco_group_members', function ($table) {
            $table->id();
            $table->unsignedBigInteger('sacco_group_id');
            $table->unsignedBigInteger('client_id');
            $table->string('role')->default('member');
            $table->date('joined_date');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('sacco_meetings', function ($table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('sacco_group_id')->nullable();
            $table->string('title');
            $table->enum('meeting_type', ['agm', 'sgm', 'board', 'credit_committee', 'supervisory', 'management', 'other'])->default('board');
            $table->date('scheduled_date');
            $table->text('minutes')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();
        });
    }

    protected function makeClient(string $first = 'Ada'): int
    {
        return \DB::table('clients')->insertGetId(['first_name' => $first, 'last_name' => 'Lovelace']);
    }

    protected function makeMeeting(string $title, string $type, ?int $groupId = null): SaccoMeeting
    {
        return SaccoMeeting::create([
            'title' => $title,
            'meeting_type' => $type,
            'scheduled_date' => '2026-11-01',
            'sacco_group_id' => $groupId,
            'minutes' => 'Confidential minutes for ' . $title,
            'status' => 'scheduled',
        ]);
    }

    public function test_a_member_sees_meetings_of_their_own_group(): void
    {
        $group = SaccoGroup::create(['name' => 'Chama A', 'type' => 'chama']);
        $clientId = $this->makeClient();

        SaccoGroupMember::create([
            'sacco_group_id' => $group->id,
            'client_id' => $clientId,
            'role' => 'member',
            'joined_date' => '2024-01-10',
            'status' => 'active',
        ]);

        $own = $this->makeMeeting('Chama A monthly', 'other', $group->id);
        $this->makeMeeting('Chama B monthly', 'other', 999);

        $visible = SaccoMeeting::visibleToMember($clientId)->pluck('id');

        $this->assertTrue($visible->contains($own->id));
        $this->assertCount(1, $visible, 'Only the member’s own group meeting should be visible');
    }

    public function test_board_and_committee_minutes_are_never_visible_to_members(): void
    {
        $clientId = $this->makeClient();

        $this->makeMeeting('Board Meeting Q4', 'board');
        $this->makeMeeting('Credit Committee', 'credit_committee');
        $this->makeMeeting('Supervisory Committee', 'supervisory');
        $this->makeMeeting('Management Meeting', 'management');

        $this->assertCount(0, SaccoMeeting::visibleToMember($clientId),
            'SACCO-wide governance meetings must not reach the member portal');
    }

    public function test_sacco_wide_general_meetings_are_visible_to_all_members(): void
    {
        $clientId = $this->makeClient();

        $agm = $this->makeMeeting('Annual General Meeting 2026', 'agm');
        $sgm = $this->makeMeeting('Special General Meeting', 'sgm');
        $this->makeMeeting('Board Meeting Q4', 'board');

        $visible = SaccoMeeting::visibleToMember($clientId)->pluck('id');

        $this->assertTrue($visible->contains($agm->id));
        $this->assertTrue($visible->contains($sgm->id));
        $this->assertCount(2, $visible);
    }

    public function test_an_exited_member_loses_access_to_their_former_group_meetings(): void
    {
        $group = SaccoGroup::create(['name' => 'Chama A', 'type' => 'chama']);
        $clientId = $this->makeClient();

        SaccoGroupMember::create([
            'sacco_group_id' => $group->id,
            'client_id' => $clientId,
            'role' => 'member',
            'joined_date' => '2024-01-10',
            'status' => 'exited',
        ]);

        $this->makeMeeting('Chama A monthly', 'other', $group->id);

        $this->assertCount(0, SaccoMeeting::visibleToMember($clientId),
            'Only active memberships confer visibility');
    }

    public function test_a_group_scoped_governance_meeting_stays_private_to_its_members(): void
    {
        // A committee meeting scoped to one group must be visible to that
        // group's members but to nobody else.
        $group = SaccoGroup::create(['name' => 'Chama A', 'type' => 'chama']);
        $insider = $this->makeClient('Ada');
        $outsider = $this->makeClient('Grace');

        SaccoGroupMember::create([
            'sacco_group_id' => $group->id,
            'client_id' => $insider,
            'role' => 'treasurer',
            'joined_date' => '2024-01-10',
            'status' => 'active',
        ]);

        $meeting = $this->makeMeeting('Chama A committee', 'credit_committee', $group->id);

        $this->assertTrue(SaccoMeeting::visibleToMember($insider)->pluck('id')->contains($meeting->id));
        $this->assertFalse(SaccoMeeting::visibleToMember($outsider)->pluck('id')->contains($meeting->id));
    }
}
