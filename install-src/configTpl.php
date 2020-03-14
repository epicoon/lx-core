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

	// Application components
	'components' => [
		// You can define db-connector component for your application
		'dbConnector' => [
			'class' => 'lx\DbConnector',
			'db' => [
				'hostname' => 'localhost',
				'username' => 'username',
				'password' => '*',
				'dbName' => 'dbName'
			],
		],
	],

	// Injection of configurations for services and plugins
	// 'configInjection' => [],

	// Global js-code executed before page rendered
	// 'jsBootstrap' => '',
	// Global js-code executed after page rendered
	// 'jsMain' => '',
];

EOT;


$configRoutesCode = <<<EOT
<?php
/*
Router settings
Type variants:
	- map (require parameter 'routes' - routes map - array with pares key - route, value - source information)
	- class (require parameter 'name' - Router class name)
*/
return [
	'type' => 'map',
	'routes' => [
		'/' => ['service-plugin' => 'lx/lx-hello:world', 'on-mode' => 'dev'],
	],
];

EOT;


$configServiceCode = <<<EOT
<?php
return [
	// Common plugin aliases
	'aliases' => [],
	// Flag for use common plugin aliases
	'useCommonAliases' => true,

	// Directory(ies) for plugins
	'plugins' => 'plugins',

	// Directory(ies) for models
	'models' => 'models',

	// Models save/load manage class
	'modelCrudAdapter' => null,

	// Service components
	'components' => [
		// You need to install 'lx/lx-model' service to use this model provider
		// 'modelProvider' => [
		//	'class' => 'lx\model\ModelProvider',
		//	'crudAdapter' => 'lx\model\CRUD\db\DbCrudAdapter',
		// ],

		// You can define db-connector component for your service
		// 'dbConnector' => [
		//	'class' => 'lx\DbConnector',
		//	'db' => [
		//		'hostname' => 'localhost',
		//		'username' => 'username',
		//		'password' => '*',
		//		'dbName' => 'dbName'
		//	],
		//],
	],
];

EOT;


$configPluginCode = <<<EOT
<?php
return [
	/*
	 * none - [[lx\Plugin::CACHE_NONE]] don't use cache, allways rebuild plugin
	 * on - [[lx\Plugin::CACHE_ON]] use cache if exists, else create cache
	 * strict - [[lx\Plugin::CACHE_STRICT]] allways use cache. Plugin will not build if cache doesn't exist
	 * build - [[lx\Plugin::CACHE_BUILD]] allways rebuild plugin and cache
	 * smart - [[lx\Plugin::CACHE_SMART]] update cache if files were changed
	 */
	'cacheType' => lx\Plugin::CACHE_SMART,

	// Common plugin aliases
	'aliases' => [],
	// Flag for use common plugin aliases
	'useCommonAliases' => true,

	// Respondents map
	'respondents' => [
		'Respondent' => 'backend\Respondent',
	],

	// Asset directories
	'images' => 'assets/images',
	'css' => 'assets/css',

	// Snippets directory (or directories if value is array)
	'snippets' => 'snippets',

	// Root snippet
	'rootSnippet' => 'snippets/_root.js',

	// File name for js-code which is executed before plugin snippets load
	'jsBootstrap' => 'frontend/_bootstrap.js',
	// File name for js-code which is executed after plugin snippets load
	'jsMain' => 'frontend/_main.js',
];

EOT;
