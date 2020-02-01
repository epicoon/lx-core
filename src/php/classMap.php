<?php

return [
	'AutoloadMapBuilder' => 'classes/system/autoload',
	'AutoloadMap' => 'classes/system/autoload',

	'AbstractApplication' => 'classes/system/app',
	'Application' => 'classes/system/app',
	'ConsoleApplication' => 'classes/system/app',

	'ApplicationConductor' => 'classes/system/app',
	'ApplicationComponent' => 'classes/system/app',
	'ApplicationLogger' => 'classes/system/app',
	'Router' => 'classes/system/app',
	'AjaxRouter' => 'classes/system/app',
	'Response' => 'classes/system/app',
	'ResponseSource' => 'classes/system/app',
	'User' => 'classes/system/app',
	'UserEventsEnum' => 'classes/system/app',

	'Dialog' => 'classes/system/dialog',
	'Cookie' => 'classes/system/dialog',

	'EventManager' => 'classes/system/event',
	'EventLestenerInterface' => 'classes/system/event',
	'EventListenerTrait' => 'classes/system/event',

	'Console' => 'classes/system',
	'HtmlHead' => 'classes/system',
	'JsCompiler' => 'classes/system/JsCompiler',
	'JsCompileDependencies' => 'classes/system/JsCompiler',
	'NodeJsExecutor' => 'classes/system/JsCompiler',
	'PluginEditor' => 'classes/system/editor',
	'ServiceEditor' => 'classes/system/editor',

	'Language' => 'classes/i18n',
	'I18nMap' => 'classes/i18n',
	'I18nApplicationMap' => 'classes/i18n',
	'I18nServiceMap' => 'classes/i18n',
	'I18nPluginMap' => 'classes/i18n',

	'PackageDirectory' => 'classes/package',
	'PackageBrowser' => 'classes/package',
	'ServicesMap' => 'classes/package',
	'Service' => 'classes/package',
	'ServiceRouter' => 'classes/package',
	'ServiceController' => 'classes/package',
	'ServiceConductor' => 'classes/package',

	'PluginDirectory' => 'classes/plugin',
	'PluginBrowser' => 'classes/plugin',
	'Snippet' => 'classes/plugin',
	'Plugin' => 'classes/plugin',
	'PluginConductor' => 'classes/plugin',
	'Respondent' => 'classes/plugin',
	'PluginBuildContext' => 'classes/plugin/build',
	'SnippetBuildContext' => 'classes/plugin/build',
	'SnippetCacheData' => 'classes/plugin/build',

	'JsModuleMapBuilder' => 'classes/module',
	'JsModuleMap' => 'classes/module',

	'DataObject' => 'classes/dataClasses',

	'DbColumnDefinition' => 'classes/db',
	'DbTableSchemaProvider' => 'classes/db',
	'DbSchema' => 'classes/db',
	'DbTable' => 'classes/db',
	'DbRecord' => 'classes/db',
	'DbConnectionList' => 'classes/db',
	'DB' => 'classes/db',
	'DBpostgres' => 'classes/db',
	'DBmysql' => 'classes/db',

	'Request' => 'classes/tools',
	'Vector' => 'classes/tools',
	'Collection' => 'classes/tools',
	'Tree' => 'classes/tools',
	'Iterator' => 'classes/tools',

	'BaseFile' => 'classes/file',
	'File' => 'classes/file',
	'Directory' => 'classes/file',
	'YamlFile' => 'classes/file',
	'HtmpFile' => 'classes/file',
	'ConfigFile' => 'classes/file',

	'Cli' => 'classes/system/cli',
	'CliProcessor' => 'classes/system/cli',

	'BitLine' => 'classes/bit',
	'BitMap' => 'classes/bit',


	/*******************************************************************************************************************
	 * Helpers
	 ******************************************************************************************************************/

	'ConfigHelper' => 'classes/helpers',
	'ClassHelper' => 'classes/helpers',
	'ModuleHelper' => 'classes/helpers',
	'Math' => 'classes/helpers',
	'ArrayHelper' => 'classes/helpers',
	'StringHelper' => 'classes/helpers',
	'Geom' => 'classes/helpers',
	'Yaml' => 'classes/helpers',
	'Htmp' => 'classes/helpers',
	'WidgetHelper' => 'classes/helpers',
	'I18nHelper' => 'classes/helpers',


	/*******************************************************************************************************************
	 * Behaviors
	 ******************************************************************************************************************/
	'ClassOfServiceInterface' => 'behaviors/ClassOfService',
	'ClassOfServiceTrait' => 'behaviors/ClassOfService',

	'ContextTreeInterface' => 'behaviors/ContextTree',
	'ContextTreeTrait' => 'behaviors/ContextTree',

	'FusionInterface' => 'behaviors/Fusion',
	'FusionTrait' => 'behaviors/Fusion',
	'FusionComponentInterface' => 'behaviors/Fusion',
	'FusionComponentTrait' => 'behaviors/Fusion',
	'FusionComponentList' => 'behaviors/Fusion',

	'ErrorCollectorInterface' => 'behaviors/ErrorCollector',
	'ErrorCollectorTrait' => 'behaviors/ErrorCollector',
	'ErrorCollectorList' => 'behaviors/ErrorCollector',
	'ErrorCollectorError' => 'behaviors/ErrorCollector',


	/*******************************************************************************************************************
	 * Interfaces
	 ******************************************************************************************************************/
	'UserProcessorInterface' => 'interfaces',
	'AuthenticationInterface' => 'interfaces',
	'AuthorizationInterface' => 'interfaces',
	'LoggerInterface' => 'interfaces',
	'ServiceCliInterface' => 'interfaces',
	'ServiceCliExecutorInterface' => 'interfaces',
	'ModelInterface' => 'interfaces',
	'ToStringConvertableInterface' => 'interfaces',

	'ApplicationToolTrait' => 'traits',
];