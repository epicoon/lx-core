<?php

namespace lx;

class ModelSchema extends ApplicationTool {
	protected
		$provider,
		$name,
		$pkName = null,
		$fieldNames = [],
		$fields = [],
		$relationNames = [],
		$relations = [];

	public function __construct($provider, $name, $schema) {
		parent::__construct($provider->app);
		$this->provider = $provider;
		$this->name = $name;

		$forbidden = isset($schema['forbidden']) ? $schema['forbidden'] : [];

		foreach ($schema['fields'] as $fieldName => $fieldData) {
			$this->fieldNames[] = $fieldName;

			if (!is_array($fieldData)) {
				$fieldData = $this->complementFieldData($fieldData);
			}

			if (array_search($fieldName, $forbidden) !== false) {
				$fieldData['forbidden'] = true;
			}

			if (array_key_exists('pk', $fieldData)) {
				if ($this->pkName === null) {
					$this->pkName = $fieldName;
				} else {
					unset($fieldData['pk']);
				}
			}

			$this->fields[$fieldName] = ModelField::create($fieldName, $fieldData);
		}

		if ($this->pkName === null) {
			$this->pkName = 'id';
			$this->fieldNames[] = $this->pkName;
			$this->fields[$this->pkName] = ModelField::create($this->pkName, ['pk' => true, 'type' => ModelField::TYPE_INTEGER_SLUG]);
		}

		if (isset($schema['relations']) && is_array($schema['relations'])) {
			foreach ($schema['relations'] as $relationName => $relationData) {
				$this->relationNames[] = $relationName;
				$this->relations[$relationName] = new ModelFieldRelation($relationName, $relationData);
			}
		}
	}

	/**
	 *
	 * */
	public function getProvider() {
		return $this->provider;
	}

	/**
	 *
	 * */
	public function getCrudAdapter() {
		return $this->getProvider()->getCrudAdapter($this->name);
	}

	/**
	 *
	 * */
	public function getManager() {
		return $this->provider->getManager($this->name);
	}

	public function getDefinitions($params = null) {
		$result = [];
		foreach ($this->fields as $name => $field) {
			$result[$name] = $field->getDefinition($params);
		}
		return $result;
	}

	public function getName() {
		return $this->name;
	}

	public function getRelativeSchema($relation) {
	    $relationName = is_string($relation)
            ? $relation
            : $relation->getName();

	    if ( ! array_key_exists($relationName, $this->relations)) {
	        return null;
        }

	    $relativeModelName = $this->relations[$relationName]->getRelativeModelName();
	    list($serviceName, $modelName) = $this->splitModelName($relativeModelName);
	    $manager = $this->app->getModelManager($serviceName, $modelName);
	    return $manager->getSchema();
    }

	public function pkName() {
		return $this->pkName;
	}

	public function fieldNames() {
		return $this->fieldNames;
	}

	public function field($name) {
		return $this->fields[$name];
	}

	public function pkField() {
		return $this->fields[$this->pkName()];
	}

	public function relationNames() {
		return $this->relationNames;
	}

	public function hasRelation($name) {
		return array_key_exists($name, $this->relations);
	}

	public function getRelations() {
		return $this->relations;
	}

	public function relation($name) {
		return $this->relations[$name];
	}

	public function confirmRelationName($modelName, $relationName) {
		if ($relationName) {
			if (array_key_exists($relationName, $this->relations)
				&& $this->relations[$relationName]->getRelativeModelName() == $modelName
			) {
				return $relationName;
			}

			return false;
		}

		$count = 0;
		$matchName = null;
		foreach ($this->relations as $name => $relation) {
			if ($relation->getRelativeModelName() == $modelName) {
				++$count;
				$matchName = $name;
			}
		}

		if ($count == 1 && $matchName) {
			return $matchName;
		}

		return false;
	}

	public function getRelationsForModel($model, $filter = []) {
		$modelName = is_string($model) ? $model : $model->getName();

		$result = [];
		foreach ($this->relations as $name => $relation) {
			if ($relation->getRelativeModelName() == $modelName) {
				if (empty($filter) || (array_search($name, $filter) !== false)) {
					$result[$name] = $relation;
				}
			}
		}

		return $result;
	}

	private function complementFieldData($data) {
		$result = [];
		if ($data === false || $data === true) {
			$result['type'] = 'boolean';
			$result['default'] = $data;
		} elseif (is_numeric($data)) {
			$result['type'] = 'integer';
			$result['default'] = $data;
		} elseif (is_string($data)) {
			$result['type'] = 'string';
			$result['default'] = $data;
		}
		return $result;
	}

    private function splitModelName($name) {
        $arr = explode('.', $name);
        if (count($arr) == 1) {
            return [$this->getProvider()->getService()->name, $name];
        }

        return $arr;
    }
}
