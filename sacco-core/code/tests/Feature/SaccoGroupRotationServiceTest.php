<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Modules\Sacco\Entities\SaccoGroup;
use Modules\Sacco\Entities\SaccoGroupMember;
use Modules\Sacco\Entities\SaccoMerryGoRoundCycle;
use Modules\Sacco\Services\GroupService;

class SaccoGroupRotationServiceTest extends \TestCase
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

        Schema::create('sacco_merry_go_round_cycles', function ($table) {
            $table->id();
            $table->unsignedBigInteger('sacco_group_id');
            $table->integer('cycle_number');
            $table->unsignedBigInteger('beneficiary_client_id')->nullable();
            $table->decimal('pot_amount', 10, 2)->default(0);
            $table->date('cycle_date');
            $table->string('status')->default('upcoming');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('bidding_discount', 10, 2)->default(0);
            $table->decimal('penalties_deducted', 10, 2)->default(0);
            $table->decimal('net_payout_amount', 10, 2)->default(0);
            $table->dateTime('disbursed_at')->nullable();
            $table->string('disbursement_reference')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_rotation_bids', function ($table) {
            $table->id();
            $table->unsignedBigInteger('sacco_group_id');
            $table->integer('cycle_number');
            $table->unsignedBigInteger('client_id');
            $table->decimal('bid_amount', 10, 2);
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function test_generate_schedule_uses_join_date_order_for_fixed_rotation(): void
    {
        $group = SaccoGroup::create([
            'name' => 'Test Chama',
            'type' => 'merry_go_round',
            'monthly_contribution' => 100,
            'rotation_model' => 'fixed',
        ]);

        $clientOne = \DB::table('clients')->insertGetId(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $clientTwo = \DB::table('clients')->insertGetId(['first_name' => 'Grace', 'last_name' => 'Hopper']);
        $clientThree = \DB::table('clients')->insertGetId(['first_name' => 'Katherine', 'last_name' => 'Johnson']);

        SaccoGroupMember::create(['sacco_group_id' => $group->id, 'client_id' => $clientOne, 'role' => 'member', 'joined_date' => '2024-01-10', 'status' => 'active']);
        SaccoGroupMember::create(['sacco_group_id' => $group->id, 'client_id' => $clientTwo, 'role' => 'member', 'joined_date' => '2024-01-05', 'status' => 'active']);
        SaccoGroupMember::create(['sacco_group_id' => $group->id, 'client_id' => $clientThree, 'role' => 'member', 'joined_date' => '2024-01-15', 'status' => 'active']);

        $service = new GroupService();
        $service->generateMerryGoRoundSchedule($group, Carbon::parse('2024-02-01'), 1);

        $cycles = SaccoMerryGoRoundCycle::where('sacco_group_id', $group->id)->orderBy('cycle_number')->get();

        $this->assertCount(3, $cycles);
        $this->assertSame($clientTwo, $cycles[0]->beneficiary_client_id);
        $this->assertSame($clientOne, $cycles[1]->beneficiary_client_id);
        $this->assertSame($clientThree, $cycles[2]->beneficiary_client_id);
    }
}
