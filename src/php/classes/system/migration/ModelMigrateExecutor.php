<?php

namespace lx;

/**
 * Используется в качестве исполняющего инструмента by MigrationManager
 * Class ModelMigrateExecutor
 * @package lx
 */
class ModelMigrateExecutor
{
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
     * ModelMigrateExecutor constructor.
     * @param $service Service
     * @param $modelName string
     * @param $modelArray null|array
     * @param $actions null|array
     * @param $path null|string
     * @param $code null|string
     */
	public function __construct($service, $modelName, $modelArray=null, $actions=null, $path=null, $code=null)
    {
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
	 */
	public function runParseCode()
    {
		$crudAdapter = $this->service->modelProvider->getCrudAdapter();
		// Если нет CRUD адаптера, то не нужно миграций
		if ( ! $crudAdapter) {
			return true;
		}

		$migrationMaker = new MigrationMaker($this->service);

		$changes = $crudAdapter->checkModelChanges($this->modelName, $this->modelData);
		foreach ($changes as $change) {
		    switch ($change['type']) {
                case MigrationMaker::TYPE_NEW_TABLE:
                    if ( ! $crudAdapter->createTable($this->modelName)) return false;
                    $migrationMaker->createTableMigration($this->modelName, $this->modelData);
                    break;

                case MigrationMaker::TYPE_ALTER_TABLE:
                    if ( ! $crudAdapter->correctModel($this->modelName, $change['info'])) return false;
                    $migrationMaker->createFieldsMigration($this->modelName, $change['info']);
                    break;

                case MigrationMaker::TYPE_RELATIONS_TABLE:
                    if ( ! $crudAdapter->correctRelations($this->modelName, $change['info'])) return false;
                    $migrationMaker->createRelationsMigration($this->modelName, $change['info']);
                    break;
            }
        }

		$essencesChanged = $this->runChangeEssences($this->parseOuterActions());
		if ($essencesChanged) {
			$this->correctOuterYaml();
		}

		return $essencesChanged;
	}

    /**
     * @param $actions array
     * @return bool
     */
	public function runChangeEssences($actions)
    {
		if (empty($actions)) {
			return true;
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
     * @param $actions
     * @return bool
     */
	public function runCorrectActions($actions)
    {
		$crudAdapter = $this->service->modelProvider->getCrudAdapter();
		// Если нет CRUD адаптера, то не нужно миграций
		if ( ! $crudAdapter) {
			return true;
		}

		if ( ! $crudAdapter->correctModel($this->modelName, $actions)) {
			return false;
		}

		$migrationMaker = new MigrationMaker($this->service);
		$migrationMaker->createFieldsMigration($this->modelName, $actions);

		$this->correctFile($actions);

		return true;
	}


	/*************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/

    /**
     * @param $actions array
     */
    private function correctFile($actions)
    {
        // Правка yaml-кода
        //todo - перенести отсюда
        $code = $this->code;
        foreach ($actions as $action) {
            $type = $action['action'];
            switch ($type) {
                case MigrationMaker::ACTION_ADD_FIELD:
                    $arr = [];
                    foreach ($action['params'] as $property => $value) {
                        $arr[] = "$property: $value";
                    }
                    $fieldStr = '    ' . $action['name'] . ': { ' . implode(', ', $arr) . ' }';
                    $code = preg_replace(
                        '/(fields:[\w\W]*?)([\r\n]+?  \w|$)/',
                        '$1' . PHP_EOL . $fieldStr . '$2',
                        $code
                    );
                    break;

                //todo - неверно, поле может быть описано несколькими строками, надо отталкиваться от количества пробелов
                case MigrationMaker::ACTION_REMOVE_FIELD:
                    $category = 'fields';
                    $code = preg_replace(
                        '/(' . $category . ':[\w\W]*?)    ' . $action['name'] . '.*?[\r\n]+?( |$)/',
                        '$1$2',
                        $code
                    );
                    break;

                case MigrationMaker::ACTION_RENAME_FIELD:
                    $category = 'fields';
                    $code = preg_replace(
                        '/(' . $category . ':[\w\W]*?    )' . $action['old'] . '/',
                        '$1' . $action['new'],
                        $code
                    );
                    break;

                case MigrationMaker::ACTION_CHANGE_FIELD:
                    $code = preg_replace(
                        '/(fields:[\w\W]*?    ' . $action['fieldName'] . '[\w\W]*?'
                            . $action['property'] . ': *)' . $action['old'] . '/',
                        '$1'.$action['new'],
                        $code
                    );
                    break;
            }
        }
        $file = new File($this->path);
        $file->put($code);
    }

    /**
     * @return array
     */
	private function parseOuterActions()
    {
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
     * @param $__data array
     * @return array
     */
	private function outerAddModels($__data)
    {
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
     * @param $data array
     * @return array
     */
	private function outerAddModelsFromArray($data)
    {
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
     * @param $__data array
     * @return array|bool
     */
	private function outerEditModels($__data)
    {
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
     * @param $__data array
     * @return array
     */
	private function outerRemoveModels($__data)
    {
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
	 */
	private function correctOuterYaml()
    {
		$code = $this->code;

		if (!empty($this->outerActions)) {
			$code = preg_replace('/!!(add|remove|query)[\w\W]*$/', '', $code);
		}

		$file = new File($this->path);
		$file->put($code);
	}
}
