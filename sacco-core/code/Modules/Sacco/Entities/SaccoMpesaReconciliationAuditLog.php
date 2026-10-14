<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoMpesaReconciliationAuditLog extends Model
{
    // Audit logs are immutable — never update or delete
    protected $fillable = [
        'sacco_mpesa_reconciliation_id', 'user_id', 'item_id',
        'action', 'description', 'meta',
        'ip_address', 'user_agent',
        'amount_before', 'amount_after',
        'status_before', 'status_after',
    ];

    protected $casts = ['meta' => 'array'];

    // No updates allowed
    public static function boot()
    {
        parent::boot();
        static::updating(function () {
            throw new \RuntimeException('Audit log records are immutable.');
        });
        static::deleting(function () {
            throw new \RuntimeException('Audit log records cannot be deleted.');
        });
    }

    public function reconciliation()
    {
        return $this->belongsTo(SaccoMpesaReconciliation::class, 'sacco_mpesa_reconciliation_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function getActionLabelAttribute(): string
    {
        $labels = [
            'session_created'       => 'Session Created',
            'csv_imported'          => 'Statement Imported',
            'auto_matched'          => 'Auto-Match Run',
            'item_matched'          => 'Item Matched',
            'item_unmatched'        => 'Item Unmatched',
            'item_excluded'         => 'Item Excluded',
            'exception_flagged'     => 'Exception Flagged',
            'exception_resolved'    => 'Exception Resolved',
            'status_changed'        => 'Status Changed',
            'approved'              => 'Reconciliation Approved',
            'float_updated'         => 'Float Balance Updated',
            'notes_updated'         => 'Notes Updated',
        ];
        return $labels[$this->action] ?? ucwords(str_replace('_', ' ', $this->action));
    }
}
