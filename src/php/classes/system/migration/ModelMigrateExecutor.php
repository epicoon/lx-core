<?php

namespace lx;

/**
 * Используется в качестве исполняющего инструмента by MigrationManager
 * */
class ModelMigrateExecutor {
	const ACTION_ADD_FIELD = 'addField';
	const ACTION_REMOVE_FIELD = 'removeField';
	const ACTION_RENAME_FIELD = 'renameField';
	const ACTION_CHANGE_FIELD_PROPERTY = 'changeFieldProperty';

	private $service;
	private $path;
	private $code;
	private $modelName;
	private $modelData;

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
		$crudAdapter = $this->service->modelProvider->getCrudAdapter();
		// Если нет CRUD адаптера, то не нужно миграций
		if ( ! $crudAdapter) {
			return true;
		}

		$migrationMaker = new MigrationMaker($this->service);

		$this->parseInnerActions();
		$needTable = $crudAdapter->checkNeedTable($this->modelName);
		if ($needTable) {
			$this->correctInnerYaml();
			if ( ! $crudAdapter->createTable($this->modelName)) {
				return false;
			} else {
				$migrationMaker->createTableMigration($this->modelName);
			}
		}

		list ($addedRelations, $deletedRelations) = $crudAdapter->acualizeRelationTables($this->modelName);
		if ( ! empty($addedRelations)) {
			$migrationMaker->createRelationTablesMigration($this->modelName, $addedRelations);
		}
		if ( ! empty($deletedRelations)) {
			$migrationMaker->deleteRelationTablesMigration($this->modelName, $deletedRelations);
		}

		if ( ! $needTable) {
			if ( ! empty($this->innerActions)) {
				if ( ! $crudAdapter->correctModel(
					$this->modelName,
					$this->innerActions
				)) {
					return false;
				}

				$this->correctInnerYaml();
				$migrationMaker->createInnerMigration(
					$this->modelName,
					$this->innerActions
				);
			}
		}

		$essencesChanged = $this->runChangeEssences($this->parseOuterActions());
		if ($essencesChanged) {
			$this->correctOuterYaml();
		}

