<?php

$respondentCode = <<<EOT
<?php

namespace;

use lx\Respondent as lxRespondent;

class RespondentName extends lxRespondent
{

}

EOT;


$mainJsCode = <<<EOT
class Plugin extends lx.Plugin {
    initCssAsset(css) {

    }
    
    run() {

    }
}

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
