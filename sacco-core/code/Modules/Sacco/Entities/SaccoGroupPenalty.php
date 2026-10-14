<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

/**
 * A per-member fine raised against a chama rotation cycle.
 *
 * Exists so that `sacco_groups.late_payment_penalty` produces an auditable,
 * attributable record instead of a flat deduction from the pot.
 */
class SaccoGroupPenalty extends Model
{
    protected $table = 'sacco_group_penalties';

    protected $fillable = [
        'sacco_group_id', 'sacco_group_member_id', 'client_id',
        'sacco_merry_go_round_cycle_id', 'penalty_type', 'period',
        'amount', 'status', 'reason',
        'assessed_by_id', 'waived_by_id', 'waived_at',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'waived_at' => 'datetime',
    ];

    /**
     * Fines that still count against the member.
     *
     * `collected` is included because the money was owed and has been accounted
     * for; `waived` is excluded because an officer cancelled it.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['assessed', 'collected']);
    }

    public function group()
    {
        return $this->belongsTo(SaccoGroup::class, 'sacco_group_id');
    }

    public function member()
    {
        return $this->belongsTo(SaccoGroupMember::class, 'sacco_group_member_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function cycle()
    {
        return $this->belongsTo(SaccoMerryGoRoundCycle::class, 'sacco_merry_go_round_cycle_id');
    }

    /**
     * Human-readable cause, for the register and member statements.
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->penalty_type === 'missed_contribution'
            ? 'Missed contribution'
            : 'Late contribution';
    }
}
