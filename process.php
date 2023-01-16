<?php

use lx\ErrorHelper;

require_once __DIR__ . '/main.php';

$processClassName = $argv[1] ?? null;
if (!$processClassName) {
    echo 'Process class does not exist';
    return;
}

$configString = $argv[2];
$config = json_decode($configString ?? '', true) ?? [];

try {
    /** @var \lx\process\ProcessApplication $app */
    $app = new $processClassName($config);
    $app->run();
} catch (\Exception $exception) {
    echo PHP_EOL . ErrorHelper::renderErrorString($exception);
}
