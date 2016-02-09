<?php

use Transphpile\Console\Application;

set_error_handler(function ($severity, $message, $file, $line) {
    if ($severity & error_reporting()) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
});

if (file_exists($a = __DIR__.'/../../autoload.php')) {
    require_once $a;
} else {
    require_once __DIR__.'/vendor/autoload.php';
}

$app = new Application();
$app->run();

