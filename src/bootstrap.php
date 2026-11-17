<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$configFile = APP_ROOT . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('config.php fehlt. Bitte config.sample.php nach config.php kopieren und anpassen.');
}
$GLOBALS['__config'] = require $configFile;

function config(string $key, mixed $default = null): mixed
{
    return $GLOBALS['__config'][$key] ?? $default;
}

date_default_timezone_set((string) config('timezone', 'Europe/Berlin'));
mb_internal_encoding('UTF-8');

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/time.php';
