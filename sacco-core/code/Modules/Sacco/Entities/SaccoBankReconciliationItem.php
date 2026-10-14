<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\Entities\JournalEntry;

class SaccoBankReconciliationItem extends Model
{
    protected $fillable = [
        'sacco_bank_reconciliation_id', 'transaction_date', 'description', 'reference',
        'amount', 'entry_type', 'source', 'journal_entry_id', 'match_status',
    ];

    public function reconciliation()
    {
        return $this->belongsTo(SaccoBankReconciliation::class, 'sacco_bank_reconciliation_id');
    }

    public function journal_entry()
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
