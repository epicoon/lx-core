<?php

namespace lx;

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
		$table = $this->getTable($modelName);
		if (!$table) {
			return [];
		}

		$data = $table->select('*', $condition);
		if (empty($data)) return [];

		$manager = $this->getModelManager($modelName);
		if ( ! $manager) {
			//TODO сообщение что адаптер не поддерживает такую модель
			return [];
		}

		$result = [];		
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

	/**
	 *
	 * */
	public function delRelations($model, $relation, $modelsList) {
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


		var_dump(333);
	}

	/**
	 *
	 * */
	public function loadRelations($model, $relation) {
		list ($tableName, $key1, $key2) = $this->getRelativeTableParams(
			$model->getModelName(),
			$relation->getRelativeModelName()
		);

		$table = $this->getDb()->table($tableName);
		$ids = $table->selectColumn($key2, [$key1 => $model->pk()]);
		$relativeSchema = $relation->getRelativeSchema();
		return ModelData::load($relativeSchema->getManager(), $ids);
	}


	/*******************************************************************************************************************
	 * Управление таблицами
	 ******************************************************************************************************************/

	/**
	 * Проверить - существует ли для модели таблица
	 * */
	public function checkNeedTable($modelName) {
		$db = $this->getDb();
		if (!$db) {
			throw new \Exception("Not found db-connection for CRUD adapter in service '{$this->service()->name}'", 400);
		}

		$tableExists = $this->getSysTable()->tableExists($this->service, $modelName);
		return !$tableExists;
	}

	/**
	 *
	 * */
	public function createTable($modelName, $schema = null) {
		if ( ! $this->checkNeedTable($modelName)) {
			return;
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

		$tableName = $this->getSysTable()->defineModelTableName($this->service, $modelName);
		$result = $db->newTable($tableName, $schemaConfig);
		if ($result) {
			$this->getSysTable()->createTable($this->service, $modelName, $tableName);
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
			$this->getSysTable()->deleteTable($this->service, $modelName, $tableName);
		}

		return $result;
	}
















	public function acualizeRelationTables($modelName) {
		
	}
	
	/**
	 * TODO - учесть возможность других сервисов для моделей
	 * */
	public function checkNeedRelationTable($modelName, $relativeModelName) {
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

	/**
	 * TODO - учесть возможность других сервисов для моделей
	 * */
	public function createRelationTable($modelName, $relativeModelName) {
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

		$tableName = $this->getSysTable()->getModelTableName(
			$manager->getService(),
			$manager->getModelName()
		);
		$relativeTableName = $this->getSysTable()->getModelTableName(
			$relativeManager->getService(),
			$relativeManager->getModelName()
		);
		$relTableName = $this->determineRelationTableName($manager, $relativeManager);
		$key1 = 'id_' . $tableName;
		$key2 = 'id_' . $relativeTableName;

		$db = $this->getDb();

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

		return $db->newTable($relTableName, $schemaConfig);
	}

	/**
	 * TODO - переделываем createRelationTable, тут тоже что-то меняем
	 * @param $modelName
	 * @param null $schema
	 * @param null $relationNames
	 * @return array|bool
	 * @throws \Exception
	 */
	public function createRelationTables($modelName, $schema = null, $relationNames = null) {
		if ($this->checkNeedTable($modelName)) {
			return false;
		}

		if ($schema === null) {
			$schema = $this->getSchema($modelName);
		}

		$result = [];
		foreach ($schema->getRelations() as $name => $relation) {
			$relativeModelName = $relation->getRelativeModelName();
			if ($relationNames && array_search($relativeModelName, $relationNames) === false) {
				continue;
			}
			if ($this->checkNeedTable($relativeModelName)) {
				continue;
			}

			$relativeSchema = $relation->getRelativeSchema();
			if ($this->checkNeedRelationTable($schema, $relativeSchema)) {
				if ($this->createRelationTable($schema, $relativeSchema)) {
					$result[] = [$schema->getName(), $relativeSchema->getName()];
				}
			}
		}

		if (empty($result)) {
			return false;
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

	/**
	 *
	 * */
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
			$manager->getService(),
			$manager->getModelName()
		);

		$relativeTableName = $this->getSysTable()->getModelTableName(
			$relativeManager->getService(),
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
			$manager->getService(),
			$manager->getModelName()
		);
		$relativeTableName = $this->getSysTable()->getModelTableName(
			$relativeManager->getService(),
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
				'service_name' => ['type' => 'string'],
				'model_name' => ['type' => 'string'],
				'table_name' => ['type' => 'string'],
			]);
		}

		$this->sysTable = new DbCrudAdapterSysTable($db->table($tableName));
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
		return $this->getSysTable()->getModelTableName($this->service, $modelName);
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
			$action = $actionData['action'];
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
		if ($data['category'] == 'fields') {
			$this->getDb()->tableRenameColumn($tableName, $data['old'], $data['new']);
		} elseif ($data['category'] == 'relations') {

		}
	}

	/**
	 *
	 * */
	private function addField($tableName, $data) {
		if ($data['category'] == 'fields') {
			$field = ModelField::create($data['name'], $data['params']);
			$definition = $this->definitionByField($field);
			$this->getDb()->tableAddColumn($tableName, $data['name'], $definition);
		} elseif ($data['category'] == 'relations') {

		}
	}

	/**
	 *
	 * */
	private function removeField($tableName, $data) {
		if ($data['category'] == 'fields') {
			$this->getDb()->tableDropColumn($tableName, $data['name']);
		} elseif ($data['category'] == 'relations') {



			var_dump($data);
		}
	}

	/**
	 * //todo!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	 * */
	private function changeFieldProperty($tableName, $data) {



	}

	/**
	 *
	 * */
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

	/**
	 *
	 * */
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

	/**
	 *
	 * */
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

	/**
	 *
	 * */
	private function delModelsFromMigration($modelName, $data) {
		$manager = $this->getModelManager($modelName);
		$models = [];
		foreach ($data as $params) {
			$models[] = $manager->loadModel($params);
		}
		$manager->deleteModels($models);
	}

	/**
	 *
	 * */
	private function setModelManagerPappet($schema) {
		$modelName = $schema->getName();
		if ( ! array_key_exists($modelName, $this->modelManagerPappets)) {
			$this->modelManagerPappets[$modelName] = new ModelManager($this->app, $this, $schema);
		}
	}

	/**
	 *
	 * */
	private function dropModelManagerPappet($schema) {
		unset($this->modelManagerPappets[$schema->getName()]);
	}
}


