<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;
use Modules\User\Entities\User;

class SaccoAgentMemberRegistration extends Model
{
    protected $table = 'sacco_agent_member_registrations';

    protected $fillable = [
        'sacco_agent_id', 'client_id', 'registration_fee', 'commission_earned',
        'status', 'approved_by_id', 'approved_at', 'notes',
    ];

    protected $casts = [
        'registration_fee' => 'decimal:2',
        'commission_earned' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected $appends = ['status_badge'];

    public function agent()
    {
        return $this->belongsTo(SaccoAgent::class, 'sacco_agent_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
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

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => '<span class="badge badge-warning">Pending Approval</span>',
            'approved' => '<span class="badge badge-success">Approved</span>',
            'rejected' => '<span class="badge badge-danger">Rejected</span>',
            default => '<span class="badge badge-secondary">' . ucfirst($this->status) . '</span>',
        };
    }

    public function getClientName(): string
    {
        return $this->client 
            ? ($this->client->first_name . ' ' . $this->client->last_name)
            : 'N/A';
    }
}
