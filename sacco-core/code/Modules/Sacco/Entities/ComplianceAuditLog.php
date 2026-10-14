<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class ComplianceAuditLog extends Model
{
    protected $table = 'compliance_audit_log';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'action', 'module', 'subject_type', 'subject_id',
        'description', 'properties', 'ip_address', 'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
    ];
}
