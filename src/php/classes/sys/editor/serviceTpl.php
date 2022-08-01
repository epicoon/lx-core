<?php

$configCode = <<<EOT
name: <name>

autoload:
  psr-4:
    <nmsp>: ''
    <nmsp>sys\: '.system'

service:
  class: <service>

  routes:
    /: '' #TODO: must be defined

  plugins: <plugin>

EOT;


$serviceCode = <<<EOT
<?php

namespace ;

use lx\Service as lxService;

class Service extends lxService
{

}

EOT;
