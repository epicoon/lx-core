<?php

$configMainCode = <<<EOT
<?php

return [
    // 'localConfig' => '@site/config/main.php',

	// Directories for services
	'servicesMap' => [
		'vendor',
		'services',
	],

	// Application aliases
	'aliases' => [
		'services' => '@site/services',
	],

    'serviceConfig' => require_once(__DIR__ . '/service.php'),
    'pluginConfig' => require_once(__DIR__ . '/plugin.php'),

	// Application components
	'components' => [
    	// Routing to services
	    'router' => [
	        'class' => lx\Router::class,
	        'routes' => require __DIR__ . '/routes.php',
	    ],
	
		// You can define db-connector component for your application
		'dbConnector' => [
			'class' => lx\DbConnector::class,
			'default' => [
			    'driver' => 'pgsql',
				'hostname' => 'localhost',
				'username' => 'username',
				'password' => '*',
				'dbName' => 'dbName'
			],
		],
	],

	// Injection of configurations for services and plugins
	// 'configInjection' => [],
];

EOT;


$configRoutesCode = <<<EOT
<?php

return [

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

	// Service components
	'components' => [
		// You can define db-connector component for your service
		// 'dbConnector' => [
		//	'class' => 'lx\DbConnector',
		//	'default' => [
		//      'driver' => 'pgsql',
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

	// File name for plugin js-code
	'client' => 'client/Plugin.js',

	// Respondents map
	'respondents' => [
		'Respondent' => 'Respondent',
	],

	// Asset directories
	'images' => 'assets/images',

	// Snippets directory (or directories if value is array)
	'snippets' => 'snippets',

	// Root snippet
	'rootSnippet' => 'snippets/_root.js',
];

EOT;
