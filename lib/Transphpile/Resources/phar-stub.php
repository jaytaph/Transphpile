#!/usr/bin/env php
<?php

use Transphpile\Console\Application;

set_error_handler(function ($severity, $message, $file, $line) {
    if ($severity & error_reporting()) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
});

Phar::mapPhar('transphpile.phar');
require_once 'phar://transphpile.phar/vendor/autoload.php';

$app = new Application();
$app->run();

__HALT_COMPILER();
