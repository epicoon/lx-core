<?php

return [
	'AutoloadMapBuilder' => 'classes/system/autoload',
	'AutoloadMap' => 'classes/system/autoload',

    'ObjectTrait' => 'classes/system/object',
    'ObjectReestr' => 'classes/system/object',

	'AbstractApplication' => 'classes/system/app',
    'BaseApplication' => 'classes/system/app',
	'HttpApplication' => 'classes/system/app',
	'ConsoleApplication' => 'classes/system/app',

	'DevApplicationLifeCycleManager' => 'classes/system/app',
	'ApplicationConductor' => 'classes/system/app',
	'ApplicationLogger' => 'classes/system/app',
	'DevLogger' => 'classes/system/app',
	'User' => 'classes/system/app',
	'UserEventsEnum' => 'classes/system/app',
	'DependencyProcessor' => 'classes/system/app',

	'Dialog' => 'classes/system/dialog',
	'Cookie' => 'classes/system/dialog',
	'ResponseCodeEnum' => 'classes/system/dialog',
	'Router' => 'classes/system/dialog',
	'SpecialAjaxRouter' => 'classes/system/dialog',
	'Resource' => 'classes/system/dialog',
    'Response' => 'classes/system/dialog',
    'Renderer' => 'classes/system/dialog',
	'Rect' => 'classes/system/dialog',
	'ResourceContext' => 'classes/system/dialog',
	'AbstractResourceVoter' => 'classes/system/dialog',
    'CorsProcessor' => 'classes/system/dialog',
    'RequestHandler' => 'classes/system/dialog/requestHandler',
    'PageRequestHandler' => 'classes/system/dialog/requestHandler',
    'AjaxRequestHandler' => 'classes/system/dialog/requestHandler',
    'CommonRequestHandler' => 'classes/system/dialog/requestHandler',

	'EventManager' => 'classes/system/event',
	'EventListenerInterface' => 'classes/system/event',
	'EventListenerTrait' => 'classes/system/event',

	'Console' => 'classes/system/console',
	'ConsoleInputContext' => 'classes/system/console',
	'Cli' => 'classes/system/console',
	'CliProcessor' => 'classes/system/console',
    'CliArgument' => 'classes/system/console',
    'CliArgumentsList' => 'classes/system/console',
    'CliCommand' => 'classes/system/console',
    'CliCommandsList' => 'classes/system/console',
	'ServiceCliExecutor' => 'classes/system/console',
	'JsCompiler' => 'classes/system/compiler',
    'PluginFrontendJsCompiler' => 'classes/system/compiler',
	'JsCompileDependencies' => 'classes/system/compiler',
	'AssetCompiler' => 'classes/system/compiler',
	'NodeJsExecutor' => 'classes/system/compiler',
	'HtmlHead' => 'classes/system/html',
	'HtmlBody' => 'classes/system/html',
	'HtmlHelper' => 'classes/system/html',
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
	'JsScriptAsset' => 'classes/plugin',

	'JsModuleMapBuilder' => 'classes/module',
	'JsModuleMap' => 'classes/module',
	'JsModuleProvider' => 'classes/module',

	'DataObject' => 'classes/dataClasses',

	'DbConnectionList' => 'classes/db',
	'DbConnector' => 'classes/db',
	'DB' => 'classes/db',
	'DBpostgres' => 'classes/db',
	'DBmysql' => 'classes/db',
    'DbTableSchema' => 'classes/db/schema',
    'DbTableField' => 'classes/db/schema',
    'DbTable' => 'classes/db/table',
    'DbTableBuilder' => 'classes/db/table',
    //deprecated
    'DbColumnDefinition' => 'classes/db',
    'DbTableSchemaProvider' => 'classes/db',
    'DbSchema' => 'classes/db',

	'Request' => 'classes/tools',
	'Vector' => 'classes/tools',
    'CascadeReport' => 'classes/tools',

	'BaseFile' => 'classes/file',
	'File' => 'classes/file',
	'Directory' => 'classes/file',
	'FileLink' => 'classes/file',
	'YamlFile' => 'classes/file',
	'DataFile' => 'classes/file',
	'DataFileAdapter' => 'classes/file/dataFileAdapter',
	'PhpDataFileAdapter' => 'classes/file/dataFileAdapter',
	'JsonDataFileAdapter' => 'classes/file/dataFileAdapter',
	'YamlDataFileAdapter' => 'classes/file/dataFileAdapter',

	'BitLine' => 'classes/bit',
	'BitMap' => 'classes/bit',


	/*******************************************************************************************************************
	 * Helpers
	 ******************************************************************************************************************/

	'ConfigHelper' => 'classes/helpers',
	'ClassHelper' => 'classes/helpers',
	'Math' => 'classes/helpers',
	'ArrayHelper' => 'classes/helpers',
	'StringHelper' => 'classes/helpers',
	'Yaml' => 'classes/helpers',
	'WidgetHelper' => 'classes/helpers',
	'I18nHelper' => 'classes/helpers',


	/*******************************************************************************************************************
	 * Behaviors
	 ******************************************************************************************************************/
	'ArrayInterface' => 'behaviors/Array',
	'ArrayTrait' => 'behaviors/Array',
	'Iterator' => 'behaviors/Array',

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
	'ApplicationLifeCycleManagerInterface' => 'interfaces',
	'ConductorInterface' => 'interfaces',
	'DataFileInterface' => 'interfaces',
    'ResponseInterface' => 'interfaces',
    'RendererInterface' => 'interfaces',
    'UserProcessorInterface' => 'interfaces',
	'UserInterface' => 'interfaces',
	'AuthenticationInterface' => 'interfaces',
	'AuthorizationInterface' => 'interfaces',
	'LoggerInterface' => 'interfaces',
	'ServiceCliInterface' => 'interfaces',
	'ServiceCliExecutorInterface' => 'interfaces',
	'ToStringConvertableInterface' => 'interfaces',
	'ResourceInterface' => 'interfaces',
	'ResourceVoterInterface' => 'interfaces',
	'ResourceAccessDataInterface' => 'interfaces',
    'DbConnectorInterface' => 'interfaces',
	'ModelInterface' => 'interfaces/model',
	'ModelManagerInterface' => 'interfaces/model',
	'ModelRelationInterface' => 'interfaces/model',

    'ModelManagerInterfaceNEW' => 'interfaces/model',

	'ApplicationToolTrait' => 'traits',
];