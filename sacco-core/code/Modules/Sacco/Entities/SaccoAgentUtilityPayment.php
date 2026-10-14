<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class SaccoAgentUtilityPayment extends Model
{
    protected $table = 'sacco_agent_utility_payments';

    protected $fillable = [
        'sacco_agent_id', 'client_id', 'provider_id', 'account_number',
        'customer_name', 'amount', 'fee', 'commission', 'reference',
        'provider_reference', 'status', 'response_data', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'commission' => 'decimal:2',
    ];

    protected $appends = ['status_badge', 'total_amount'];

    public function agent()
    {
        return $this->belongsTo(SaccoAgent::class, 'sacco_agent_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function provider()
    {
        return $this->belongsTo(SaccoUtilityProvider::class, 'provider_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    public function scopeForAgent($query, $agentId)
    {
        return $query->where('sacco_agent_id', $agentId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => '<span class="badge badge-warning"><i class="fas fa-clock"></i> Pending</span>',
            'processing' => '<span class="badge badge-info"><i class="fas fa-spinner fa-spin"></i> Processing</span>',
            'completed' => '<span class="badge badge-success"><i class="fas fa-check"></i> Completed</span>',
            'failed' => '<span class="badge badge-danger"><i class="fas fa-times"></i> Failed</span>',
            default => '<span class="badge badge-secondary">' . ucfirst($this->status) . '</span>',
        };
    }

    public function getTotalAmountAttribute(): float
    {
        return (float)$this->amount + (float)$this->fee;
    }

    public function getProviderName(): string
    {
        return $this->provider?->name ?? 'Unknown Provider';
    }
}
