<?php

namespace lx;

class DbCrudAdapter extends CrudAdapter {
	private $service;
	private $db;

	/*************************************************************************************************************************
	 * PUBLIC
	 *************************************************************************************************************************/

	/**
	 * Доопределяем родительский метод, чтобы иметь подключение к базе от модуля
	 * */
	public function setModelProvider($modelProvider) {
		parent::setModelProvider($modelProvider);
		$this->service = $modelProvider->getService();
		$this->db = null;
	}

	/**
	 * Загрузка данных для моделей в формате перечислимого массива со строками - ассоциативными массивами
	 * */
	public function loadModelsData($schema, $condition) {
		$table = $this->getTable($schema);
		return $table->select('*', $condition);
	}

	/**
	 * Добавление/апдейт конкретной модели
	 * */
	public function saveModel($model) {
		if (!$model->isNew() && !$model->isChanged()) return;

		$schema = $model->getSchema();

		$pk = $model->pk();
		$pkName = $model->pkName();

		$temp = $model->getFields();
		unset($temp[$pkName]);

		$table = $this->getTable($schema);
		// Если запись новая
		if ($pk === null) {
			$model->setPk( $table->insert(array_keys($temp), array_values($temp)) );
		
		// Если запись не новая
		} else {
			$table->update($temp, [$pkName => $pk]);
		}
	}

	/**
	 * Удаление конкретной модели
	 * */
	public function deleteModel($model) {
		if ($model->isNew()) return;

		$schema = $model->getSchema();
		$table = $this->getTable($schema);

		$table->delete([$schema->pkName() => $model->pk()]);
		$model->drop();
	}

	/**
	 * Массовое добавление/обновление моделей
	 * */
	public function saveModels($schema, $arr) {
		if (!is_array($arr) || empty($arr)) return;

		$forInsert = [];
		$forUpdate = [];
		foreach ($arr as $model) {
			// Если модель уже не новая, но при этом не поменялась - не перезаписываем
			if (!$model->isNew() && !$model->isChanged()) continue;

			if ($model->getSchema() !== $schema) {
				throw new \Exception('Array of models is bitty', 400);
			}

			if ($model->isNew()) {
				$forInsert[] = $model;
			} else {
				$forUpdate[] = $model;
			}
		}

		$table = $this->getTable($schema);

		if (!empty($forUpdate)) {
			$rows = [];
			foreach ($forUpdate as $model) {
				$rows[] = $model->getFields();
			}
			$this->getDb()->massUpdate($table, $rows);
		}

		$pkName = $schema->pkName();
		if (!empty($forInsert)) {
			$rows = [];
			foreach ($forInsert as $model) {
				$fields = $model->getFields();
				unset($fields[$pkName]);
				$rows[] = $fields;
			}
			$rows = ArrayHelper::valuesStable($rows);
			$ids = (array)$table->insert($rows['keys'], $rows['rows']);
			sort($ids);

			$i = 0;
			foreach ($forInsert as $model) {
				$model->setPk($ids[$i++]);
			}
		}
	}

	/**
	 * Массовое удаление моделей
	 * */
	public function deleteModels($schema, $arr) {
		if (!is_array($arr) || empty($arr)) return;

		$pks = [];
		foreach ($arr as $model) {
			if ($model->getSchema() !== $schema) {
				throw new \Exception('Array of records is bitty', 400);
			}

			$pks[] = $model->pk();
			$model->drop();
		}

		$pkName = $schema->pkName();
		$table = $this->getTable($schema);
		$table->delete([$pkName => $pks]);
	}

	/**
	 *
	 * */
	public function createTable($schema) {
		$db = $this->getDb();

		$tableName = $schema->getTableName();
		$schemaConfig = [
			$schema->pkName() => $db->primaryKeyDefinition()
		];

		$fields = $schema->fieldNames();
		foreach ($fields as $fieldName) {
			if ($fieldName == $schema->pkName()) continue;

			$field = $schema->field($fieldName);
			$definition = $this->definitionByField($field);
			if (!$definition) continue;

			$schemaConfig[$fieldName] = $definition;
		}

		return $db->newTable($tableName, $schemaConfig);
	}

