<?php
return [
	'AutoloadMapBuilder' => 'classes/system/autoload',
	'AutoloadMap' => 'classes/system/autoload',

	'Conductor' => 'classes/system',
	'JsCompiler' => 'classes/system/JsCompiler',
	'Dialog' => 'classes/system',
	'Router' => 'classes/system',
	'Respondent' => 'classes/system',
	'Console' => 'classes/system',
	'ModuleEditor' => 'classes/system/editor',
	'ServiceEditor' => 'classes/system/editor',

	'PackageDirectory' => 'classes/package',
	'PackageBrowser' => 'classes/package',
	'ServicesMap' => 'classes/package',
	'Service' => 'classes/package',
	'ServiceRouter' => 'classes/package',
	'ServiceController' => 'classes/package',
	'ServiceConductor' => 'classes/package',
	'ServiceResponse' => 'classes/package/response',
	'RenderServiceResponse' => 'classes/package/response',

	'ModuleDirectory' => 'classes/module',
	'ModuleBrowser' => 'classes/module',
	'Block' => 'classes/module',
	'Module' => 'classes/module',
	'ModuleBuilder' => 'classes/module',
	'ModuleConductor' => 'classes/module',
	'Renderer' => 'classes/module',

	'Model' => 'classes/model',
	'ModelBrowser' => 'classes/model',
	'ModelData' => 'classes/model',
	'ModelManager' => 'classes/model',
	'ModelProvider' => 'classes/model',
	'ModelSchema' => 'classes/model',
	'ModelField' => 'classes/model/field',
	'ModelFieldString' => 'classes/model/field',
	'ModelFieldInteger' => 'classes/model/field',
	'ModelFieldBoolean' => 'classes/model/field',
	'CrudAdapter' => 'classes/model/CRUD',
	'DbCrudAdapter' => 'classes/model/CRUD',

	'MigrationManager' => 'classes/system/migration',
	'ModelMigrateExecutor' => 'classes/system/migration',
	'MigrationMaker' => 'classes/system/migration',

	'DataObject' => 'classes/dataClasses',

	'DbColumnDefinition' => 'classes/db',
	'DbTableSchemaProvider' => 'classes/db',
	'DbTable' => 'classes/db',
	'DbRecord' => 'classes/db',
	'DB' => 'classes/db',
	'DBpostgres' => 'classes/db',
	'DBmysql' => 'classes/db',

	'Request' => 'classes/tools',
	'Vector' => 'classes/tools',
	'Collection' => 'classes/tools',
	'Tree' => 'classes/tools',

	'BaseFile' => 'classes/file',
	'File' => 'classes/file',
	'Directory' => 'classes/file',
	'YamlFile' => 'classes/file',
	'HtmpFile' => 'classes/file',
	'ConfigFile' => 'classes/file',

	'Cli' => 'classes/system/cli',
	'CliProcessor' => 'classes/system/cli',

	'ClassHelper' => 'classes/helpers',
	'Math' => 'classes/helpers',
	'ArrayHelper' => 'classes/helpers',
	'Geom' => 'classes/helpers',
	'Yaml' => 'classes/helpers',
	'Htmp' => 'classes/helpers',
	'WidgetHelper' => 'classes/helpers',

	'IndentData' => '../widgets/Box/positioningStrategiesPhp',
	'PositioningStrategy' => '../widgets/Box/positioningStrategiesPhp',
	'SimplePositioningStrategy' => '../widgets/Box/positioningStrategiesPhp',
	'AlignPositioningStrategy' => '../widgets/Box/positioningStrategiesPhp',
	'StreamPositioningStrategy' => '../widgets/Box/positioningStrategiesPhp',
	'GridPositioningStrategy' => '../widgets/Box/positioningStrategiesPhp',
	'SlotPositioningStrategy' => '../widgets/Box/positioningStrategiesPhp',
];