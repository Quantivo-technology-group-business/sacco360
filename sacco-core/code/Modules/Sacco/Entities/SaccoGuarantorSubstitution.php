<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;
use Modules\Loan\Entities\Loan;

class SaccoGuarantorSubstitution extends Model
{
    protected $fillable = [
        'created_by_id', 'approved_by_id', 'loan_id',
        'outgoing_guarantor_client_id', 'incoming_guarantor_client_id',
        'guaranteed_amount', 'reason', 'rejection_reason',
        'request_date', 'decision_date', 'status',
        'outgoing_guarantor_consented', 'incoming_guarantor_consented',
        'outgoing_consent_at', 'incoming_consent_at',
    ];

    protected $dates = ['request_date', 'decision_date', 'outgoing_consent_at', 'incoming_consent_at'];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function outgoingGuarantor()
    {
        return $this->belongsTo(Client::class, 'outgoing_guarantor_client_id');
    }

    public function incomingGuarantor()
    {
        return $this->belongsTo(Client::class, 'incoming_guarantor_client_id');
    }

    public function isReadyForApproval(): bool
    {
        return $this->outgoing_guarantor_consented && $this->incoming_guarantor_consented;
    }
}
