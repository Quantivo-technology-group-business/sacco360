<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoMpesaReconciliationItem extends Model
{
    protected $fillable = [
        'sacco_mpesa_reconciliation_id',
        'receipt_number', 'transaction_id', 'completion_time',
        'details', 'transaction_status',
        'paid_in_number', 'account_reference',
        'amount', 'other_party_info_amount', 'transaction_cost',
        'running_balance', 'direction',
        'match_status',
        'c2b_transaction_id', 'b2c_transaction_id',
        'matched_by_id', 'matched_at',
        'variance_amount', 'match_notes',
        'is_duplicate', 'is_exception', 'exception_reason',
    ];

    protected $dates = ['completion_time', 'matched_at'];

    public function reconciliation()
    {
        return $this->belongsTo(SaccoMpesaReconciliation::class, 'sacco_mpesa_reconciliation_id');
    }

    public function c2bTransaction()
    {
        return $this->belongsTo(SaccoMpesaC2bTransaction::class, 'c2b_transaction_id');
    }

    public function b2cTransaction()
    {
        return $this->belongsTo(SaccoMpesaB2cTransaction::class, 'b2c_transaction_id');
    }

    public function isMatched(): bool
    {
        return in_array($this->match_status, ['matched', 'matched_with_variance', 'manually_matched']);
    }

    public function getMatchBadgeColorAttribute(): string
    {
        return match ($this->match_status) {
            'matched'                => 'success',
            'matched_with_variance'  => 'warning',
            'manually_matched'       => 'info',
            'excluded'               => 'secondary',
            default                  => 'danger',
        };
    }
}
