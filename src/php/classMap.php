<?php

return [
    'AbstractApplication' => 'classes/app',
    'ConsoleApplication' => 'classes/app',
    'HttpApplication' => 'classes/app',

    'CssManager' => 'classes/appComponents/asset',
    'WebAssetHelper' => 'classes/appComponents/asset',
    'AppAssetCompiler' => 'classes/appComponents/asset',
    'MainCssCompiler' => 'classes/appComponents/asset',
    'ModuleCssCompiler' => 'classes/appComponents/asset',

    'ApplicationComponents' => 'classes/appComponents',
    'ApplicationLogger' => 'classes/appComponents',
    'DbConnector' => 'classes/appComponents/db',
    'DbConnection' => 'classes/appComponents/db/connection',
    'DbConnectionFactory' => 'classes/appComponents/db/connection',
    'DbConnectionRegistry' => 'classes/appComponents/db/connection',
    'DbQueryBuilder' => 'classes/appComponents/db/connection',
    'PostgresConnection' => 'classes/appComponents/db/connection/postgresql',
    'PostgresQueryBuilder' => 'classes/appComponents/db/connection/postgresql',
    'MysqlConnection' => 'classes/appComponents/db/connection/mysql',
    'MysqlQueryBuilder' => 'classes/appComponents/db/connection/mysql',
    'DbQueryFieldData' => 'classes/appComponents/db/query',
    'DbQueryTableData' => 'classes/appComponents/db/query',
    'DbSelectParser' => 'classes/appComponents/db/query',
    'DbSelectQuery' => 'classes/appComponents/db/query',
    'DbForeignKeyInfo' => 'classes/appComponents/db/table',
    'DbTable' => 'classes/appComponents/db/table',
    'DbTableEditor' => 'classes/appComponents/db/table',
    'DbTableField' => 'classes/appComponents/db/table',
    'DbTableSchema' => 'classes/appComponents/db/table',
    'Cookie' => 'classes/appComponents/dialog',
    'CorsProcessor' => 'classes/appComponents/dialog',
    'HttpRequest' => 'classes/appComponents/dialog',
    'HttpResponse' => 'classes/appComponents/dialog',
    'Router' => 'classes/appComponents/dialog',
    'EventListenerInterface' => 'classes/appComponents/event',
    'EventListenerTrait' => 'classes/appComponents/event',
    'EventManager' => 'classes/appComponents/event',
    'EventManagerInterface' => 'classes/appComponents/event',
    'Event' => 'classes/appComponents/event',
    'ApplicationI18nMap' => 'classes/appComponents/i18n',
    'I18nHelper' => 'classes/appComponents/i18n',
    'I18nMap' => 'classes/appComponents/i18n',
    'Language' => 'classes/appComponents/i18n',
    'PluginI18nMap' => 'classes/appComponents/i18n',
    'ServiceI18nMap' => 'classes/appComponents/i18n',
    'AbstractJsModuleInjector' => 'classes/appComponents/jsModuleInject',
    'ApplicationJsModuleInjector' => 'classes/appComponents/jsModuleInject',
    'PluginJsModuleInjector' => 'classes/appComponents/jsModuleInject',
    'ServiceJsModuleInjector' => 'classes/appComponents/jsModuleInject',
    'DevApplicationLifeCycleHandler' => 'classes/appComponents/lifeCycle',
    'User' => 'classes/appComponents/user',
    'UserEventsEnum' => 'classes/appComponents/user',

    'Console' => 'classes/console',
    'Cli' => 'classes/console/cli',
    'CliCommand' => 'classes/console/cli',
    'CliCommandsList' => 'classes/console/cli',
    'CliProcessor' => 'classes/console/cli',
    'ServiceCliExecutor' => 'classes/console/cli',
    'AbstractConsoleInput' => 'classes/console/input',
    'ConsoleInput' => 'classes/console/input',
    'ConsoleSelect' => 'classes/console/input',
    'AbstractCommand' => 'classes/console/command',
    'CommandArgument' => 'classes/console/command',
    'CommandArgumentsList' => 'classes/console/command',
    'ConsoleResourceContext' => 'classes/console/native',
    'ConsoleRouter' => 'classes/console/native',
    'DefaultCommand' => 'classes/console/native',
    'NativeCommand' => 'classes/console/native',

    'DbException' => 'classes/exceptions',

    'BaseFile' => 'classes/file',
    'DataFile' => 'classes/file',
    'Directory' => 'classes/file',
    'File' => 'classes/file',
    'FileHelper' => 'classes/file',
    'FileLink' => 'classes/file',
    'DataFileAdapter' => 'classes/file/dataFileAdapter',
    'JsonDataFileAdapter' => 'classes/file/dataFileAdapter',
    'PhpDataFileAdapter' => 'classes/file/dataFileAdapter',
    'YamlDataFileAdapter' => 'classes/file/dataFileAdapter',

    'ArrayHelper' => 'classes/helpers',
    'ClassHelper' => 'classes/helpers',
    'StringHelper' => 'classes/helpers',

    'JsCompileDependencies' => 'classes/jsTools/compiler',
    'JsCompiler' => 'classes/jsTools/compiler',
    'JsCompilerExtension' => 'classes/jsTools/compiler',
    'Minimizer' => 'classes/jsTools/compiler',
    'NodeJsExecutor' => 'classes/jsTools/compiler',
    'PluginFrontendJsCompiler' => 'classes/jsTools/compiler',
    'SyntaxExtender' => 'classes/jsTools/compiler',
    'JsModulesActualizer' => 'classes/jsTools/module',
    'JsModuleProvider' => 'classes/jsTools/module',
    'JsModulesComponent' => 'classes/jsTools/module',
    'JsModulesConductor' => 'classes/jsTools/module',
    'DocSplitter' => 'classes/jsTools/module/docParser',
    'ModuleDocParser' => 'classes/jsTools/module/docParser',
    'JsClassDocumentation' => 'classes/jsTools/module/info',
    'JsLinkDocumentation' => 'classes/jsTools/module/info',
    'JsMethodDocumentation' => 'classes/jsTools/module/info',
    'JsModuleInfo' => 'classes/jsTools/module/info',
    'JsParamDocumentation' => 'classes/jsTools/module/info',
    'JsTypeDocumentation' => 'classes/jsTools/module/info',

    'Plugin' => 'classes/plugin',
    'PluginConductor' => 'classes/plugin',
    'PluginDirectory' => 'classes/plugin',
    'Respondent' => 'classes/plugin',

    'Service' => 'classes/service',
    'ServiceConductor' => 'classes/service',
    'ServiceController' => 'classes/service',
    'ServiceDirectory' => 'classes/service',
    'ServiceRouter' => 'classes/service',

    'ApplicationConductor' => 'classes/sys/app',
    'DevLogger' => 'classes/sys/app',
    'Autoloader' => 'classes/sys/autoload',
    'AutoloadMap' => 'classes/sys/autoload',
    'AutoloadMapBuilder' => 'classes/sys/autoload',
    'DependencyBuilder' => 'classes/sys/di',
    'DependencyProcessor' => 'classes/sys/di',
    'AbstractResourceVoter' => 'classes/sys/dialog',
    'Module' => 'classes/sys/dialog',
    'Resource' => 'classes/sys/dialog',
    'ResourceContext' => 'classes/sys/dialog',
    'SpecialAjaxRouter' => 'classes/sys/dialog',
    'AjaxRequestHandler' => 'classes/sys/dialog/requestHandler',
    'CommonRequestHandler' => 'classes/sys/dialog/requestHandler',
    'CorsRequestHandler' => 'classes/sys/dialog/requestHandler',
    'PageRequestHandler' => 'classes/sys/dialog/requestHandler',
    'RequestHandler' => 'classes/sys/dialog/requestHandler',
    'PluginEditor' => 'classes/sys/editor',
    'ServiceEditor' => 'classes/sys/editor',
    'CodeConverterHelper' => 'classes/sys/helpers',
    'ConfigHelper' => 'classes/sys/helpers',
    'ErrorHelper' => 'classes/sys/helpers',
    'HtmlBody' => 'classes/sys/html',
    'HtmlHead' => 'classes/sys/html',
    'HtmlHelper' => 'classes/sys/html',
    'HtmlRenderer' => 'classes/sys/html',
    'HtmlTemplateProvider' => 'classes/sys/html',
    'JsScriptAsset' => 'classes/sys/plugin',
    'PluginBrowser' => 'classes/sys/plugin',
    'PluginProvider' => 'classes/sys/plugin',
    'PluginCssCompiler' => 'classes/sys/plugin/build',
    'PluginBuildContext' => 'classes/sys/plugin/build',
    'Snippet' => 'classes/sys/plugin/build',
    'SnippetBuildContext' => 'classes/sys/plugin/build',
    'SnippetCacheData' => 'classes/sys/plugin/build',
    'PluginCacheManager' => 'classes/sys/plugin/build',
    'PluginAssetProvider' => 'classes/sys/plugin/build',
    'ServiceBrowser' => 'classes/sys/service',
    'ServiceProvider' => 'classes/sys/service',
    'ServicesMap' => 'classes/sys/service',

    'CascadeReport' => 'classes/tools',
    'CurlRequest' => 'classes/tools',
    'DataObject' => 'classes/tools',
    'Math' => 'classes/tools',
    'Undefined' => 'classes/tools',
    'Vector' => 'classes/tools',
    'Yaml' => 'classes/tools',
    'MdBlocksBuilder' => 'classes/tools/mdConverter',
    'MdBlockTypeEnum' => 'classes/tools/mdConverter',
    'MdConverter' => 'classes/tools/mdConverter',
    'MdParser' => 'classes/tools/mdConverter',
    'MdRenderer' => 'classes/tools/mdConverter',


    /*******************************************************************************************************************
     * Modules
     ******************************************************************************************************************/

    'WebCli' => '../modules/webCli',


	/*******************************************************************************************************************
	 * Behaviors
	 ******************************************************************************************************************/

    'ObjectReestr' => 'behaviors/Object',
    'ObjectInterface' => 'behaviors/Object',
    'ObjectTrait' => 'behaviors/Object',

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
    'JsModuleClientInterface' => 'interfaces',
    'ClientComponentInterface' => 'interfaces',

	'ConductorInterface' => 'interfaces',
    'RouterInterface' => 'interfaces',
    'ResourceContextInterface' => 'interfaces',
    'HttpResponseInterface' => 'interfaces',
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
    'JsModuleInjectorInterface' => 'interfaces',
    'CssManagerInterface' => 'interfaces',

    'ApplicationLifeCycleInterface' => 'interfaces/lifeCycle',
    'HttpApplicationLifeCycleInterface' => 'interfaces/lifeCycle',

    'DbConnectorInterface' => 'interfaces/db',
    'DbConnectionFactoryInterface' => 'interfaces/db',
    'DbConnectionInterface' => 'interfaces/db',
    'DbQueryBuilderInterface' => 'interfaces/db',

    'CommonFileInterface' => 'interfaces/file',
    'DirectoryInterface' => 'interfaces/file',
    'FileInterface' => 'interfaces/file',
    'FileLinkInterface' => 'interfaces/file',
    'DataFileInterface' => 'interfaces/file',

	'ModelInterface' => 'interfaces/model',
    'ModelManagerInterface' => 'interfaces/model',
    'ModelSchemaInterface' => 'interfaces/model',

    'CommandInterface' => 'interfaces/console',
    'CommandExecutorInterface' => 'interfaces/console',
];
