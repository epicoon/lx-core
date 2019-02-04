<?php

namespace lx;

class ModelManager {
	private $crudTool = null;
	private $schema;
	private $models = [];

	public function __construct($crudTool, $schema) {
		$this->crudTool = $crudTool;
		$this->schema = $schema;
	}

	/**************************************************************************************************************************
		CRUD действия с одной моделью
	**************************************************************************************************************************/

	public function newModel() {
		return ModelData::getNew($this, 1);
	}

	public function loadModel($condition) {
		$field = $this->schema->pkField();
		if ($field->suitableType($condition) && array_key_exists($condition, $this->models)) {
			return $this->models[$condition];
		}

		$this->checkCrud();
		$modelData = ModelData::loadOne($this, $condition);
		if (!$modelData) return null;

		$this->cacheModel($modelData);
		return $modelData;


		// $this->checkCrud();
		// return ModelData::loadOne($this, $condition);
	}

	public function saveModel($model) {
		$this->checkCrud();
		$this->crudTool->saveModel($model);

		$this->cacheModel($model);


		// $this->checkCrud();
		// $this->crudTool->saveModel($model);
	}

	public function deleteModel($model) {
		if ($model->pk() === null) return;

		$this->checkCrud();
		$this->crudTool->deleteModel($model);

		$model->setPk(null);


		// $this->checkCrud();
		// $this->crudTool->deleteModel($model);
	}

	/**************************************************************************************************************************
		CRUD действия с множествами моделей
	**************************************************************************************************************************/

	/**
	 * Создание нескольких новых моделей
	 * @param $count - сколько моделей создать
	 * */
	public function newModels($count) {
		return ModelData::getNew($this, $count);
	}

	/**
	 * Загрузка нескольких моделей
	 * */
	public function loadModels($condition = null) {
		$this->checkCrud();
		return ModelData::load($this, $condition);
	}

	public function saveModels($arr) {
		$this->checkCrud();
		$this->crudTool->saveModels($this->schema, $arr);
	}

	public function deleteModels($arr) {
		$this->checkCrud();
		$this->crudTool->deleteModels($this->schema, $arr);
	}

	/**************************************************************************************************************************
		Всякое прочее
	**************************************************************************************************************************/

	public function getSchema() {
		return $this->schema;
	}

	public function cacheModel($model) {
		$this->models[$model->pk()] = $model;
	}

	public function uncacheModel($model) {
		unset($this->models[$model->pk()]);
	}

	public function resetModels() {
		$this->models = [];
	}

	/**
	 *
	 * */
	public function loadModelsData($condition = null) {
		$this->checkCrud();
		return $this->crudTool->loadModelsData($this->schema, $condition);
	}

	private function checkCrud() {
		if ($this->crudTool === null) {  //todo какой-нибудь интерфейс еще проверять
			throw new \Exception('ModelManager CRUD operation failed. There is no CRUD tool.', 400);
		}
	}
}
