<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class SaccoCrbCheck extends Model
{
    protected $table = 'sacco_crb_checks';

    protected $fillable = [
        'client_id', 'checked_by_id', 'id_number', 'provider',
        'status', 'check_type',
        'credit_score', 'score_band', 'is_listed', 'negative_listings', 'npl_count',
        'summary', 'recommendation',
        'raw_request', 'raw_response',
        'reference_number', 'consent_reference', 'consent_obtained', 'consent_date',
        'valid_until', 'error_message',
    ];

    protected $casts = [
        'is_listed'       => 'boolean',
        'consent_obtained'=> 'boolean',
        'consent_date'    => 'datetime',
        'valid_until'     => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function checkedBy()
    {
        return $this->belongsTo(\Modules\User\Entities\User::class, 'checked_by_id');
    }

    public function isValid(): bool
    {
        return $this->valid_until && $this->valid_until->isFuture();
    }

    public function getScoreBadgeClassAttribute(): string
    {
        if (in_array($this->status, ['error', 'pending'])) {
            return 'badge-secondary';
        }
        if ($this->status === 'simulated') {
            return 'badge-info';
        }
        if ($this->is_listed || $this->negative_listings > 0) {
            return 'badge-danger';
        }
        $score = $this->credit_score;
        if ($score === null) {
            return 'badge-secondary';
        }
        if ($score >= 700) return 'badge-success';
        if ($score >= 550) return 'badge-warning';
        return 'badge-danger';
    }

    public function getRecommendationBadgeAttribute(): string
    {
        return match ($this->recommendation) {
            'approve' => '<span class="badge badge-success">Approve</span>',
            'review'  => '<span class="badge badge-warning">Review</span>',
            'decline' => '<span class="badge badge-danger">Decline</span>',
            default   => '<span class="badge badge-secondary">—</span>',
        };
    }
}
