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

	// Routing to services
	'router' => require __DIR__ . '/routes.php',

	// Data base settings
	'db' => require __DIR__ . '/db.php',

	// Application aliases
	'aliases' => [
	],

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
		'/' => ['service-module' => 'hiFromLx:main'],
	],
];

EOT;


$configModuleCode = <<<EOT
<?php
return [
	// Common module aliases
	'commonAliases' => [],
	// Flag for use common module aliases
	'useCommonAliases' => true,


	// Infrastructure

	// Directory for js-code
	'frontend' => 'frontend',
	// File name for js-code executed before module blocks load
	'jsBootstrap' => '_bootstrap.js',
	// File name for js-code executed after module blocks load
	'jsMain' => '_main.js',

	// Respondents map
	'respondents' => [
		'Respondent' => 'backend\Respondent',
	],

	// Directory for view
	'view' => 'view',
	// View root block
	'viewIndex' => '_root.php',

	// Asset directories
	'images' => 'assets/images',
	'css' => 'assets/css',
];

EOT;


$configServiceCode = <<<EOT
<?php
return [
	// Directory(ies) for modules
	'modules' => 'module',

	// Directory(ies) for models
	'models' => 'model',

	// Models save/load manage class
	'modelCrudAdapter' => '\lx\DbCrudAdapter',
];

EOT;


$configDbCode = <<<EOT
<?php
return [
	'dbList' => [
		'dbKey' => [
			'hostname' => 'localhost',
			'username' => 'username',
			'password' => 'password',
			'dbName' => 'dbName'
		],
	],

	'dbMap' => [
		'__default__' => ['db' => 'dbKey'],
	],
];

EOT;
