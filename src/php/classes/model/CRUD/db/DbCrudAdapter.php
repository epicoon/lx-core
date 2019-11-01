<?php

namespace lx;

require_once(__DIR__ . '/DbCrudAdapterSysTable.php');
require_once(__DIR__ . '/DbCrudAdapterFieldsComparator.php');
require_once(__DIR__ . '/DbCrudAdapterRelationsComparator.php');

class DbCrudAdapter extends CrudAdapter {
	const SYS_TABLE_NAME = 'lx_crud_sys';

	private $service;
	private $db;

	private $modelManagerPappets = [];
	private $sysTable;

	/*************************************************************************************************************************
	 * PUBLIC
	 *************************************************************************************************************************/

	public function getConnection() {
		return $this->getDb()->getConnection();
	}

	public function getService() {
		return $this->service;
	}

	/**
	 * Доопределяем родительский метод, чтобы иметь подключение к базе от модуля
	 * */
	public function setModelProvider($modelProvider) {
		parent::setModelProvider($modelProvider);
		$this->service = $modelProvider->getService();
		$this->db = null;
	}

	/**
	 *
	 * */
	public function loadModel($modelName, $condition) {
		$table = $this->getTable($modelName);
		if (!$table) return null;

		//TODO limit 1

		$data = $table->select('*', $condition);
		if (empty($data)) return null;

		$props = $data[0];
		$manager = $this->getModelManager($modelName);
		if ( ! $manager) {
			//TODO сообщение что адаптер не поддерживает такую модель
			return null;
		}

		$result = new ModelData($manager, $props);
		$result->setNewFlag(false);
		return $result;
	}

	/**
	 * Добавление/апдейт конкретной модели
	 * */
	public function saveModel($model) {
		if (!$model->isNew() && !$model->isChanged()) return false;

		$schema = $model->getSchema();
		if ($schema->getCrudAdapter() !== $this) {
			throw new Exception("There is attemp to save model '{$model->getModelName()}' by wrong CRUD adapter", 400);
		}

		$pk = $model->pk();
		$pkName = $model->pkName();

		$temp = $model->getFields();
		unset($temp[$pkName]);

		$table = $this->getTable($schema->getName());
		if (!$table) {
			return false;
		}

		// Если запись новая
		if ($pk === null) {
			$model->setPk($table->insert($temp));
			$model->setNewFlag(false);
		
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
		if ( ! $model || $model->isNew()) return;

		$schema = $model->getSchema();
		if ($schema->getCrudAdapter() !== $this) {
			throw new Exception("There is attemp to delete model '{$model->getModelName()}' by wrong CRUD adapter", 400);
		}

		$table = $this->getTable($schema->getName());
		if (!$table) {
			return;
		}

		$table->delete([$schema->pkName() => $model->pk()]);
		$model->drop();
	}

	/**
	 *
	 * */
	public function loadModels($modelName, $condition = null) {
		$result = new ModelCollection();

		$table = $this->getTable($modelName);
		if (!$table) {
			return $result;
		}

		$data = $table->select('*', $condition);
		if (empty($data)) return $result;

		$manager = $this->getModelManager($modelName);
		if ( ! $manager) {
			//TODO сообщение что адаптер не поддерживает такую модель
			return $result;
		}

		foreach ($data as $props) {
			$obj = new ModelData($manager, $props);
			$obj->setNewFlag(false);
			$result[] = $obj;
		}

		return $result;
	}

	/**
	 * Массовое добавление/обновление моделей
	 * */
	public function saveModels($arr) {
		if (is_object($arr) && method_exists($arr, 'toArray')) {
			$arr = $arr->toArray();
		}

		if (!is_array($arr) || empty($arr)) return;

		$schema = $arr[0]->getSchema();
		$forInsert = [];
		$forUpdate = [];
		foreach ($arr as $model) {
			// Если модель уже не новая, но при этом не поменялась - не перезаписываем
			if (!$model->isNew() && !$model->isChanged()) continue;

			$currSchema = $model->getSchema();
			if ($currSchema->getCrudAdapter() !== $this) {
				throw new Exception("There is attemp to save model '{$model->getModelName()}' by wrong CRUD adapter", 400);
			}

			if ($currSchema !== $schema) {
				throw new \Exception('Array of models is bitty', 400);
			}

			if ($model->isNew()) {
				$forInsert[] = $model;
			} else {
				$forUpdate[] = $model;
			}
		}

		$table = $this->getTable($schema->getName());
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
				if (array_key_exists($pkName, $fields) && !$fields[$pkName]) {
					unset($fields[$pkName]);
				}
				$rows[] = $fields;
			}
			$rows = ArrayHelper::valuesStable($rows);
			$ids = (array)$table->insert($rows['keys'], $rows['rows']);
			sort($ids);

			$i = 0;
			foreach ($forInsert as $model) {
				$model->setPk($ids[$i++]);
				$model->setNewFlag(false);
			}
		}

