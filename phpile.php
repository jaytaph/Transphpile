<?php

use PHPile\Console\Application;

include __DIR__ . "/vendor/autoload.php";

const PHPILE_SEMVER = '0.0.1';


// Get rid of this
$functionStack = array();

$app = new Application("PHPile", PHPILE_SEMVER);
$app->add(new PHPile\Command\Transpile());
$app->run();

