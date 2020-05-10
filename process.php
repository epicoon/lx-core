<?php

require_once __DIR__ . '/main.php';
$processClassName = $argv[1] ?? null;
$config = json_decode($argv[2] ?? '', true) ?? [];
$app = new $processClassName($config);
$app->run();