		return true;
	}

	/**
	 * Массовое удаление моделей
	 * */
	public function deleteModels($arr) {
		if (is_object($arr) && method_exists($arr, 'toArray')) {
			$arr = $arr->toArray();
		}

		if (!is_array($arr) || empty($arr)) return;

		$schema = $arr[0]->getSchema();
		$pks = [];
		foreach ($arr as $model) {
			$currSchema = $model->getSchema();
			if ($currSchema->getCrudAdapter() !== $this) {
				throw new Exception("There is attemp to delete model '{$model->getModelName()}' by wrong CRUD adapter", 400);
			}

			if ($currSchema !== $schema) {
				throw new \Exception('Array of records is bitty', 400);
			}

			$pks[] = $model->pk();
			$model->drop();
		}

		$pkName = $schema->pkName();
		$table = $this->getTable($schema->getName());
		if (!$table) {
			return;
		}

		$table->delete([$pkName => $pks]);
	}

	public function addRelations($baseModel, $relation, $modelsList) {
		if (is_object($modelsList) && method_exists($modelsList, 'toArray')) {
			$modelsList = $modelsList->toArray();
		}

		list ($tableName, $key1, $key2) = $this->getRelativeTableParams(
			$baseModel->getModelName(),
			$relation->getRelativeModelName()
		);

		$table = $this->getDb()->table($tableName);

		$ids = [];
		foreach ($modelsList as $model) {
			$ids[] = $model->pk();
		}

		$idsExist = $table->selectColumn($key2, [$key1 => $baseModel->pk()]);
		$ids = array_diff($ids, $idsExist);
		if (empty($ids)) {
			return;
		}

		$modelPk = $baseModel->pk();
		$data = [];
		foreach ($modelsList as $model) {
			$pk = $model->pk();
			if (array_search($pk, $ids) !== false) {
				$data[] = [$modelPk, $pk];
			}
		}

		$table->insert([$key1, $key2], $data, false);
	}

	public function delRelations($model, $relation, $modelsList) {
		if (is_object($modelsList) && method_exists($modelsList, 'toArray')) {
			$modelsList = $modelsList->toArray();
		}

		list ($tableName, $key1, $key2) = $this->getRelativeTableParams(
			$model->getModelName(),
			$relation->getRelativeModelName()
		);
		$table = $this->getDb()->table($tableName);

		$ids = [];
		foreach ($modelsList as $relModel) {
			$ids[] = $relModel->pk();
		}

		$table->delete([
			$key1 => $model->pk(),
			$key2 => $ids,
		]);
	}

	public function loadRelations($model, $relation) {
		list ($tableName, $key1, $key2) = $this->getRelativeTableParams(
			$model->getModelName(),
			$relation->getRelativeModelName()
		);

		$table = $this->getDb()->table($tableName);
		$ids = $table->selectColumn($key2, [$key1 => $model->pk()]);
		$relativeSchema = $model->getSchema()->getRelativeSchema($relation);
		return ModelData::load($relativeSchema->getManager(), $ids);
	}


	/*******************************************************************************************************************
	 * Управление таблицами
	 ******************************************************************************************************************/

    public function checkModelChanges($modelName, $modelSchema)
    {
        $result = [];
        if ($this->checkNeedTable($modelName)) {
            $result[] = [
                'type' => MigrationMaker::TYPE_NEW_TABLE,
            ];

            if (isset($modelSchema['relations'])) {
                $result[] = [
                    'type' => MigrationMaker::TYPE_RELATIONS_TABLE,
                    'info' => $modelSchema['relations'],
                ];
            }

            return $result;
        }

        list ($serviceName, $selfModelName) = $this->splitModelName($modelName);
        $tableName = $this->getSysTable()->getModelTableName($serviceName, $selfModelName);
        $tableSchema = $this->getDb()->tableSchema($tableName, DB::SHORT_SCHEMA);
        $comparator = new DbCrudAdapterFieldsComparator($modelSchema, $tableSchema);
        $diff = $comparator->run();
        if ( ! empty($diff)) {
            $result[] = [
                'type' => MigrationMaker::TYPE_ALTER_TABLE,
                'info' => $diff,
            ];
        }

        $relations = $modelSchema['relations'] ?? [];
        $dbRelations = $this->getSysTable()->getModelRelations($serviceName, $selfModelName);
        $comparator = new DbCrudAdapterRelationsComparator($serviceName, $relations, $dbRelations);
        $diff = $comparator->run();
        if ( ! empty($diff)) {
            $result[] = [
                'type' => MigrationMaker::TYPE_RELATIONS_TABLE,
                'info' => $diff,
            ];
        }

        return $result;
    }

	/**
	 * Проверить - существует ли для модели таблица
	 * */
	public function checkNeedTable($modelName) {
		$db = $this->getDb();
		if (!$db) {
			throw new \Exception(
				"Not found db-connection for CRUD adapter in service '{$this->getService()->name}'",
				400
			);
		}

		list($serviceName, $selfModelName) = $this->splitModelName($modelName);
		$tableExists = $this->getSysTable()->tableExists($serviceName, $selfModelName);
		return !$tableExists;
	}

	/**
	 *
	 * */
	public function createTable($modelName, $schema = null) {
		if ( ! $this->checkNeedTable($modelName)) {
			return null;
		}

		if ($schema === null) {
			$schema = $this->getSchema($modelName);
		}

		if ( ! $schema) {
			//TODO сообщение что адаптер не поддерживает такую модель
			return null;
		}

		$db = $this->getDb();
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

		list($serviceName, $selfModelName) = $this->splitModelName($modelName);
		$tableName = $this->getSysTable()->defineModelTableName($serviceName, $selfModelName);
		$result = $db->newTable($tableName, $schemaConfig);
		if ($result) {
			$this->getSysTable()->createTable($serviceName, $selfModelName, $tableName);
		}

		return $result;
	}

	/**
	 *
	 * */
	public function deleteTable($modelName) {
		$tableName = $this->getModelTableName($modelName);
		if ( ! $tableName) {
			return true;
		}

		$db = $this->getDb();
		$result = $db->dropTable($tableName);
		if ($result) {
		    list($serviceName, $selfModelName) = $this->splitModelName($modelName);
			$this->getSysTable()->deleteTable($serviceName, $selfModelName, $tableName);
		}

		return $result;
	}



















	/*******************************************************************************************************************
	 * Реагируем на миграции
	 ******************************************************************************************************************/

	/**
	 * //todo!!!! нормально с транзакциями сделать
	 * //todo есть сомнения по архитектуре - зачем крад-адаптеру так глубоко знать про миграционные экшены?
	 * */
	public function correctModel($modelName, $actions) {
		/*
		$actions - перечислимый массив, возможные элементы:
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
			$result = $this->applyInnerActions($modelName, $actions);
		} catch (\Exception $e) {
			$db->query('ROLLBACK;');
			throw $e;
			
		}
		$db->query('COMMIT;');

		return $result;
	}

    public function correctRelations($modelName, $actions) {
        $db = $this->getDb();
        $db->query('BEGIN;');
        try {
            $this->applyRelationActions($modelName, $actions);
        } catch (\Exception $e) {
            $db->query('ROLLBACK;');
            throw $e;

        }
        $db->query('COMMIT;');

        return true;
    }

	public function correctModelEssences($modelName, &$actions, $schema = null) {
		/*
		$actions - ассоциативный массив, варианты диерктив:
			['query', $query]
			['add', $modelName, $data]
			['del', $modelName, $data]
			['edit', $modelName, $dataPares]

			//todo del-force - быстрое удаление без запоминания
			//todo edit-force - быстрое изменение без запоминания
		*/
		if ($schema === null) {
			$schema = $this->getSchema($modelName);
		}

		if ( ! $schema) {
			//TODO сообщение что адаптер не поддерживает такую модель
			return null;
		}

		$db = $this->getDb();
		$db->query('BEGIN;');
		try {
			$this->applyOuterActions($schema, $actions);
		} catch (\Exception $e) {
			$db->query('ROLLBACK;');
			throw $e;
			
		}
		$db->query('COMMIT;');

		return true;
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

	/**
	 *
	 * */
	protected function determineRelationTableName($manager, $relativeManager) {
		$tableName = $this->getSysTable()->getModelTableName(
			$manager->getService()->name,
			$manager->getModelName()
		);

		$relativeTableName = $this->getSysTable()->getModelTableName(
			$relativeManager->getService()->name,
			$relativeManager->getModelName()
		);

		$names = [$tableName, $relativeTableName];
		sort($names);
		return 'rel__' . $names[0] . '__vs__' . $names[1];
	}

	/**
	 *
	 * */
	protected function getRelativeTableParams($modelName, $relativeModelName) {
		$manager = $this->getModelManager($modelName);
		if ( ! $manager) {
			throw new \Exception("Wrong model '$modelName' for CRUD adapter", 400);			
		}

		$relativeManager = $this->getModelManager($relativeModelName);
		if ( ! $relativeManager) {
			throw new \Exception("Wrong model '$relativeModelName' for CRUD adapter", 400);			
		}

		$tableName = $this->getSysTable()->getModelTableName(
			$manager->getService()->name,
			$manager->getModelName()
		);
		$relativeTableName = $this->getSysTable()->getModelTableName(
			$relativeManager->getService()->name,
			$relativeManager->getModelName()
		);
		$key1 = 'id_' . $tableName;
		$key2 = 'id_' . $relativeTableName;

		$names = [$tableName, $relativeTableName];
		sort($names);
		$relTableName = 'rel__' . $names[0] . '__vs__' . $names[1];

		return [$relTableName, $key1, $key2];
	}


	/*************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/

	/**
	 *
	 * */
	/*TODO!!!!*/public function getSysTable() {
		if ($this->sysTable) {
			return $this->sysTable;
		}

		$db = $this->getDb();
		if (!$db) {
			throw new \Exception("Not found db-connection for model '{$schema->getName()}'", 400);
		}

		$tableName = self::SYS_TABLE_NAME;
		if (!$db->tableExists($tableName)) {
			$db->newTable($tableName, [
                'model_info' => ['type' => 'string'],
                'table_name' => ['type' => 'string'],
                'type' => ['type' => 'string'],
			]);
		}

		$this->sysTable = new DbCrudAdapterSysTable($this, $db->table($tableName));
		return $this->sysTable;
	}

	/**
	 *
	 * */
	private function getTable($modelName) {
		$tableName = $this->getModelTableName($modelName);
		if ( ! $tableName) {
			return null;
		}

		$schema = $this->getSchema($modelName);
		if ( ! $schema) {
			//TODO сообщение что адаптер не поддерживает такую модель
			return null;
		}

		$db = $this->getDb();
		if (!$db) {
			throw new \Exception("Not found db-connection for model '$modelName'", 400);
		}

		$table = $db->table($tableName);
		$table->setPkName($schema->pkName());
		return $table;
	}

	/**
	 *
	 * */
	private function compatible($crudAdapter) {
		try {
			return $crudAdapter instanceof DbCrudAdapter
				&&  $this->getConnection() === $crudAdapter->getConnection();
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 *
	 * */
	private function getModelManager($modelName) {
		if (array_key_exists($modelName, $this->modelManagerPappets)) {
			return $this->modelManagerPappets[$modelName];
		}

		if (preg_match('/\./', $modelName)) {
			$manager = $this->app->getModelManager($modelName);
			if ( ! $manager || ! $this->compatible($manager->getCrudAdapter())) {
				return null;
			}

			return $manager;
		} else {
			return $this->service->modelProvider->getManager($modelName);
		}
	}

	/**
	 *
	 * */
	private function getSchema($modelName) {
		$manager = $this->getModelManager($modelName);
		if ( ! $manager) {
			return null;
		}

		return $manager->getSchema();
	}

	/**
	 *
	 * */
	private function getModelTableName($modelName) {
	    list($serviceName, $selfModelName) = $this->splitModelName($modelName);
		return $this->getSysTable()->getModelTableName($serviceName, $selfModelName);
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
	private function applyInnerActions($modelName, $innerActions) {
		$tableName = $this->getModelTableName($modelName);

		foreach ($innerActions as $actionData) {
		    switch ($actionData['action']) {
                case MigrationMaker::ACTION_ADD_FIELD: $action = 'addField'; break;
                case MigrationMaker::ACTION_REMOVE_FIELD: $action = 'removeField'; break;
                case MigrationMaker::ACTION_RENAME_FIELD: $action = 'renameField'; break;
                case MigrationMaker::ACTION_CHANGE_FIELD: $action = 'changeFieldProperty'; break;
            }

			if (method_exists($this, $action)) {
				$this->$action($tableName, $actionData);
			}
		}

		return true;
	}

	// /**
	//  *
	//  * */
	// private function renameTable($tableName, $data) {
	// 	$this->getDb()->renameTable($data['old'], $data['new']);
	// 	return $data['new'];
	// }

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

	private function applyRelationActions($modelName, $actions) {
        foreach ($actions as $action) {
            switch ($action['action']) {
                case MigrationMaker::ACTION_ADD_RELATION:
                    $this->addRelation($modelName, $action);
                    break;
                case MigrationMaker::ACTION_REMOVE_RELATION:
                    $this->removeRelation($modelName, $action);
                    break;
                case MigrationMaker::ACTION_RENAME_RELATION:
                    $this->renameRelation($modelName, $action);
                    break;
                case MigrationMaker::ACTION_CHANGE_RELATION:
                    $this->changeRelation($modelName, $action);
                    break;
            }
        }
    }

    private function addRelation($modelName, $action) {
        $manager = $this->getModelManager($modelName);
        if ( ! $manager) {
            throw new \Exception("Wrong model '$modelName' for CRUD adapter", 400);
        }

        $relativeModelName = $action['value'];
        $relativeManager = $this->getModelManager($relativeModelName);
        if ( ! $relativeManager) {
            throw new \Exception("Wrong model '$relativeModelName' for CRUD adapter", 400);
        }

        $db = $this->getDb();
        if (!$db) {
            throw new \Exception("Not found CRUD adapter db-connection", 400);
        }

        if ($this->checkNeedTable($modelName) || $this->checkNeedTable($relativeModelName)) {
            return null;
        }

        $tableName = $this->getSysTable()->getModelTableName(
            $manager->getService()->name,
            $manager->getModelName()
        );
        $relativeTableName = $this->getSysTable()->getModelTableName(
            $relativeManager->getService()->name,
            $relativeManager->getModelName()
        );
        $relTableName = $this->determineRelationTableName($manager, $relativeManager);
        $key1 = 'id_' . $tableName;
        $key2 = 'id_' . $relativeTableName;

        $schemaConfig = [
            $key1 => $db->foreignKeyDefinition([
                $relTableName,
                $key1,
                $tableName,
                $manager->getSchema()->pkName()
            ]),
            $key2 => $db->foreignKeyDefinition([
                $relTableName,
                $key2,
                $relativeTableName,
                $relativeManager->getSchema()->pkName()
            ]),
        ];

        $result = $db->newTable($relTableName, $schemaConfig);
        if ($result) {
            $this->getSysTable()->createRelativeTable(
                $manager->getSchema(),
                $relativeManager->getSchema(),
                $action['name'],
                $relTableName
            );
        }

        return $result;
	}

    private function removeRelation($modelName, $action) {
        $manager = $this->getModelManager($modelName);
        if ( ! $manager) {
            throw new \Exception("Wrong model '$modelName' for CRUD adapter", 400);
        }

        $relativeModelName = $action['value'];
        $relativeManager = $this->getModelManager($relativeModelName);
        if ( ! $relativeManager) {
            throw new \Exception("Wrong model '$relativeModelName' for CRUD adapter", 400);
        }

        $db = $this->getDb();
        if (!$db) {
            throw new \Exception("Not found CRUD adapter db-connection", 400);
        }

        if ($this->checkNeedTable($modelName) || $this->checkNeedTable($relativeModelName)) {
            return null;
        }

        $tableName = $this->getSysTable()->getModelTableName(
            $manager->getService()->name,
            $manager->getModelName()
        );
        $relativeTableName = $this->getSysTable()->getModelTableName(
            $relativeManager->getService()->name,
            $relativeManager->getModelName()
        );
        $relTableName = $this->determineRelationTableName($manager, $relativeManager);

        $result = $db->dropTable($relTableName);
        if ($result) {
            $this->getSysTable()->removeRelativeTable(
                $manager->getSchema(),
                $relativeManager->getSchema(),
                $action['name'],
                $relTableName
            );
        }

        return $result;
    }

    private function renameRelation($modelName, $action) {
        throw new \Exception('Not implemented yet', 405);
    }

    private function changeRelation($modelName, $action) {
        throw new \Exception('Not implemented yet', 405);
    }

    private function applyOuterActions($schema, &$outerActions) {
		$this->setModelManagerPappet($schema);
		$modelName = $schema->getName();

		foreach ($outerActions as &$action) {
			$actionKey = $action[0];
			switch ($actionKey) {
				case 'add':
					$this->addModelsFromMigration($modelName, $action[1]);
					break;
				case 'edit':
					$this->editModelsFromMigration($modelName, $action[1]);
					break;
				case 'del':
					$this->delModelsFromMigration($modelName, $action[1]);
					break;
				case 'query':
					$this->getDb()->query($action[1]);
					break;
			}
		}
		unset($action);

		$this->dropModelManagerPappet($schema);
	}

	private function addModelsFromMigration($modelName, &$data) {
		$manager = $this->getModelManager($modelName);
		$models = $manager->newModels( count($data) );
		if (!is_array($models)) $models = [$models];
		foreach ($data as $i => $params) {
			$model = $models[$i];
			$pk = null;
			if (array_key_exists($model->pkName(), $params)) {
				$pk = $params[$model->pkName()];
				unset($params[$model->pkName()]);
			}
			$model->setFields($params);
			if ($pk !== null) {
				$model->setPk($pk);
			}
		}

		$manager->saveModels($models);
		foreach ($models as $i => $model) {
			$data[$i][$model->pkName()] = $model->pk();
		}
	}

	private function editModelsFromMigration($modelName, $pares) {
		$manager = $this->getModelManager($modelName);
		$models = [];
		foreach ($pares as $pare) {
			$tempModels = $manager->loadModels($pare[0]);
			foreach ($tempModels as $model) {
				$model->setFields($pare[1]);
				$models[] = $model;
			}
		}

		$manager->saveModels($models);
	}

	private function delModelsFromMigration($modelName, $data) {
		$manager = $this->getModelManager($modelName);
		$models = [];
		foreach ($data as $params) {
			$models[] = $manager->loadModel($params);
		}
		$manager->deleteModels($models);
	}


    /*******************************************************************************************************************
     * PRIVATE INNER LOGIC
     ******************************************************************************************************************/

    private function checkNeedRelationTable($modelName, $relativeModelName) {
        $manager = $this->getModelManager($modelName);
        if ( ! $manager) {
            throw new \Exception("Wrong model '$modelName' for CRUD adapter", 400);
        }

        $relativeManager = $this->getModelManager($relativeModelName);
        if ( ! $relativeManager) {
            throw new \Exception("Wrong model '$relativeModelName' for CRUD adapter", 400);
        }

        $db = $this->getDb();
        if (!$db) {
            throw new \Exception("Not found CRUD adapter db-connection", 400);
        }

        $tableName = $this->determineRelationTableName($manager, $relativeManager);
        return (!$db->tableExists($tableName));
    }

	private function splitModelName($name) {
	    $arr = explode('.', $name);
	    if (count($arr) == 1) {
	        return [$this->getService()->name, $name];
        }

	    return $arr;
    }

	private function setModelManagerPappet($schema) {
		$modelName = $schema->getName();
		if ( ! array_key_exists($modelName, $this->modelManagerPappets)) {
			$this->modelManagerPappets[$modelName] = new ModelManager($this->app, $this, $schema);
		}
	}

	private function dropModelManagerPappet($schema) {
		unset($this->modelManagerPappets[$schema->getName()]);
	}
}
