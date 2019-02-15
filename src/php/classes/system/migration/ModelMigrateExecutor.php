<?php

namespace lx;

/**
 * Используется в качестве исполняющего инструмента by MigrationManager
 * //todo - отсюда вопрос - может в тот файл и засунуть? Нигде вроде больше этот класс не нужен?
 * */
class ModelMigrateExecutor {
	private $service;
	private $path;
	private $code;
	private $modelName;
	private $modelData;
	private $tableName;

	// Действия со схемой
	private $innerActions;
	
	// Действия с экземплярами моделей
	private $outerActions;

	/*************************************************************************************************************************
	 * PUBLIC
	 *************************************************************************************************************************/

	/**
	 *
	 * */
	public static function runMigration($service, $migration) {
		$modelProvider = $service->modelProvider;
		switch ($migration['type']) {
			case 'table_create':
				$name = $migration['model'];
				$schema = new ModelSchema($name, $migration['schema']);
				if (!$modelProvider->createTable($name, $schema)) {
					return false;
				}
				break;
			case 'table_alter':
				$correctResult = $modelProvider->correctModel(
					$migration['model'],
					$migration['table_name'],
					$migration['actions']
				);
				if (!$correctResult) {
					return false;
				}
				break;
			case 'table_content':
				$correctResult = $modelProvider->addModelEssences(
					$migration['model'],
					$migration['table_name'],
					$migration['actions']
				);
				if (!$correctResult) {
					return false;
				}
				break;
		}

		return true;
	}

	/**
	 *
	 * */
	public function __construct($service, $modelName, $modelArray=null, $actions=null, $path=null, $code=null) {
		$this->service = $service;
		$this->modelName = $modelName;

		if ($path === null) {
			$path = $service->conductor->getModelPath($modelName);
		}
		if ($code === null) {
			$code = (new File($path))->get();
		}
		if ($modelArray === null) {
			$modelData = (new Yaml($code, dirname($path)))->parse();
			$modelArray = $modelData[$modelName];
			// Остаются команды добавления/удаления/запросов
			unset($modelData[$modelName]);
		}
		if ($actions === null) {
			$actions = isset($modelData) ? $modelData : [];
		}

		$this->modelData = $modelArray;
		$this->outerActions = $actions;
		$this->path = $path;
		$this->code = $code;

		$this->innerActions = [];
	}

	/**
	 * Для запуска парсинга yaml-кода модели
	 * */
	public function runParseCode() {
		$this->parseInnerActions();
		$modelProvider = $this->service->modelProvider;

		if ($modelProvider->checkModelNeedTable($this->modelName)) {
			$this->correctInnerYaml();
			$this->createTableMigration();
			if (!$modelProvider->createTable($this->modelName)) {
				return false;
			}
		} else {
			if (!empty($this->innerActions)) {
				$this->createInnerMigration();
				$correctResult = $modelProvider->correctModel(
					$this->modelName,
					$this->tableName,
					$this->innerActions
				);
				if (!$correctResult) {
					return false;
				}
				$this->correctInnerYaml();
				$this->tableName = $correctResult;
			}
		}

		if (!empty($this->outerActions)) {
			$this->createOuterMigration();
			$correctResult = $modelProvider->addModelEssences(
				$this->modelName,
				$this->tableName,
				$this->outerActions
			);
			if (!$correctResult) {
				return false;
			}
			$this->correctOuterYaml();
		}

		return true;
	}