	/**
	 * //todo!!!! нормально с транзакциями сделать
	 * */
	public function correctModel($modelName, $tableName, $actions) {
		/*
		$actions - перечислимый массив, возможные элементы:
			'action' => 'renameTable'
				'old'
				'new'
			'action' => 'renameField'
				'old'
				'new'
			'action' => 'addField'
				'name'
				'params'
			'action' => 'removeField'
				'name'
			'action' => 'changeFieldProperty'
				'fieldName'  // с учетом переименования (уже переименованное)
				'property'
				'old'
				'new'
		*/

		$db = $this->getDb();
		$db->query('BEGIN;');
		try {
			$tableName = $this->applyInnerActions($tableName, $actions);
		} catch (\Exception $e) {
			$db->query('ROLLBACK;');
			throw $e;
			
		}
		$db->query('COMMIT;');

		return $tableName;
	}

	/**
	 *
	 * */
	public function addModelEssences($modelName, $tableName, $actions) {
		/*
		$actions - ассоциативный массив, примеры:
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
			!!remove:
			  slug: val
			!!remove:
			  vars:
			    $slug: SomeModel('slug1')->field;
			  where:
			    slug: $slug
			!!query: "--SOME SQL QUERY"
		*/

		$db = $this->getDb();
		$db->query('BEGIN;');
		try {
			$this->applyOuterActions($modelName, $actions);
		} catch (\Exception $e) {
			$db->query('ROLLBACK;');
			throw $e;
			
		}
		$db->query('COMMIT;');

		return true;
	}

	/**
	 * Проверить - существует ли для модели таблица
	 * */
	public function checkNeedTable($schema) {
		$tableName = $schema->getTableName();

		$db = $this->getDb();
		if (!$db) {
			throw new \Exception("Not found db-connection for model '{$schema->getName()}'", 400);
		}

		return (!$db->tableExists($tableName));
	}

	/*************************************************************************************************************************
	 * PROTECTED
	 *************************************************************************************************************************/

	/**
	 * Можно переопределить у потомка, чтобы в зависимости от схемы менять базу. Задел для шардинга
	 * Более сложный шардинг зависит от конкретной реализации, проще написать свой CRUD-адаптер для конкретной ситуации
	 * */
	protected function getDb($schema = null) {
		if ($this->db === null)  {
			$this->db = $this->service->db();
		}

		return $this->db;
	}

	/*************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/

	/**
	 *
	 * */
	private function getTable($schema) {
		$tableName = $schema->getTableName();

		$db = $this->getDb();
		if (!$db) {
			throw new \Exception("Not found db-connection for model '{$schema->getName()}'", 400);
		}

		if (!$db->tableExists($tableName)) $this->createTable($schema);
		return $db->table($tableName);
	}

	/**
	 *
	 * */
	private function definitionByField($field) {
		// $type = $field->getType();
		// $dbType;
		// $conf = [];
		// switch ($type) {
		// 	case ModelField::TYPE_INTEGER:
		// 		$dbType = 'integer';
		// 		break;

		// 	case ModelField::TYPE_STRING:
		// 		$dbType = 'varchar';
		// 		$conf['size'] = $field->len();
		// 		break;

		// 	case ModelField::TYPE_BOOLEAN:
		// 		$dbType = 'boolean';
		// 		break;

		// 	// case ModelField::TYPE_MODEL:
		// 	// 	/*
		// 	// 	Это явно заявка на внешний ключ
		// 	// 	*/
		// 	// 	break;

		// 	// case ModelField::TYPE_MODEL_ARRAY:
		// 	// 	// Здесь надо выяснить - возможно нужна промежуточная таблица, если вторая модель тоже с массивом
		// 	// 	// Если вторая модель с одиночной ссылкой - это внешний ключ во второй модели
		// 	// 	// Если во второй модели нет ссылки на первую - это расценивать как ошибку?

		// 	// 	// $manager = $this->modelProvider->getManager( $field->getModelName() );
		// 	// 	// var_dump($manager);
		// 	// 	// die();

		// 	// 	break;
		// }

		// if (!$dbType) return null;

		// $conf['default'] = $field->getDefault();
		// $conf['notNull'] = $field->isNotNull();

		$fieldDefinition = $field->getDefinition();
		$dbType = $fieldDefinition['dbType'];

		$definition = $this->getDb()->$dbType($fieldDefinition);

		return $definition;
	}

