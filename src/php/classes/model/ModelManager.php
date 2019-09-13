<?php

namespace lx;

class ModelManager extends ApplicationTool {
	private $crudTool = null;
	private $schema;

	public function __construct($app, $crudTool, $schema) {
		parent::__construct($app);
		$this->crudTool = $crudTool;
		$this->schema = $schema;
	}


	/**************************************************************************************************************************
	 * CRUD действия с одной моделью
	 *************************************************************************************************************************/

	public function newModel() {
		return ModelData::getNew($this, 1);
	}

	public function loadModel($condition) {
		$this->checkCrud();
		return $this->crudTool->loadModel($this->schema->getName(), $condition);
	}

	public function saveModel($model) {
		$this->checkCrud();
		$this->crudTool->saveModel($model);
	}

	public function deleteModel($model) {
		if ($model->pk() === null) return;

		$this->checkCrud();
		$this->crudTool->deleteModel($model);

		$model->setPk(null);
	}

	public function addRelations($model, $relation, $modelsList) {
		$this->checkCrud();
		$this->crudTool->addRelations($model, $relation, $modelsList);
	}

	public function loadRelations($model, $relation) {
		$this->checkCrud();
		return $this->crudTool->loadRelations($model, $relation);
	}

	public function delRelations($model, $relation, $modelsList) {
		$this->checkCrud();
		$this->crudTool->delRelations($model, $relation, $modelsList);
	}


	/**************************************************************************************************************************
	 * CRUD действия с множествами моделей
	 *************************************************************************************************************************/

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
		return $this->crudTool->loadModels($this->schema->getName(), $condition);
	}

	public function saveModels($arr) {
		$this->checkCrud();
		$this->crudTool->saveModels($arr);
	}

	public function deleteModels($arr) {
		$this->checkCrud();
		$this->crudTool->deleteModels($arr);
	}


	/**************************************************************************************************************************
	 * Всякое прочее
	 *************************************************************************************************************************/

	public function getModelName() {
		return $this->schema->getName();
	}

	public function getSchema() {
		return $this->schema;
	}

	public function getCrudAdapter() {
		return $this->crudTool;
	}

	public function getService() {
		return $this->crudTool->getService();
	}

	public function resetModels() {
		$this->models = [];
	}

	private function checkCrud() {
		if ($this->crudTool === null) {  //todo какой-нибудь интерфейс еще проверять
			throw new \Exception('ModelManager CRUD operation failed. There is no CRUD tool.', 400);
		}
	}
}
