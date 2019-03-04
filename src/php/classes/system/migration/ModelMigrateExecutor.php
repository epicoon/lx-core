<?php

namespace lx;

/**
 * Используется в качестве исполняющего инструмента by MigrationManager
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
	 * Парсинг текста yaml-схемы модели для поиска директив изменения (изменение структуры, изменение контента)
	 * */
	public function runParseCode() {
		$modelProvider = $this->service->modelProvider;
		$migrationMaker = new MigrationMaker($this->service);

		$this->parseInnerActions();
		if ($modelProvider->checkModelNeedTable($this->modelName)) {
			$this->correctInnerYaml();
			$migrationMaker->createTableMigration($this->modelName, $this->tableName);
			if (!$modelProvider->createTable($this->modelName)) {
				return false;
			}
		} else {
			if (!empty($this->innerActions)) {
				$migrationMaker->createInnerMigration(
					$this->modelName,
					$this->tableName,
					$this->innerActions
				);
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

		$actions = $this->parseOuterActions();
		if (!empty($actions)) {
			$migrationMaker->createOuterMigration(
				$this->modelName,
				$this->tableName,
				$actions
			);

			$correctResult = $modelProvider->correctModelEssences(
				$this->modelName,
				$this->tableName,
				$actions
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
	public function runChangeEssences($actions) {
		$modelProvider = $this->service->modelProvider;
		$migrationMaker = new MigrationMaker($this->service);

		$migrationMaker->createOuterMigration(
			$this->modelName,
			$this->tableName,
			$actions
		);

		return $modelProvider->correctModelEssences(
			$this->modelName,
			$this->tableName,
			$actions
		);
	}

	/**
	 * Корректировки моделей готовым набором директив (только изменение структуры)
	 * */
	public function runCorrectActions($actions) {
		$this->tableName = $this->modelData['table'];
		$this->innerActions = $actions;

		$migrationMaker = new MigrationMaker($this->service);
		$migrationMaker->createInnerMigration(
			$this->modelName,
			$this->tableName,
			$this->innerActions
		);

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
	 * Парсинг непосредственно текста yaml-схемы, если директивы изменения написали прямо в файле
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
	 * Отформатировать Yaml-схему
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
	private function parseOuterActions() {
		if (empty($this->outerActions)) return;

		$list = [];

		/*
		$this->outerActions - ассоциативный массив, примеры:
			!!add:
			  - f0: val1_0
			    f1: val1_1
			  - f0: val2_0
			    f1: val2_1
			!!add:
			  vars:
			    $field1: SomeModel('slug1')->field
			    $field2: SomeModel('slug2')->field + '_' + SomeModel('slug1')->field
			  models:
			    - f0: $field1
			      f1: val1_1
			    - f0: $field2
			      f1: val2_1
			!!addTable: [
			  [ f0,     f1     ],
			  [ val1_0, val1_1 ],
			  [ val2_0, val2_1 ]
			]
			!!addTable:
			  vars:
			    $field1: SomeModel('slug1')->field;
			    $field2: SomeModel('slug2')->field . '_' . SomeModel('slug1')->field;
			  models: [
			    [ f0,      f1     ],
			    [ $field1, val1_1 ],
			    [ $field2, val2_1 ]
			  ]
			!!edit:
			  vars:
			    $slug: someSlug
			    $field: SomeModel('slug1')->field;
			  for:
			    slug: $slug
			  fields:
			    - field: $field
			  for:
			    slug: $slug2
			  fields:
			    - field: $field2
			!!remove:
			  slug: val
			!!remove:
			  vars:
			    $slug: SomeModel('slug1')->field;
			  for:
			    slug: $slug
			!!query: "--SOME SQL QUERY"
		*/
		foreach ($this->outerActions as $actionKey => $actionData) {
			switch ($actionKey) {
				case '!!query':
					$list[] = ['query', $actionData];
					break;
				case '!!add':
					$list[] = ['add', $this->modelName, $this->outerAddModels($actionData)];
					break;
				case '!!addTable':
					$list[] = ['add', $this->modelName, $this->outerAddModelsFromArray($actionData)];
					break;
				case '!!edit':
					$arr = $this->outerEditModels($actionData);
					if ($arr) {
						$list[] = ['edit', $this->modelName, $arr];
					}
					break;
				case '!!remove':
					$list[] = ['del', $this->modelName, $this->outerRemoveModels($actionData)];
					break;
			}
		}

		return $list;
	}

	/**
	 *
	 * */
	private function outerAddModels($__data) {
		if (array_key_exists('models', $__data)) {
			// Создаем переменные
			$__vars = [];
			if (array_key_exists('vars', $__data)) {
				foreach ($__data['vars'] as $varName => $varCode) {
					if (!preg_match('/;$/', $varCode)) $varCode .= ';';
					eval('$__vars[\''. $varName .'\']=' . $varCode);
				}
			}

			// Парсим данные на использование переменных
			$__data = $__data['models'];
			foreach ($__data as $i => &$params) {
				foreach ($params as $param => &$value) {
					if (array_key_exists($value, $__vars)) {
						$value = $__vars[$value];
					}
				}
				unset($value);
			}
			unset($params);
		}

		return $__data;
	}

	/**
	 *
	 * */
	private function outerAddModelsFromArray($data) {
		$parse = function($data) {
			$header = array_shift($data);
			if (is_string($header)) {
				$header = preg_split('/\s*/', $header);
			}
			$result = [];
			foreach ($data as $params) {
				if (is_string($params)) {
					$params = preg_split('/\s*/', $params);
				}
				$row = [];
				foreach ($header as $i => $fieldName) {
					$row[$fieldName] = $params[$i];
				}
				$result[] = $row;
			}
			return $result;
		};

		if (array_key_exists('models', $data)) {
			$data['models'] = $parse($data['models']);
		} else {
			$data = $parse($data);
		}

		return $this->outerAddModels($data);
	}

	/**
	 *
	 * */
	private function outerEditModels($__data) {
		// Создаем переменные
		$__vars = [];
		if (array_key_exists('vars', $__data)) {
			foreach ($__data['vars'] as $__varName => $__varCode) {
				if (!preg_match('/;$/', $__varCode)) $__varCode .= ';';
				eval( $__varName . '=' . $__varCode . '$__vars[\''. $__varName .'\']=' . $__varName . ';' );
			}
		}

		$result = [];
		$pare = [null, null];
		//todo добавить проверок на корректность директивы
		foreach ($__data as $__key => $__value) {
			if ($__key == 'vars') continue;

			// Парсим на использование переменных
			if (is_string($__value)) {
				foreach ($__vars as $key => $value) {
					$__value = str_replace($key, $value, $__value);
				}
			} else {
				foreach ($__value as $i => &$param) {
					if (array_key_exists($param, $__vars)) {
						$param = $__vars[$param];
					}
				}
				unset($params);
			}

			if ($__key == 'for') {
				$pare[0] = $__value;
			} elseif ($__key == 'fields') {
				$pare[1] = $__value;
				$result[] = $pare;
			}
		}

		if (empty($result)) return false;
		return $result;
	}

	/**
	 *
	 * */
	private function outerRemoveModels($__data) {
		if (array_key_exists('for', $__data)) {
			// Создаем переменные
			$__vars = [];
			if (array_key_exists('vars', $__data)) {
				foreach ($__data['vars'] as $varName => $varCode) {
					if (!preg_match('/;$/', $varCode)) $varCode .= ';';
					eval( $varName . '=' . $varCode . '$__vars[\''. $varName .'\']=' . $varName . ';' );
				}
			}

			// Парсим условие на использование переменных
			$__data = $__data['for'];
			if (is_string($__data)) {
				foreach ($__vars as $key => $value) {
					$__data = str_replace($key, $value, $__data);
				}
			} else {
				foreach ($__data as $i => &$param) {
					if (array_key_exists($param, $__vars)) {
						$param = $__vars[$param];
					}
				}
				unset($params);
			}
		}

		$manager = $this->service->modelProvider->getManager($this->modelName);
		$models = $manager->loadModels($__data);
		$data = [];
		foreach ($models as $model) {
			$data[] = $model->getFields();
		}
		return $data;
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
}