	/**
	 *
	 * */
	private function applyInnerActions($tableName, $innerActions) {
		foreach ($innerActions as $actionData) {
			$action = $actionData['action'];
			if (method_exists($this, $action)) {
				if ($action == 'renameTable') {
					$tableName = $this->renameTable($tableName, $actionData);
				} else {
					$this->$action($tableName, $actionData);
				}
			}
		}

		return $tableName;
	}

	/**
	 *
	 * */
	private function renameTable($tableName, $data) {
		$this->getDb()->renameTable($data['old'], $data['new']);
		return $data['new'];
	}

	/**
	 *
	 * */
	private function renameField($tableName, $data) {
		$this->getDb()->tableRenameColumn($tableName, $data['old'], $data['new']);
	}

	/**
	 *
	 * */
	private function addField($tableName, $data) {
		$field = ModelField::create($data['name'], $data['params']);
		$definition = $this->definitionByField($field);
		$this->getDb()->tableAddColumn($tableName, $data['name'], $definition);
	}

	/**
	 *
	 * */
	private function removeField($tableName, $data) {
		$this->getDb()->tableDropColumn($tableName, $data['name']);
	}

	/**
	 * //todo!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	 * */
	private function changeFieldProperty($tableName, $data) {



	}

	/**
	 *
	 * */
	private function applyOuterActions($modelName, $outerActions) {
		foreach ($outerActions as $actionKey => $actionData) {
			switch ($actionKey) {
				case '!!query':
					$this->getDb()->query($actionData);
					break;
				case '!!add':
					$this->migrationAddModels($modelName, $actionData);
					break;
				case '!!addTable':
					$this->migrationAddModelsFromArray($modelName, $actionData);
					break;
				case '!!remove':
					$this->migrationRemoveModels($modelName, $actionData);
					break;
			}
		}
	}

	/**
	 *
	 * */
	private function migrationAddModels($__modelName, $__data) {
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

		// Создаем модели
		$manager = $this->modelProvider->getManager($__modelName);
		$models = $manager->newModels( count($__data) );
		if (!is_array($models)) $models = [$models];
		foreach ($__data as $i => $params) {
			$models[$i]->setFields($params);
		}
		$manager->saveModels($models);
	}

	/**
	 *
	 * */
	private function migrationAddModelsFromArray($modelName, $data) {
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

		$this->migrationAddModels($modelName, $data);
	}

	/**
	 *
	 * */
	private function migrationRemoveModels($__modelName, $__data) {
		if (array_key_exists('condition', $__data)) {
			// Создаем переменные
			$__vars = [];
			if (array_key_exists('vars', $__data)) {
				foreach ($__data['vars'] as $varName => $varCode) {
					if (!preg_match('/;$/', $varCode)) $varCode .= ';';
					eval( $varName . '=' . $varCode . '$__vars[\''. $varName .'\']=' . $varName . ';' );
				}
			}

			// Парсим данные на использование переменных
			$__data = $__data['condition'];
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

		$manager = $this->modelProvider->getManager($__modelName);
		$models = $manager->loadModels($__data);
		$manager->deleteModels($models);
	}
}