		return $essencesChanged;

//		$actions = $this->parseOuterActions();
//		if (!empty($actions)) {
//			if ( ! $crudAdapter->correctModelEssences(
//				$this->modelName,
//				$actions
//			)) {
//				return false;
//			}
//
//			$migrationMaker->createOuterMigration(
//				$this->modelName,
//				$actions
//			);
//			$this->correctOuterYaml();
//		}
//
//		return true;
	}

	/**
	 *
	 * */
	public function runChangeEssences($actions) {
		if (empty($actions)) {
			return false;
		}

		$crudAdapter = $this->service->modelProvider->getCrudAdapter();
		// Если нет CRUD адаптера, то не нужно миграций
		if ( ! $crudAdapter) {
			return true;
		}

		if ($crudAdapter->correctModelEssences($this->modelName, $actions)) {
			$migrationMaker = new MigrationMaker($this->service);
			$migrationMaker->createOuterMigration($this->modelName, $actions);
			return true;
		}

		return false;
	}

	/**
	 * Корректировки моделей готовым набором директив (только изменение структуры)
	 * */
	public function runCorrectActions($actions) {
		$crudAdapter = $this->service->modelProvider->getCrudAdapter();
		// Если нет CRUD адаптера, то не нужно миграций
		if ( ! $crudAdapter) {
			return true;
		}

		$this->innerActions = $actions;
		if ( ! $crudAdapter->correctModel($this->modelName, $this->innerActions)) {
			return false;
		}

		$migrationMaker = new MigrationMaker($this->service);
		$migrationMaker->createInnerMigration($this->modelName, $this->innerActions);

		// Правка yaml-кода
		//todo - перенести отсюда
		$code = $this->code;
		foreach ($actions as $action) {
			$type = $action['action'];
			switch ($type) {
				case self::ACTION_ADD_FIELD:
					if ($action['category'] == 'fields') {
						$arr = [];
						foreach ($action['params'] as $property => $value) {
							$arr[] = "$property: $value";
						}
						$fieldStr = '    ' . $action['name'] . ': { ' . implode(', ', $arr) . ' }';
						$code = preg_replace('/(fields:[\w\W]*?)([\r\n]+?  \w|$)/', '$1'.PHP_EOL.$fieldStr.'$2', $code);
					} else {
						//TODO!!!!!!!!!!!!!!!!!!!!!!!!!!
						// тут relations
					}
					break;

				//todo - неверно, поле может быть описано несколькими строками, надо отталкиваться от количества пробелов
				case self::ACTION_REMOVE_FIELD:
					$category = $action['category'];
					$code = preg_replace('/(' . $category . ':[\w\W]*?)    '.$action['name'].'.*?[\r\n]+?( |$)/', '$1$2', $code);
					break;

				case self::ACTION_RENAME_FIELD:
					$category = $action['category'];
					$code = preg_replace('/(' . $category . ':[\w\W]*?    )'.$action['old'].'/', '$1'.$action['new'], $code);
					break;

				case self::ACTION_CHANGE_FIELD_PROPERTY:
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
		// Проверки всех изменений полей
		foreach ($this->modelData['fields'] as $name => $field) {
			// Проверка на удаление поля
			if (preg_match('/!!remove/', $name)) {
				$this->parseInnerRemove('fields', $name);
				// Если поле удаляется - параметры уже не важны
				continue;
			}

			$realName = $name;

			// Проверка на переименование поля
			if (preg_match('/!!change/', $name)) {
				$realName = $this->parseInnerChange('fields', $name);
			}

			// Проверка на добавление поля
			if (preg_match('/!!add/', $name)) {
				$realName = $this->parseInnerAdd('fields', $name, $field);
			}

			// Проверки изменений параметров полей
			foreach ($field as $propName => $propValue) {
				if (!is_string($propValue)) continue;

				if (preg_match('/!!change/', $propValue)) {
					$arr = preg_split('/\s+!!change\s+/', $propValue);
					$this->innerActions[] = [
						'action' => self::ACTION_CHANGE_FIELD_PROPERTY,
						'currentName' => $name,
						'fieldName' => $realName,
						'property' => $propName,
						'old' => $arr[0],
						'new' => $arr[1]
					];
				}
			}
		}

		if (isset($this->modelData['relations']) && is_array($this->modelData['relations'])) {
			foreach ($this->modelData['relations'] as $name => $relation) {
				// Проверка на удаление связи
				if (preg_match('/!!remove/', $name)) {
					$this->parseInnerRemove('relations', $name);
					continue;
				}

			}
		}
	}

	private function parseInnerRemove($category, $name) {
		$arr = preg_split('/\s*!!remove\s*/', $name);
		$realName = $arr[0] ? $arr[0] : $arr[1];
		$this->innerActions[] = [
			'category' => $category,
			'action' => self::ACTION_REMOVE_FIELD,
			'currentName' => $name,
			'name' => $realName,
			'params' => $this->modelData[$category][$name],
		];
	}

	private function parseInnerChange($category, $name) {
		$arr = preg_split('/\s+!!change\s+/', $name);
		$this->innerActions[] = [
			'category' => $category,
			'action' => self::ACTION_RENAME_FIELD,
			'currentName' => $name,
			'old' => $arr[0],
			'new' => $arr[1],
		];
		return $arr[1];
	}

	private function parseInnerAdd($category, $name, $field) {		
		$arr = preg_split('/\s*!!add\s*/', $name);
		$realName = $arr[0] ? $arr[0] : $arr[1];
		$this->innerActions[] = [
			'category' => $category,
			'action' => self::ACTION_ADD_FIELD,
			'currentName' => $name,
			'name' => $realName,
			'params' => $field,
		];
		return $realName;
	}

	/**
	 * Отформатировать Yaml-схему
	 * */
	private function correctInnerYaml() {
		if (empty($this->innerActions)) {
			return;
		}

		$code = $this->code;
		
		foreach ($this->innerActions as &$actionData) {
			$action = 'yaml_' . $actionData['action'];
			if (method_exists($this, $action)) {
				$this->{$action}($actionData, $code);
			}
		}
		unset($actionData);

		$this->code = $code;

		$file = new File($this->path);
		$file->put($code);
	}

	/**
	 *
	 * */
	private function yaml_renameField(&$data, &$code) {
		$reg = '/(\s)' . $data['currentName'] . '(\s*:)/';
		$code = preg_replace($reg, '$1' . $data['new'] . '$2', $code);
		unset($data['currentName']);
	}

	/**
	 *
	 * */
	private function yaml_addField(&$data, &$code) {
		$reg = '/(\s)' . $data['currentName'] . '(\s*:)/';
		$code = preg_replace($reg, '$1' . $data['name'] . '$2', $code);
		unset($data['currentName']);
	}

	/**
	 *
	 * */
	private function yaml_removeField(&$data, &$code) {
		$itemNames = array_keys($this->modelData[$data['category']]);

		$i = 0;
		while ($itemNames[$i] != $data['currentName']) $i++;

		if ($i == count($itemNames) - 1) {
			$regFace = '\n\s+';
			$regTail = '(\n$|$|\n\n|\n[^\n])';
		} else {
			$regFace = '';
			$regTail = '(' . $itemNames[$i + 1] . ':)';
		}

		$reg = '/' . $regFace . $data['currentName'] . '[\w\W]+?' . $regTail . '/';
		$code = preg_replace($reg, '$1', $code);
		unset($data['currentName']);
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
			  [ f0,     f1     ]
			  [ val1_0, val1_1 ]
			  [ val2_0, val2_1 ]
			]
			!!addTable:
			  vars:
			    $field1: SomeModel('slug1')->field;
			    $field2: SomeModel('slug2')->field . '_' . SomeModel('slug1')->field;
			  models: [
			    [ f0,      f1     ]
			    [ $field1, val1_1 ]
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
					$list[] = ['add', $this->outerAddModels($actionData)];
					break;
				case '!!addTable':
					$list[] = ['add', $this->outerAddModelsFromArray($actionData)];
					break;
				case '!!edit':
					$arr = $this->outerEditModels($actionData);
					if ($arr) {
						$list[] = ['edit', $arr];
					}
					break;
				case '!!remove':
					$list[] = ['del', $this->outerRemoveModels($actionData)];
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
			}

			//????????????????????????? было внутри последнего условия. Бред же? Проверить
			$result[] = $pare;
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
