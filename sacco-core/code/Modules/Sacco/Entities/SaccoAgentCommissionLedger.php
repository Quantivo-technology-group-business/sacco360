<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\User\Entities\User;

class SaccoAgentCommissionLedger extends Model
{
    protected $table = 'sacco_agent_commission_ledger';

    protected $fillable = [
        'sacco_agent_id', 'transaction_id', 'type', 'amount',
        'balance_before', 'balance_after', 'description', 'reference', 'created_by_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    protected $appends = ['type_badge'];

    public function agent()
    {
        return $this->belongsTo(SaccoAgent::class, 'sacco_agent_id');
    }

    public function transaction()
    {
        return $this->belongsTo(SaccoAgentTransaction::class, 'transaction_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function scopeEarned($query)
    {
        return $query->where('type', 'earned');
    }

    public function scopeWithdrawn($query)
    {
        return $query->where('type', 'withdrawn');
    }

    public function scopeForAgent($query, $agentId)
    {
        return $query->where('sacco_agent_id', $agentId);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function getTypeBadgeAttribute(): string
    {
        return match ($this->type) {
            'earned' => '<span class="badge badge-success"><i class="fas fa-plus"></i> Earned</span>',
            'withdrawn' => '<span class="badge badge-warning"><i class="fas fa-minus"></i> Withdrawn</span>',
            'adjustment' => '<span class="badge badge-info"><i class="fas fa-edit"></i> Adjustment</span>',
            'bonus' => '<span class="badge badge-primary"><i class="fas fa-gift"></i> Bonus</span>',
            default => '<span class="badge badge-secondary">' . ucfirst($this->type) . '</span>',
        };
    }
}
