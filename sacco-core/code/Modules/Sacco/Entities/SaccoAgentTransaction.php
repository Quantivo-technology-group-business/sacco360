<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;
use Modules\Loan\Entities\Loan;
use Modules\Savings\Entities\Savings;

class SaccoAgentTransaction extends Model
{
    protected $fillable = [
        'sacco_agent_id', 'client_id', 'savings_id', 'loan_id',
        'utility_payment_id', 'member_registration_id', 'transaction_type',
        'amount', 'float_before', 'float_after', 'commission', 'commission_rate',
        'reference', 'status', 'notes', 'reversed_at', 'reversed_by_id', 'reversal_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'float_before' => 'decimal:2',
        'float_after' => 'decimal:2',
        'commission' => 'decimal:2',
        'commission_rate' => 'decimal:4',
        'reversed_at' => 'datetime',
    ];

    protected $appends = ['type_badge', 'status_badge'];

    // ─── Relationships ──────────────────────────────────────────────────────

    public function agent()
    {
        return $this->belongsTo(SaccoAgent::class, 'sacco_agent_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function savings()
    {
        return $this->belongsTo(Savings::class);
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function utilityPayment()
    {
        return $this->belongsTo(SaccoAgentUtilityPayment::class, 'utility_payment_id');
    }

    public function memberRegistration()
    {
        return $this->belongsTo(SaccoAgentMemberRegistration::class, 'member_registration_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeDeposits($query)
    {
        return $query->where('transaction_type', 'deposit');
    }

    public function scopeWithdrawals($query)
    {
        return $query->where('transaction_type', 'withdrawal');
    }

    public function scopeFloatTopups($query)
    {
        return $query->where('transaction_type', 'float_topup');
    }

    public function scopeLoanRepayments($query)
    {
        return $query->where('transaction_type', 'loan_repayment');
    }

    public function scopeUtilities($query)
    {
        return $query->where('transaction_type', 'utility');
    }

    public function scopeRegistrations($query)
    {
        return $query->where('transaction_type', 'registration');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeReversed($query)
    {
        return $query->where('status', 'reversed');
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

    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    public function scopeForAgent($query, $agentId)
    {
        return $query->where('sacco_agent_id', $agentId);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('reference', 'like', "%{$term}%")
              ->orWhereHas('agent', function ($q2) use ($term) {
                  $q2->where('agent_name', 'like', "%{$term}%")
                     ->orWhere('agent_code', 'like', "%{$term}%");
              })
              ->orWhereHas('client', function ($q2) use ($term) {
                  $q2->where('first_name', 'like', "%{$term}%")
                     ->orWhere('last_name', 'like', "%{$term}%");
              });
        });
    }

    // ─── Computed Attributes ────────────────────────────────────────────────

    public function getTypeBadgeAttribute(): string
    {
        return match ($this->transaction_type) {
            'deposit' => '<span class="badge badge-success"><i class="fas fa-arrow-down"></i> Deposit</span>',
            'withdrawal' => '<span class="badge badge-warning"><i class="fas fa-arrow-up"></i> Withdrawal</span>',
            'float_topup' => '<span class="badge badge-info"><i class="fas fa-plus"></i> Float Top-up</span>',
            'commission' => '<span class="badge badge-primary"><i class="fas fa-percentage"></i> Commission</span>',
            'loan_repayment' => '<span class="badge badge-info"><i class="fas fa-hand-holding-usd"></i> Loan Repayment</span>',
            'utility' => '<span class="badge badge-primary"><i class="fas fa-bolt"></i> Utility</span>',
            'registration' => '<span class="badge badge-success"><i class="fas fa-user-plus"></i> Registration</span>',
            default => '<span class="badge badge-secondary">' . ucfirst(str_replace('_', ' ', $this->transaction_type)) . '</span>',
        };
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'completed' => '<span class="badge badge-success">Completed</span>',
            'pending' => '<span class="badge badge-warning">Pending</span>',
            'failed' => '<span class="badge badge-danger">Failed</span>',
            'reversed' => '<span class="badge badge-secondary">Reversed</span>',
            default => '<span class="badge badge-secondary">' . ucfirst($this->status) . '</span>',
        };
    }

    public function getFormattedTypeAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->transaction_type));
    }

    // ─── Helper Methods ─────────────────────────────────────────────────────

    public function isReversible(): bool
    {
        return $this->status === 'completed'
            && in_array($this->transaction_type, ['deposit', 'withdrawal'])
            && $this->created_at->diffInHours(now()) < 24;
    }

    public function getClientName(): string
    {
        return $this->client
            ? ($this->client->first_name . ' ' . $this->client->last_name)
            : 'N/A';
    }

    public function getAgentName(): string
    {
        return $this->agent?->agent_name ?? 'Unknown';
    }

    public function getAgentCode(): string
    {
        return $this->agent?->agent_code ?? 'N/A';
    }
}
