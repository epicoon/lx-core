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
	public function __construct($service, $modelName, $model, $actions, $path, $code) {
		$this->service = $service;
		$this->path = $path;
		$this->code = $code;
		$this->modelName = $modelName;
		$this->modelData = $model;
		$this->outerActions = $actions;

		$this->innerActions = [];
	}

	/**
	 *
	 * */
	public function run() {
		$this->parseInnerActions();

		if ($this->service->modelProvider->checkModelNeedTable($this->modelName)) {
			$this->correctInnerYaml();
			$data = (new YamlFile($this->path))->get();

			var_dump($data);

			// if (!$this->service->modelProvider->createTable($this->modelName, $data)) {
			// 	return false;
			// }
			// $this->createTableMigration();
		} else {
			if (!empty($this->innerActions)) {
				$correctResult = $this->service->modelProvider->correctModel(
					$this->modelName,
					$this->tableName,
					$this->innerActions,
					[]
				);
				if (!$correctResult) {
					return false;
				}

				$this->correctInnerYaml();
				$this->createInnerMigration();
			}
		}

		if (!empty($this->outerActions)) {
			$correctResult = $this->service->modelProvider->correctModel(
				$this->modelName,
				$this->tableName,
				[],
				$this->outerActions
			);
			if (!$correctResult) {
				return false;
			}

			$this->correctOuterYaml();
			$this->createOuterMigration();
		}

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

			// Проверка на удаление поля
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

			// Проверка на добавление поля
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
		// return;
		$code = $this->code;

		if (!empty($this->outerActions)) {
			$code = preg_replace('/!!(add|remove|query)[\w\W]*$/', '', $code);
		}

		$file = new File($this->path);
		$file->put($code);
	}

	/**
	 * //todo
	 * Сгенерировать миграции, отметить их выполненными
	 * */
	private function createInnerMigration() {

	}

	/**
	 * //todo
	 * Сгенерировать миграции, отметить их выполненными
	 * */
	private function createOuterMigration() {

	}
}
