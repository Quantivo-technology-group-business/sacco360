<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\User\Entities\User;

class SaccoAgentKycDocument extends Model
{
    protected $fillable = [
        'sacco_agent_id',
        'uploaded_by_id',
        'reviewed_by_id',
        'document_type',
        'document_number',
        'file_path',
        'status',
        'rejection_reason',
        'expiry_date',
        'reviewed_at',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    protected $appends = ['status_badge', 'document_type_label'];

    public function agent()
    {
        return $this->belongsTo(SaccoAgent::class, 'sacco_agent_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function requirement()
    {
        return SaccoAgentKycRequirement::where('code', $this->document_type)->first();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('document_type', $type);
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'pending' => '<span class="badge badge-warning">Pending</span>',
            'approved' => '<span class="badge badge-success">Approved</span>',
            'rejected' => '<span class="badge badge-danger">Rejected</span>',
            'expired' => '<span class="badge badge-secondary">Expired</span>',
            default => '<span class="badge badge-secondary">' . ucfirst($this->status) . '</span>',
        };
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        $requirement = $this->requirement();
        return $requirement ? $requirement->name : ucwords(str_replace('_', ' ', $this->document_type));
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }
}
