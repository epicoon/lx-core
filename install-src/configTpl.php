<?php

$__map__ = <<<EOT
<?php

return [
    lx\ConsoleApplication::class => __DIR__ . '/console.php',
    lx\AbstractApplication::class => __DIR__ . '/web.php',
];

EOT;

$local = <<<EOT
<?php

return [
    'mode' => 'dev',

    'components' => [
        'lifeCycle' => lx\DevApplicationLifeCycleHandler::class,
        'dbConnector' => [
            'class' => 'lx\DbConnector',
            'default' => [
                'driver' => 'pgsql',
                'hostname' => 'localhost',
                'username' => '',
                'password' => '',
                'dbName' => '',
            ],
        ],
    ],

    'configInjection' => [
    ],
];

EOT;

$common = <<<EOT
<?php

return [
    'localConfig' => '@site/config/main.php',

    // Directories for services
    'serviceCategories' => [
        'project' => [
            'app',
            'services',
        ],
        'dependencies' => [
            'vendor',
        ],
    ],

    'aliases' => [
        'services' => '@site/services',
    ],

    // Application components
    'components' => require_once(__DIR__ . '/common/components.php'),

    // DI processot map
    'diProcessor' => require_once(__DIR__ . '/common/diMap.php'),

    // Default services config
    'serviceConfig' => require_once(__DIR__ . '/common/service.php'),

    // Default plugins config
    'pluginConfig' => require_once(__DIR__ . '/common/plugin.php'),
];

EOT;

$console = <<<EOT
<?php

return lx\ArrayHelper::mergeRecursiveDistinct(
    require_once(__DIR__ . '/common.php'),
    [
        'components' => require_once(__DIR__ . '/console/components.php'),
    ],
    true
);

EOT;

$web = <<<EOT
<?php

return lx\ArrayHelper::mergeRecursiveDistinct(
    require_once(__DIR__ . '/common.php'),
    [
        'components' => require_once(__DIR__ . '/web/components.php'),
    ],
    true
);

EOT;

$common_components = <<<EOT
<?php

return [
    'cssManager' => [
        'defaultCssPreset' => 'dark',
        'buildType' => lx\CssManager::BUILD_TYPE_ALL_TOGETHER,
    ],
];

EOT;

$common_diMap = <<<EOT
<?php

return [
    'interfaces' => [

	],
    'classes' => [

    ],
];

EOT;

$common_plugin = <<<EOT
<?php

return [
	/*
	 * none - [[lx\Plugin::CACHE_NONE]] don't use cache, allways rebuild plugin
	 * on - [[lx\Plugin::CACHE_ON]] use cache if exists, else create cache
	 * strict - [[lx\Plugin::CACHE_STRICT]] allways use cache. Plugin will not build if cache doesn't exist
	 * build - [[lx\Plugin::CACHE_BUILD]] allways rebuild plugin and cache
	 * smart - [[lx\Plugin::CACHE_SMART]] update cache if files were changed
	 */
	'cacheType' => lx\PluginCacheManager::CACHE_SMART,

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

$common_service = <<<EOT
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

$console_components = <<<EOT
<?php

return [
    'router' => [
        'routes' => require_once(__DIR__ . '/commands.php'),
    ],
];

EOT;

$console_commands = <<<EOT
<?php

return [
    // Routing for your console commands, like:
    // '!process' => 'lx/process',
    // 'test' => app\TestCommand::class,
];

EOT;

$web_components = <<<EOT
<?php

return [
    'router' => [
        'routes' => require_once(__DIR__ . '/routes.php'),
    ],
];

EOT;

$web_routes = <<<EOT
<?php

return [
    // Routing for your console commands, like:
	// '/' => ['plugin' => 'app:main'],
    // '!process' => 'lx/process',
];

EOT;
