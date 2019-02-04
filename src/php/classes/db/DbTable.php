<?php

namespace lx;

class DbTable {
	protected 
		$db,
		$name;

	public function __construct($name, $db) {
		$this->name = $name;
		$this->db = $db;
	}

	/**
	 * 
	 * */
	public function getDb() {
		return $this->db;
	}

	/**
	 * 
	 * */
	public function getName() {
		return $this->name;
	}

	/**
	 * 
	 * */
	public function schema($columns = DB::SHORT_SCHEME) {
		if ($columns == DB::SHORT_SCHEME)
			return DbTableSchemaProvider::get($this);
	
		return $this->db->tableSchema($this->name, $columns);
	}

	/**
	 *
	 * */
	public function pkName() {
		return $this->schema()['pk'];
	}

	/**
	 * 
	 * */
	public function select($fields = '*', $condition = null) {
		if (is_array($fields)) $fields = implode(',', $fields);
		$query = "SELECT $fields FROM {$this->name}";
		$condition = $this->parseCondition($condition);
		if ($condition) $query .= $condition;

		$result = $this->db->select($query);
		$result = $this->db->normalizeTypes($this, $result);
		return $result;
	}

	/**
	 *
	 * */
	public function insert($fields, $values, $returnId=true) {
		$query = 'INSERT INTO ' . $this->name . ' (' . implode(', ', $fields) . ')' . ' VALUES ';
		$valstr = [];
		if (!is_array($values[0])) $values = [$values];
		foreach ($values as $valueSet) {
			foreach ($valueSet as &$value) {
				$value = DB::valueForQuery($value);
			}
			unset($value);
			$valstr[] = '(' . implode(', ', (array)$valueSet) . ')';
		}
		$query .= implode(', ', $valstr);
		return $this->db->insert($query, $returnId);
	}

	/**
	 *
	 * */
	public function update($sets, $condition=null) {
		$temp = [];
		foreach ($sets as $field => $value) {
			$temp[] = $field . ' = ' . DB::valueForQuery($value);
		}
		$temp = implode(', ', $temp);

		$condition = $this->parseCondition($condition);
		$query = "UPDATE {$this->name} SET $temp";
		if ($condition !== null) $query .= $condition;

		$res = $this->db->query($query);
		return $res;
	}

	/**
	 *
	 * */
	public function delete($condition=null) {
		$condition = $this->parseCondition($condition);
		$query = 'DELETE FROM ' . $this->name;
		if ($condition !== null) $query .= $condition;
		$res = $this->db->query($query);
		return $res;
	}

	/**
	 * Парсим условие
	 * //todo пока только на AND
	 * */
	protected function parseCondition($condition) {
		if ($condition === null) return null;
		if (is_array($condition)) {
			$conds = [];
			foreach ($condition as $key => $value) {
				if (is_array($value)) {
					foreach ($value as &$val) $val = DB::valueForQuery($val);
					unset($val);
					$conds[] = $key . ' IN (' . implode(', ', $value) . ')';
				} else {
					$conds[] = $key . '=' . DB::valueForQuery($value);
				}
			}
			$condition = implode(' AND ', $conds);
		} 
		$condition = preg_replace('/^(WHERE |where |(?<!WHERE )|(?<!where ))/', ' WHERE ', $condition);
		return $condition;
	}
}
