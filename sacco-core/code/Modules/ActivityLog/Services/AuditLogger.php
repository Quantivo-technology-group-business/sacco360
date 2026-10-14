<?php

namespace Modules\ActivityLog\Services;

use Spatie\Activitylog\Models\Activity;

class AuditLogger
{
    /**
     * Log an activity with enriched context (IP, user agent, URL, method).
     *
     * @param string $description
     * @param mixed|null $subject
     * @param array $properties
     * @param string $logName
     * @param string $actionType
     * @return Activity
     */
    public static function log(
        string $description,
        $subject = null,
        array $properties = [],
        string $logName = 'default',
        string $actionType = ''
    ): Activity {
        $request = request();

        // Enrich properties with request context
        $properties['ip_address'] = $request->ip();
        $properties['user_agent'] = $request->userAgent();
        $properties['url'] = $request->fullUrl();
        $properties['method'] = $request->method();

        // Determine module from subject class or logName
        $module = $logName !== 'default' ? $logName : '';
        if (empty($module) && $subject !== null && is_object($subject)) {
            $parts = explode('\\', get_class($subject));
            // Try to get module name (e.g. Modules\Sacco\Entities\Foo -> Sacco)
            if (count($parts) >= 2 && $parts[0] === 'Modules') {
                $module = strtolower($parts[1]);
            }
        }

        $builder = activity($logName)
            ->withProperties($properties);

        if ($subject !== null) {
            $builder->on($subject);
        }

        $activity = $builder->log($description);

        // Set the extra columns directly
        $activity->ip_address = $request->ip();
        $activity->user_agent = $request->userAgent();
        $activity->module = $module;
        $activity->action_type = $actionType;
        $activity->save();

        return $activity;
    }
}
