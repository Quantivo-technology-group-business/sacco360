<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Per-member late-payment fines for chama groups.
 *
 * `sacco_groups.late_payment_penalty` existed from the start but was only ever
 * read once, in GroupService::resolveBiddingCycle(), where it was deducted from
 * the pot as a single flat amount every cycle — regardless of how many members
 * were late, whether anyone was, or who they were. Under the `fixed` and
 * `random` rotation models it was never applied at all, because no code path
 * other than the bidding one ever completed a cycle.
 *
 * That is doubly wrong: the beneficiary is underpaid when nobody was late, and
 * the group is underpaid when several members were. It is also unauditable —
 * money left the pot with no record of which member caused it.
 *
 * This table records one row per member per cycle, so the deduction is derived
 * from evidence and every cent is attributable. The unique index on
 * (cycle, member) makes double-assessment impossible at the database level, not
 * merely by convention in the service.
 */
class CreateSaccoGroupPenalties extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('sacco_group_penalties')) {
            Schema::create('sacco_group_penalties', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('sacco_group_id')->unsigned();
                $table->bigInteger('sacco_group_member_id')->unsigned();
                $table->bigInteger('client_id')->unsigned();

                // Nullable so this table can later hold manually-raised fines
                // that are not tied to a rotation cycle.
                $table->bigInteger('sacco_merry_go_round_cycle_id')->unsigned()->nullable()
                      ->comment('Cycle this fine was assessed against');

                $table->enum('penalty_type', ['late_contribution', 'missed_contribution'])
                      ->default('late_contribution');
                $table->string('period', 7)->nullable()->comment('Contribution period, e.g. 2026-06');
                $table->decimal('amount', 65, 6);
                $table->enum('status', ['assessed', 'waived', 'collected'])->default('assessed');
                $table->text('reason')->nullable();

                $table->bigInteger('assessed_by_id')->unsigned()->nullable();
                $table->bigInteger('waived_by_id')->unsigned()->nullable();
                $table->dateTime('waived_at')->nullable();

                $table->timestamps();

                // Idempotency guard: one fine per member per cycle.
                $table->unique(['sacco_merry_go_round_cycle_id', 'sacco_group_member_id'],
                    'uq_group_penalties_cycle_member');
                $table->index(['sacco_group_id', 'status'], 'idx_group_penalties_group_status');
                $table->index('client_id', 'idx_group_penalties_client');
            });
        }

        // pot_amount is theoretical: monthly_contribution * member count, written
        // when the schedule is built. It says nothing about whether the cash
        // actually arrived, and nothing in the codebase compares the two.
        //
        // This column records what was really posted for the cycle's period so
        // the shortfall is visible and auditable. It is deliberately NOT fed
        // into net_payout_amount yet: whether a payout should be based on
        // scheduled pot or on cash collected is a business decision for the
        // SACCO, not something to change silently alongside a bug fix. See
        // FEATURE_GAP_ANALYSIS.md Part G10 for the open question.
        Schema::table('sacco_merry_go_round_cycles', function (Blueprint $table) {
            if (!Schema::hasColumn('sacco_merry_go_round_cycles', 'collected_amount')) {
                $table->decimal('collected_amount', 65, 6)->nullable()->after('pot_amount')
                      ->comment('Posted contributions for this cycle period; audit only, null until completed');
            }
        });
    }

    public function down()
    {
        Schema::table('sacco_merry_go_round_cycles', function (Blueprint $table) {
            if (Schema::hasColumn('sacco_merry_go_round_cycles', 'collected_amount')) {
                $table->dropColumn('collected_amount');
            }
        });

        Schema::dropIfExists('sacco_group_penalties');
    }
}
