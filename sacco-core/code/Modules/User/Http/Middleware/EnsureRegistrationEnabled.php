<?php

namespace Modules\User\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Setting\Entities\Setting;

class EnsureRegistrationEnabled
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('app.installed')) {
            abort(404);
        }

        $setting = Setting::where('setting_key', 'user.enable_registration')->first();

        if (!$setting || $setting->setting_value !== 'yes') {
            abort(404);
        }

        return $next($request);
    }
}
