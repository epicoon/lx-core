<?php

$configMainCode = <<<EOT
<?php

return [
	// Application mode
	'mode' => 'dev',

	// Directories for packages
	'packagesMap' => [
		'vendor',
		'services',
	],

	// Application aliases
	'aliases' => [
		'services' => '@site/services',
	],

	// Routing to services
	'router' => require __DIR__ . '/routes.php',

	// Global js-code executed before rendered module load
	//'jsBootstrap' => '',
	// Global js-code executed after rendered module load
	//'jsMain' => '',
];

EOT;


$configRoutesCode = <<<EOT
<?php
/*
Router settings
Type variants:
	- map (require parameter 'routes' - routes map, or 'path' - path to routes map)
	- class (require parameter 'name' - Router class name)
*/
return [
	'type' => 'map',
	'routes' => [
		'/' => ['service-module' => 'lx/lx-hello:world', 'on-mode' => 'dev'],
	],
];

EOT;


$configServiceCode = <<<EOT
<?php
return [
	// Common module aliases
	'aliases' => [],
	// Flag for use common module aliases
	'useCommonAliases' => true,

	// Directory(ies) for modules
	'modules' => 'module',

	// Directory(ies) for models
	'models' => 'model',

	// Models save/load manage class
	'modelCrudAdapter' => 'lx\DbCrudAdapter',

	// DB connection settings
	'dbList' => [
		'db' => [
			'hostname' => 'localhost',
			'username' => 'username',
			'password' => 'password',
			'dbName' => 'dbName'
		],
	],
];

EOT;


$configModuleCode = <<<EOT
<?php
return [
	// Common module aliases
	'aliases' => [],
	// Flag for use common module aliases
	'useCommonAliases' => true,

	// File name for js-code executed before module blocks load
	'jsBootstrap' => 'frontend/_bootstrap.js',
	// File name for js-code executed after module blocks load
	'jsMain' => 'frontend/_main.js',

	// Respondents map
	'respondents' => [
		'Respondent' => 'backend\Respondent',
	],

	// View root block
	'view' => 'view/_root.php',

	// Asset directories
	'images' => 'assets/images',
	'css' => 'assets/css',
];

EOT;
