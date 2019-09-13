<?php

$respondentCode = <<<EOT
<?php

namespace;

class RespondentName extends \lx\Respondent {

}

EOT;


$bootstrapJsCode = <<<EOT
/**
 * @const lx.Plugin Plugin
 * */

EOT;


$mainJsCode = <<<EOT
/**
 * @const lx.Plugin Plugin
 * */

EOT;


$viewCode =
'/**' . PHP_EOL .
' * @const lx.Application App' . PHP_EOL .
' * @const lx.Plugin Plugin' . PHP_EOL .
' * @const lx.Snippet Snippet' . PHP_EOL .
' * */' . PHP_EOL;


$pluginCode = <<<EOT
<?php

namespace ;

class Plugin extends \lx\Plugin {

}

EOT;
