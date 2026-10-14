<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class SaccoCardDispute extends Model
{
    protected $fillable = [
        'sacco_debit_card_id', 'sacco_card_transaction_id', 'client_id',
        'created_by_id', 'resolved_by_id',
        'dispute_reference', 'dispute_type',
        'disputed_amount', 'settled_amount',
        'transaction_date', 'merchant_name',
        'description', 'supporting_documents', 'resolution_notes',
        'status', 'submitted_date', 'resolution_date', 'resolution_days',
    ];

    protected $dates = ['transaction_date', 'submitted_date', 'resolution_date'];

    public function card()
    {
        return $this->belongsTo(SaccoDebitCard::class, 'sacco_debit_card_id');
    }

    public function transaction()
    {
        return $this->belongsTo(SaccoCardTransaction::class, 'sacco_card_transaction_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['submitted', 'under_investigation']);
    }
}
