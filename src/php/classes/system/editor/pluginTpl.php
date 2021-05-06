<?php

$respondentCode = <<<EOT
<?php

namespace;

use lx\Respondent as lxRespondent;

class RespondentName extends lxRespondent
{

}

EOT;


$bootstrapJsCode = <<<EOT
/**
 * @const {lx.Plugin} Plugin
 */

EOT;


$mainJsCode = <<<EOT
/**
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

EOT;


$viewCode = <<<EOT
/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

EOT;


$pluginCode = <<<EOT
<?php

namespace ;

use lx\Plugin as lxPlugin;

class Plugin extends lxPlugin
{

}

EOT;
