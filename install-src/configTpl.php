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
	// 'components' => [],

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
	'plugins' => 'plugin',

	// Directory(ies) for models
	'models' => 'model',

	// Models save/load manage class
	'modelCrudAdapter' => null,

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


$configPluginCode = <<<EOT
<?php
return [
	// Common plugin aliases
	'aliases' => [],
	// Flag for use common plugin aliases
	'useCommonAliases' => true,

	// File name for js-code executed before plugin snippets load
	'jsBootstrap' => 'frontend/_bootstrap.js',
	// File name for js-code executed after plugin snippets load
	'jsMain' => 'frontend/_main.js',

	// Respondents map
	'respondents' => [
		'Respondent' => 'backend\Respondent',
	],
	
	// Root snippet
	'rootSnippet' => 'snippets/_root.js',
	
	// Snippets directory (or directories if value is array)
	'snippets' => 'snippets',

	// Asset directories
	'images' => 'assets/images',
	'css' => 'assets/css',
	'bundles' => 'assets/bundles',

	/*
	 * none - [[lx\Plugin::CACHE_NONE]] don't use cache, allways rebuild plugin
	 * on - [[lx\Plugin::CACHE_ON]] use cache if exists, else create cache
	 * strict - [[lx\Plugin::CACHE_STRICT]] allways use cache. Plugin doesn't build if cache not exists
	 * build - [[lx\Plugin::CACHE_BUILD]] allways rebuild plugin and cache
	 * smart - [[lx\Plugin::CACHE_SMART]] update cache if files were changed
	 */
	'cacheType' => lx\Plugin::CACHE_SMART,
];

EOT;
