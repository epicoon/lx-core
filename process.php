<?php

require_once __DIR__ . '/main.php';

$processClassName = $argv[1] ?? null;

$configString = $argv[2];
$config = json_decode($configString ?? '', true) ?? [];

$app = new $processClassName($config);
$app->run();