	/**
	 *
	 * */
	public function runCorrectActions($actions) {
		$this->tableName = $this->modelData['table'];
		$this->innerActions = $actions;
		$this->createInnerMigration();

		$modelProvider = $this->service->modelProvider;
		$correctResult = $modelProvider->correctModel(
			$this->modelName,
			$this->tableName,
			$this->innerActions
		);
		if (!$correctResult) {
			return false;
		}

		// Правка yaml-кода
		//todo - перенести отсюда
		$code = $this->code;
		foreach ($actions as $action) {
			$type = $action['action'];
			switch ($type) {
				case 'renameTable':
					$code = preg_replace('/  (table: *)'.$action['old'].'/', '$1'.$action['new'], $code);
					break;
				case 'addField':
					$arr = [];
					foreach ($action['params'] as $property => $value) {
						$arr[] = "$property: $value";
					}
					$fieldStr = '    ' . $action['name'] . ': { ' . implode(', ', $arr) . ' }';
					$code = preg_replace('/(fields:[\w\W]*?)([\r\n]+?  \w|$)/', '$1'.PHP_EOL.$fieldStr.'$2', $code);
					break;
				case 'removeField':
					$code = preg_replace('/(fields:[\w\W]*?)    '.$action['name'].'.*?[\r\n]+?( |$)/', '$1$2', $code);
					break;
				case 'renameField':
					$code = preg_replace('/(fields:[\w\W]*?    )'.$action['old'].'/', '$1'.$action['new'], $code);
					break;
				case 'changeFieldProperty':
					$code = preg_replace('/(fields:[\w\W]*?    '.$action['fieldName'].'[\w\W]*?'.$action['property'].': *)'.$action['old'].'/',
						'$1'.$action['new'], $code);
					break;
			}
		}
		$file = new File($this->path);
		$file->put($code);

		return true;
	}


	/*************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/

	/**
	 * Собираем инфу - что делать со схемой
	 * */
	private function parseInnerActions() {
		// Проверка на переименование таблицы
		$tableName = $this->modelData['table'];
		if ((preg_match('/!!change/', $tableName))) {
			$arr = preg_split('/\s+!!change\s+/', $tableName);
			$this->innerActions[] = [
				'action' => 'renameTable',
				'currentTableName' => $tableName,
				'old' => $arr[0],
				'new' => $arr[1]
			];
			$tableName = $arr[0];
		}
		$this->tableName = $tableName;

		// Проверки всех изменений полей
		foreach ($this->modelData['fields'] as $name => $field) {
			$realName = $name;

			// Проверка на переименование поля
			if (preg_match('/!!change/', $name)) {
				$arr = preg_split('/\s+!!change\s+/', $name);
				$this->innerActions[] = [
					'action' => 'renameField',
					'currentFieldName' => $name,
					'old' => $arr[0],
					'new' => $arr[1]
				];
				$realName = $arr[1];
			}

			// Проверка на добавление поля
			if (preg_match('/!!add/', $name)) {
				$arr = preg_split('/\s*!!add\s*/', $name);
				$realName = $arr[0] ? $arr[0] : $arr[1];
				$this->innerActions[] = [
					'action' => 'addField',
					'currentFieldName' => $name,
					'name' => $realName,
					'params' => $field
				];
			}

			// Проверка на удаление поля
			if (preg_match('/!!remove/', $name)) {
				$arr = preg_split('/\s*!!remove\s*/', $name);
				$realName = $arr[0] ? $arr[0] : $arr[1];
				$this->innerActions[] = [
					'action' => 'removeField',
					'currentFieldName' => $name,
					'name' => $realName
				];

				// Если поле удаляется - параметры уже не важны
				continue;
			}

			// Проверки изменений параметров полей
			foreach ($field as $propName => $propValue) {
				if (!is_string($propValue)) continue;

				if (preg_match('/!!change/', $propValue)) {
					$arr = preg_split('/\s+!!change\s+/', $propValue);
					$this->innerActions[] = [
						'action' => 'changeFieldProperty',
						'currentFieldName' => $name,
						'fieldName' => $realName,
						'property' => $propName,
						'old' => $arr[0],
						'new' => $arr[1]
					];
				}
			}
		}
	}

	/**
	 * Отформатировать Yaml-конфиг
	 * */
	private function correctInnerYaml() {
		if (empty($this->innerActions)) {
			return;
		}

		$code = $this->code;
		
		foreach ($this->innerActions as $actionData) {
			$action = 'yaml_' . $actionData['action'];
			if (method_exists($this, $action)) {
				$this->{$action}($actionData, $code);
			}
		}

		$this->code = $code;

		$file = new File($this->path);
		$file->put($code);
	}

