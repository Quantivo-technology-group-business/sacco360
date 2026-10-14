<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoMpesaReconciliation extends Model
{
    protected $fillable = [
        'created_by_id', 'reviewed_by_id', 'approved_by_id',
        'reference', 'provider', 'short_code', 'account_type',
        'period_from', 'period_to',
        'statement_total_in', 'statement_total_out',
        'statement_count_in', 'statement_count_out',
        'system_total_in', 'system_total_out',
        'system_count_in', 'system_count_out',
        'matched_count', 'unmatched_statement', 'unmatched_system',
        'discrepancy_count', 'net_variance',
        'status', 'statement_file_path', 'statement_file_name',
        'notes', 'approved_at',
    ];

    protected $dates = ['period_from', 'period_to', 'approved_at'];

    public function items()
    {
        return $this->hasMany(SaccoMpesaReconciliationItem::class);
    }

    public function matchedItems()
    {
        return $this->hasMany(SaccoMpesaReconciliationItem::class)
            ->whereIn('match_status', ['matched', 'matched_with_variance', 'manually_matched']);
    }

    public function unmatchedItems()
    {
        return $this->hasMany(SaccoMpesaReconciliationItem::class)
            ->where('match_status', 'unmatched');
    }

    public function exceptionItems()
    {
        return $this->hasMany(SaccoMpesaReconciliationItem::class)
            ->where('is_exception', 1);
    }

    public function auditLogs()
    {
        return $this->hasMany(SaccoMpesaReconciliationAuditLog::class)
            ->orderBy('created_at');
    }

    public function getMatchRateAttribute(): float
    {
        $total = $this->items()->count();
        if ($total === 0) return 0;
        return round(($this->matched_count / $total) * 100, 1);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'pending_match', 'matched', 'under_review']);
    }
}
