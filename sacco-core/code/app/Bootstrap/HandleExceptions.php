<?php

namespace App\Bootstrap;

use Illuminate\Contracts\Foundation\Application;

class HandleExceptions extends \Illuminate\Foundation\Bootstrap\HandleExceptions
{
    public function bootstrap(Application $app)
    {
        self::$reservedMemory = str_repeat('x', 10240);

        $this->app = $app;

        if (PHP_VERSION_ID >= 80100) {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_STRICT);
        } else {
            error_reporting(-1);
        }

        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);

        if (!$app->environment('testing')) {
            ini_set('display_errors', 'Off');
        }
    }

    public function handleError($level, $message, $file = '', $line = 0, $context = [])
    {
        if (in_array($level, [E_DEPRECATED, E_USER_DEPRECATED, E_STRICT], true)) {
            return true;
        }

        return parent::handleError($level, $message, $file, $line, $context);
    }
}
