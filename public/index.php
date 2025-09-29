<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dartcafe\EmailValidator\Demo\App;
use Dartcafe\EmailValidator\Demo\Http\Request;

$CONFIG_DIR = realpath(__DIR__ . '/../config') ?: (__DIR__ . '/../config');

$app = new App($CONFIG_DIR);
$app->handle(Request::fromGlobals())->send();
