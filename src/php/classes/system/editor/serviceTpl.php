<?php

$configCode = <<<EOT
name: <name>

autoload:
  psr-4:
    <nmsp>: ''

service:
  class: <service>

  router:
    type: map
    routes:
      <route>: '' #TODO: must be defined

  plugins: <plugin>
  models: <model>
  modelCrudAdapter: \lx\DbCrudAdapter

EOT;


$serviceCode = <<<EOT
<?php

namespace ;

class Service extends \lx\Service {

}

EOT;
