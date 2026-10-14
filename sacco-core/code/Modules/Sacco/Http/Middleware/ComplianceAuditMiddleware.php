<?php

namespace Modules\Sacco\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Sacco\Services\ComplianceAuditService;

class ComplianceAuditMiddleware
{
    public function handle(Request $request, Closure $next, ?string $module = 'sacco')
    {
        $response = $next($request);

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            (new ComplianceAuditService())->log(
                strtolower($request->method()) . ':' . $request->path(),
                $module,
                null,
                null,
                'HTTP ' . $request->method() . ' ' . $request->path(),
                ['input' => $request->except(['password', '_token'])]
            );
        }

        return $response;
    }
}
