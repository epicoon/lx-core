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
		if (!$table) {
			return [];
		}
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
		if (!$table) {
			return false;
		}

		// Если запись новая
		if ($pk === null) {
			$model->setPk( $table->insert(array_keys($temp), array_values($temp)) );
		
		// Если запись не новая
		} else {
			$table->update($temp, [$pkName => $pk]);
		}

		return true;
	}

	/**
	 * Удаление конкретной модели
	 * */
	public function deleteModel($model) {
		if ($model->isNew()) return;

		$schema = $model->getSchema();
		$table = $this->getTable($schema);
		if (!$table) {
			return;
		}

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
		if (!$table) {
			return false;
		}

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

		return true;
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
		if (!$table) {
			return;
		}

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
	 *
	 * */
	public function deleteTable($schema) {
		$db = $this->getDb();

		$tableName = $schema->getTableName();

		return $db->dropTable($tableName);
	}

	/**
	 * //todo!!!! нормально с транзакциями сделать
	 * //todo есть сомнения по архитектуре - зачем крад-адаптеру так глубоко знать про миграционные экшены?
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
	public function correctModelEssences($modelName, $tableName, $actions) {
		/*
		$actions - ассоциативный массив, варианты диерктив:
			['query', $query]
			['add', $modelName, $data]
			['del', $modelName, $data]
			['edit', $modelName, $dataPares]

			//todo del-force - быстрое удаление без запоминания
			//todo edit-force - быстрое изменение без запоминания
		*/
		$db = $this->getDb();
		$db->query('BEGIN;');
		try {
			$this->applyOuterActions($actions);
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

		if ($db->tableExists($tableName)) {
			return $db->table($tableName);
		}

		return null;
	}

	/**
	 *
	 * */
	private function definitionByField($field) {
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
	private function applyOuterActions($outerActions) {
		foreach ($outerActions as $action) {
			$actionKey = $action[0];
			switch ($actionKey) {
				case 'add':
					$this->addModelsFromMigration($action[1], $action[2]);
					break;
				case 'edit':
					$this->editModelsFromMigration($action[1], $action[2]);
					break;
				case 'del':
					$this->delModelsFromMigration($action[1], $action[2]);
					break;
				case 'query':
					$this->getDb()->query($action[1]);
					break;
			}
		}
	}

	/**
	 *
	 * */
	private function addModelsFromMigration($modelName, $data) {
		$manager = $this->modelProvider->getManager($modelName);
		$models = $manager->newModels( count($data) );
		if (!is_array($models)) $models = [$models];
		foreach ($data as $i => $params) {
			$models[$i]->setFields($params);
		}
		$manager->saveModels($models);
	}

	/**
	 *
	 * */
	private function editModelsFromMigration($modelName, $pares) {
		$manager = $this->modelProvider->getManager($modelName);
		$models = [];
		foreach ($pares as $pare) {
			$tempModels = $manager->loadModels($pare[0]);
			foreach ($tempModels as $model) {
				$model->setFields($pare[1]);
				$models[] = $model;
			}
		}

		// var_dump($models);

		$manager->saveModels($models);
	}

	/**
	 *
	 * */
	private function delModelsFromMigration($modelName, $data) {
		$manager = $this->modelProvider->getManager($modelName);
		$models = [];
		foreach ($data as $params) {
			$models[] = $manager->loadModel($params);
		}
		$manager->deleteModels($models);
	}
}
