<?php

$configCode = <<<EOT
name: <name>

autoload:
  psr-4:
    <nmsp>\sys: '.system'
    <nmsp>: ''

service:
  class: <service>

  routes:
    /: '' #TODO: must be defined

  plugins: <plugin>

EOT;


$serviceCode = <<<EOT
<?php

namespace ;

class Service extends \lx\Service {

}

EOT;
