<?php

/*
|--------------------------------------------------------------------------
| PHP 8.1+ compatibility shim for Laravel 8
|--------------------------------------------------------------------------
|
| Modern PHP versions emit deprecations during Laravel 8 bootstrap that
| get converted to fatal exceptions. Suppress those until a full Laravel
| 11+ upgrade is completed.
|
*/

if (PHP_VERSION_ID >= 80100) {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_STRICT);
}
