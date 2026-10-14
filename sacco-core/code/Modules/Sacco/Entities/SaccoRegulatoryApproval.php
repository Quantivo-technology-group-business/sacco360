<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoRegulatoryApproval extends Model
{
    protected $table = 'sacco_regulatory_approvals';

    protected $fillable = [
        'created_by_id', 'approval_type', 'title', 'description', 'board_resolution_id',
        'status', 'requested_date', 'approval_date', 'document_path',
    ];
}