	/**
	 *
	 * */
	private function yaml_renameTable($data, &$code) {
		$reg = '/(table:\s*)' . $data['currentTableName'] . '/';
		$code = preg_replace($reg, '$1' . $data['new'], $code);
	}

	/**
	 *
	 * */
	private function yaml_renameField($data, &$code) {
		$reg = '/(\s)' . $data['currentFieldName'] . '(\s*:)/';
		$code = preg_replace($reg, '$1' . $data['new'] . '$2', $code);
	}

	/**
	 *
	 * */
	private function yaml_addField($data, &$code) {
		$reg = '/(\s)' . $data['currentFieldName'] . '(\s*:)/';
		$code = preg_replace($reg, '$1' . $data['name'] . '$2', $code);
	}

	/**
	 *
	 * */
	private function yaml_removeField($data, &$code) {
		$fieldNames = array_keys($this->modelData['fields']);

		$i = 0;
		while ($fieldNames[$i] != $data['currentFieldName']) $i++;

		if ($i == count($fieldNames) - 1) {
			$regFace = '\n\s+';
			$regTail = '(\n$|\n\n|\n[^\n])';
		} else {
			$regFace = '';
			$regTail = '(' . $fieldNames[$i + 1] . ':)';
		}

		$reg = '/' . $regFace . $data['currentFieldName'] . '[\w\W]+?' . $regTail . '/';
		$code = preg_replace($reg, '$1', $code);
	}

	/**
	 *
	 * */
	private function correctOuterYaml() {
		$code = $this->code;

		if (!empty($this->outerActions)) {
			$code = preg_replace('/!!(add|remove|query)[\w\W]*$/', '', $code);
		}

		$file = new File($this->path);
		$file->put($code);
	}

	/**
	 * Сгенерировать миграции, отметить их выполненными
	 * */
	private function createTableMigration() {
		$migrationData = [
			'type' => 'table_create',
			'model' => $this->modelName,
			'schema' => $this->service->modelProvider->getSchemaArray($this->modelName),
		];
		$namePrefix = 'm_new_table';
		$this->saveMigration($namePrefix, $migrationData);
	}

	/**
	 * Сгенерировать миграции, отметить их выполненными
	 * */
	private function createInnerMigration() {
		$migrationData = [
			'type' => 'table_alter',
			'model' => $this->modelName,
			'table_name' => $this->tableName,
			'actions' => $this->innerActions,
		];
		$namePrefix = 'm_alter_table';
		$this->saveMigration($namePrefix, $migrationData);
	}

	/**
	 * Сгенерировать миграции, отметить их выполненными
	 * */
	private function createOuterMigration() {
		$migrationData = [
			'type' => 'table_content',
			'model' => $this->modelName,
			'table_name' => $this->tableName,
			'actions' => $this->outerActions,
		];
		$namePrefix = 'm_content_table';
		$this->saveMigration($namePrefix, $migrationData);
	}

	/**
	 * Создание файла миграции
	 * */
	private function saveMigration($namePrefix, $migrationData) {
		$time = explode(' ', microtime());
		$time = $time[1] . '_' . $time[0];
		$migrationFileName = $namePrefix .'__'. $this->tableName .'_' . $time . '.json';

		$dir = $this->service->conductor->getMigrationDirectory();
		$file = $dir->makeFile($migrationFileName);
		$code = json_encode($migrationData);
		$file->put($code);

		$mapFile = $dir->contain('map.json')
			? $dir->get('map.json')
			: $dir->makeFile('map.json');
		$map = $mapFile->exists()
			? json_decode($mapFile->get(), true)
			: ['list' => []];
		$map['list'][] = [
			'applied' => true,
			'time' => $time,
			'name' => $migrationFileName,
		];
		$mapFile->put(json_encode($map));
	}
}
