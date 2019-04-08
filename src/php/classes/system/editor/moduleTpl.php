<?php

$respondentCode = <<<EOT
<?php

namespace;

class RespondentName extends \lx\Respondent {

}

EOT;


$bootstrapJsCode = <<<EOT
/**
 * @const lx.Module Module
 * */

EOT;


$mainJsCode = <<<EOT
/**
 * @const lx.Module Module
 * */

EOT;


$viewCode = <<<EOT
<?php
/**
 * @var lx\Module $Module
 * @var lx\Block $Block
 * */

EOT;

$viewCode =
'<?php' . PHP_EOL .
'/**' . PHP_EOL .
' * @var lx\Module $Module' . PHP_EOL .
' * @var lx\Block $Block' . PHP_EOL .
' * */' . PHP_EOL;


$moduleCode = <<<EOT
<?php

namespace ;

class Module extends \lx\Module {

}

EOT;
