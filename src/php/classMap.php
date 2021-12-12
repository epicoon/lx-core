<?php

return [
	'AutoloadMapBuilder' => 'classes/system/autoload',
	'AutoloadMap' => 'classes/system/autoload',

    'ObjectTrait' => 'classes/system/object',
    'ObjectInterface' => 'classes/system/object',
    'ObjectReestr' => 'classes/system/object',

	'AbstractApplication' => 'classes/system/app',
    'BaseApplication' => 'classes/system/app',
	'HttpApplication' => 'classes/system/app',
	'ConsoleApplication' => 'classes/system/app',

    'PluginProvider' => 'classes/system/app',
    'ServiceProvider' => 'classes/system/app',
    'HttpAssetsManager' => 'classes/system/app',
	'ApplicationConductor' => 'classes/system/app',
	'ApplicationLogger' => 'classes/system/app',
	'DevLogger' => 'classes/system/app',
	'User' => 'classes/system/app',
	'UserEventsEnum' => 'classes/system/app',

	'DependencyProcessor' => 'classes/system/di',
    'DependencyBuilder' => 'classes/system/di',

    'DevApplicationLifeCycleManager' => 'classes/system/lifeCycle',
    
	'Dialog' => 'classes/system/dialog',
	'Cookie' => 'classes/system/dialog',
	'ResponseCodeEnum' => 'classes/system/dialog',
	'Router' => 'classes/system/dialog',
	'SpecialAjaxRouter' => 'classes/system/dialog',
	'Resource' => 'classes/system/dialog',
    'Response' => 'classes/system/dialog',
    'Module' => 'classes/system/dialog',
	'ResourceContext' => 'classes/system/dialog',
	'AbstractResourceVoter' => 'classes/system/dialog',
    'CorsProcessor' => 'classes/system/dialog',
    'RequestHandler' => 'classes/system/dialog/requestHandler',
    'PageRequestHandler' => 'classes/system/dialog/requestHandler',
    'AjaxRequestHandler' => 'classes/system/dialog/requestHandler',
    'CommonRequestHandler' => 'classes/system/dialog/requestHandler',

    'EventManagerInterface' => 'classes/system/event',
	'EventManager' => 'classes/system/event',
	'EventListenerInterface' => 'classes/system/event',
	'EventListenerTrait' => 'classes/system/event',

	'Console' => 'classes/system/console',
	'Cli' => 'classes/system/console',
	'CliProcessor' => 'classes/system/console',
    'CliArgument' => 'classes/system/console',
    'CliArgumentsList' => 'classes/system/console',
    'CliCommand' => 'classes/system/console',
    'CliCommandsList' => 'classes/system/console',
	'ServiceCliExecutor' => 'classes/system/console',
    'AbstractConsoleInput' => 'classes/system/console/input',
    'ConsoleInput' => 'classes/system/console/input',
    'ConsoleSelect' => 'classes/system/console/input',

	'JsCompiler' => 'classes/system/compiler',
    'JsCompilerExtension' => 'classes/system/compiler',
    'PluginFrontendJsCompiler' => 'classes/system/compiler',
	'JsCompileDependencies' => 'classes/system/compiler',
	'AssetCompiler' => 'classes/system/compiler',
	'NodeJsExecutor' => 'classes/system/compiler',
    'Minimizer' => 'classes/system/compiler',
    'SyntaxExtender' => 'classes/system/compiler',

    'HtmlRenderer' => 'classes/system/html',
    'HtmlTemplateProvider' => 'classes/system/html',
    'HtmlHead' => 'classes/system/html',
	'HtmlBody' => 'classes/system/html',
	'HtmlHelper' => 'classes/system/html',
	'PluginEditor' => 'classes/system/editor',
	'ServiceEditor' => 'classes/system/editor',

	'Language' => 'classes/i18n',
	'I18nMap' => 'classes/i18n',
	'ApplicationI18nMap' => 'classes/i18n',
	'ServiceI18nMap' => 'classes/i18n',
	'PluginI18nMap' => 'classes/i18n',

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

	'DbConnectionRegistry' => 'classes/db',
	'DbConnector' => 'classes/db',
	'DbConnection' => 'classes/db',
	'DbPostgres' => 'classes/db',
	'DbMysql' => 'classes/db',
    'DbTable' => 'classes/db/table',
    'DbTableEditor' => 'classes/db/table',
    'DbTableSchema' => 'classes/db/table',
    'DbTableField' => 'classes/db/table',
    'DbForeignKeyInfo' => 'classes/db/table',
    'DbSelectQuery' => 'classes/db/query',
    'DbSelectParser' => 'classes/db/query',
    'DbQueryTableData' => 'classes/db/query',
    'DbQueryFieldData' => 'classes/db/query',
    'DbQueryBuilder' => 'classes/db/query',

	'Request' => 'classes/tools',
	'Vector' => 'classes/tools',
    'CascadeReport' => 'classes/tools',
    'Undefined' => 'classes/tools',

	'BaseFile' => 'classes/file',
	'File' => 'classes/file',
	'Directory' => 'classes/file',
	'FileLink' => 'classes/file',
	'DataFile' => 'classes/file',
	'DataFileAdapter' => 'classes/file/dataFileAdapter',
	'PhpDataFileAdapter' => 'classes/file/dataFileAdapter',
	'JsonDataFileAdapter' => 'classes/file/dataFileAdapter',
	'YamlDataFileAdapter' => 'classes/file/dataFileAdapter',

    'DbException' => 'classes/exceptions',


	/*******************************************************************************************************************
	 * Helpers
	 ******************************************************************************************************************/

	'ConfigHelper' => 'classes/helpers',
	'ClassHelper' => 'classes/helpers',
	'Math' => 'classes/helpers',
	'ArrayHelper' => 'classes/helpers',
	'StringHelper' => 'classes/helpers',
	'Yaml' => 'classes/helpers',
	'I18nHelper' => 'classes/helpers',
    'ErrorHelper' => 'classes/helpers',
    'PhpConfigHelper' => 'classes/helpers',
    'CodeConverterHelper' => 'classes/helpers',


	/*******************************************************************************************************************
	 * Behaviors
	 ******************************************************************************************************************/
	'ArrayInterface' => 'behaviors/Array',
	'ArrayTrait' => 'behaviors/Array',
	'Iterator' => 'behaviors/Array',

	'ContextTreeInterface' => 'behaviors/ContextTree',
	'ContextTreeTrait' => 'behaviors/ContextTree',

	'FusionInterface' => 'behaviors/Fusion',
	'FusionTrait' => 'behaviors/Fusion',
	'FusionComponentInterface' => 'behaviors/Fusion',
	'FusionComponentTrait' => 'behaviors/Fusion',
	'FusionComponentList' => 'behaviors/Fusion',

    'FlightRecorderHolderInterface' => 'behaviors/FlightRecorderHolder',
    'FlightRecorderHolderTrait' => 'behaviors/FlightRecorderHolder',
    'FlightRecorderInterface' => 'behaviors/FlightRecorderHolder',
    'FlightRecorder' => 'behaviors/FlightRecorderHolder',
	'FlightError' => 'behaviors/FlightRecorderHolder',


	/*******************************************************************************************************************
	 * Interfaces
	 ******************************************************************************************************************/
	'ConductorInterface' => 'interfaces',
    'ResponseInterface' => 'interfaces',
    'HtmlRendererInterface' => 'interfaces',
    'HtmlTemplateProviderInterface' => 'interfaces',
    'UserManagerInterface' => 'interfaces',
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
    'JsCompilerExtensionInterafce' => 'interfaces',

    'DbConnectorInterface' => 'interfaces/db',
    'DbConnectionInterface' => 'interfaces/db',

    'CommonFileInterface' => 'interfaces/file',
    'DirectoryInterface' => 'interfaces/file',
    'FileInterface' => 'interfaces/file',
    'DataFileInterface' => 'interfaces/file',

	'ModelInterface' => 'interfaces/model',
    'ModelManagerInterface' => 'interfaces/model',
    'ModelSchemaInterface' => 'interfaces/model',
];
