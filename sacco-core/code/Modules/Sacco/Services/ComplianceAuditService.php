<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Modules\Sacco\Entities\ComplianceAuditLog;

class ComplianceAuditService
{
    public function log(string $action, ?string $module = null, ?string $subjectType = null, ?int $subjectId = null, ?string $description = null, ?array $properties = null): void
    {
        if (!Schema::hasTable('compliance_audit_log')) {
            return;
        }

        ComplianceAuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'description' => $description,
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'created_at' => now(),
        ]);
    }
}
