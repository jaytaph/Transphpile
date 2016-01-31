<?php

use Phpile\Console\Application;

include __DIR__ . "/vendor/autoload.php";

const PHPILE_SEMVER = '0.0.1';

$app = new Application("Phpile", PHPILE_SEMVER);
$app->add(new Phpile\Command\Transpile());
$app->run();

