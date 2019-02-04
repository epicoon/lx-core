<?php

$lxConsoleCode = <<<EOT
#!/usr/bin/env php
<?php

// fcgi doesn't have STDIN and STDOUT defined by default
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
defined('STDOUT') or define('STDOUT', fopen('php://stdout', 'w'));

require_once(__DIR__ . '/../vendor/lx/lx-core/src/php/app.php');


EOT;