//======================================================================================================================


//======================================================================================================================
class DbCrudAdapterSysTable {
	private $table;
	private $cache;

	public function __construct($table) {
		$this->table = $table;
		$this->cache = [];
	}

	public function tableExists($service, $modelName) {
		return (bool)$this->getModelTableName($service, $modelName);
	}

	public function getModelTableName($service, $modelName) {
		$data = $this->getServiceData($service);
		if ( ! $data) {
			return null;
		}

		if ( ! array_key_exists($modelName, $data)) {
			return null;
		}

		return $data[$modelName]['table'];
	}

	public function defineModelTableName($service, $modelName) {
		$name = $this->getModelTableName($service, $modelName);
		if ( ! $name) {
			$snakeCase = lcfirst(preg_replace_callback('/(.)([A-Z])/', function($match) {
				return $match[1] . '_' . strtolower($match[2]) ;
			}, $modelName));

			$snakeCase = $this->avoidReservedNames($snakeCase);

			$i = 0;
			$tempName = $snakeCase;
			while ($this->table->getDb()->tableExists($tempName)) {
				$tempName = $snakeCase . (++$i);
			}

			$name = $tempName;
		}

		return $name;
	}

	public function createTable($service, $modelName, $tableName) {
		$serviceName = $service->name;
		$this->table->insert([
			'service_name' => $serviceName,
			'model_name' => $modelName,
			'table_name' => $tableName,
		], false);

		if ( ! array_key_exists($serviceName, $this->cache)) {
			$this->cache[$serviceName] = [];
		}

		$this->cache[$serviceName][$modelName] = [
			'table' => $tableName,
		];
	}

	public function deleteTable($service, $modelName, $tableName) {
		$serviceName = $service->name;
		$this->table->delete([
			'service_name' => $serviceName,
			'model_name' => $modelName,
			'table_name' => $tableName,
		]);

		unset($this->cache[$serviceName][$modelName]);
		if (empty($this->cache[$serviceName])) {
			unset($this->cache[$serviceName]);
		}
	}


	/**************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/

	private function getServiceData($service) {
		$this->loadServiceData($service);

		$serviceName = $service->name;
		if ( ! array_key_exists($serviceName, $this->cache)) {
			return null;
		}

		return $this->cache[$serviceName];
	}

	private function loadServiceData($service) {
		$serviceName = $service->name;
		if (array_key_exists($serviceName, $this->cache)) {
			return;
		}

		$data = $this->table->select('model_name, table_name', ['service_name' => $serviceName]);
		$this->cache[$serviceName] = [];
		foreach ($data as $row) {
			$this->cache[$serviceName][$row['model_name']] = [
				'table' => $row['table_name'],
			];
		}
	}

	private function avoidReservedNames($name) {
		switch ($name) {
			// Postgresql reserved words
			case 'user': return 'users';
		}

		return $name;
	}
}
