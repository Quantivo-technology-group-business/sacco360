<?php

namespace Modules\Sacco\Http\Middleware;

use Closure;

class UssdApiKey
{
    public function handle($request, Closure $next)
    {
        $configuredKey = config('sacco.ussd_api_key');
        if (empty($configuredKey)) {
            return $next($request);
        }

        $provided = $request->header('X-USSD-API-Key') ?? $request->input('api_key');
        if (!hash_equals($configuredKey, (string)$provided)) {
            return response()->json(['success' => false, 'message' => 'Invalid USSD API key'], 401);
        }

        return $next($request);
    }
}
