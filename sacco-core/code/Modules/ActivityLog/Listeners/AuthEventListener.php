<?php

namespace Modules\ActivityLog\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class AuthEventListener
{
    public function handleLogin(Login $event)
    {
        $user = $event->user;
        activity('auth')
            ->causedBy($user)
            ->withProperties([
                'ip_address'   => request()->ip(),
                'user_agent'   => request()->userAgent(),
                'url'          => request()->fullUrl(),
                'method'       => request()->method(),
            ])
            ->log('User Login');

        $this->setExtraColumns('User Login', 'auth', 'login');
    }

    public function handleLogout(Logout $event)
    {
        $user = $event->user;
        if (!$user) {
            return;
        }
        activity('auth')
            ->causedBy($user)
            ->withProperties([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'url'        => request()->fullUrl(),
                'method'     => request()->method(),
            ])
            ->log('User Logout');

        $this->setExtraColumns('User Logout', 'auth', 'logout');
    }

    public function handleFailed(Failed $event)
    {
        $email = isset($event->credentials['email']) ? $event->credentials['email'] : '';
        activity('auth')
            ->withProperties([
                'ip_address'      => request()->ip(),
                'user_agent'      => request()->userAgent(),
                'url'             => request()->fullUrl(),
                'method'          => request()->method(),
                'attempted_email' => $email,
            ])
            ->log('Failed Login Attempt');

        $this->setExtraColumns('Failed Login Attempt', 'auth', 'failed');
    }

    /**
     * Update the most recent activity row's extra columns.
     */
    protected function setExtraColumns(string $description, string $module, string $actionType)
    {
        $activity = \Spatie\Activitylog\Models\Activity::where('log_name', 'auth')
            ->where('description', $description)
            ->latest()
            ->first();

        if ($activity) {
            $activity->ip_address  = request()->ip();
            $activity->user_agent  = request()->userAgent();
            $activity->module      = $module;
            $activity->action_type = $actionType;
            $activity->save();
        }
    }
}
