<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoKycDocument extends Model
{
    protected $fillable = [
        'client_id', 'uploaded_by_id', 'reviewed_by_id', 'document_type',
        'document_number', 'file_path', 'issue_date', 'expiry_date',
        'status', 'rejection_reason', 'reviewed_at',
    ];
}
