<?php

namespace lx;

//todo \lx::getSetting('treeSeparator') это не дело

class Tree {
	public $absoluteRoot = -1,
		$root = -1,
		$branches = [],
		$keys = [],
		$key = '',
		$path = '',
		$data = null;

	public function __construct() {
		$arr = func_get_args();
		if ( !count($arr) ) return;

		if ( is_array($arr[0]) ) $arr = $arr[0];

		foreach ($arr as $item) $this->add($item);
	}

	public function rootNode() {
		return ($this->absoluteRoot == -1) ? $this : $this->absoluteRoot;
	}

	public function add() {
		$arr = func_get_args();
		$result = [];
		$sep = \lx::getSetting('treeSeparator');

		foreach ($arr as $id) {
			$temp = explode($sep, $id);
			$newId = array_pop($temp);
			$b = $this->get($temp);

			if ($b == null) return null;

			$newBr = 
			//($this instanceof DBtree) ? new DBtree() : 
			new Tree();

			$b->branches[$newId] = $newBr;
			$b->keys[] = $newId;
			$newBr->absoluteRoot = $this->rootNode();
			$newBr->key = $newId;
			$newBr->path = ($b->path) ? $b->path.$sep.$b->key : $b->key;
			$newBr->root = $b;
			$result[] = $newBr;
		}

		if ( count($result) == 1 ) $result = $result[0];
		return $result;
	}

	public function del($id = null) {
		if ($id === null) {
			if ($this->root == -1) return $this;
			$this->root->del( $this->key );
			return null;
		}

		$arr = explode(\lx::getSetting('treeSeparator'), $id);
		$delId = array_pop($arr);
		$b = $this->get($arr);

		if ($b == null) return $this;

		$index = array_search($delId, $b->keys);
		if ($index === false) return $this;
		else array_splice($b->keys, $index, 1);
		unset( $b->branches[$delId] );

		return $this;
	}

	public function get($id) {
		if ($id === '') return $this;

		// if (is_numeric($id)) return $this->branches[ $this->keys[$id] ];

		$arr = (is_array($id)) ? $id : explode(\lx::getSetting('treeSeparator'), $id);
		if (!count($arr)) return $this;
		if ( !isset($this->branches[ $arr[0] ]) ) return null;
		$b = $this->branches[ $arr[0] ];
		for ($i=1, $l=count($arr); $i<$l; $i++) {
			if ( !isset($b->branches[ $arr[$i] ]) ) return null;
			$b = $b->branches[ $arr[$i] ];
		}

		return $b;
	}

	public function clear() {
		$this->branches = [];
		$this->keys = [];
		return $this;
	}

	private function collectJSON(&$arr, $key, $root) {
		$index = count($arr);
		$temp = [
			'root' => $root,
			'data' => $this->data,
			'path' => $this->path
		];
		if ( property_exists($this, 'comment') ) $temp['comment'] = $this->comment;
		if ( property_exists($this, 'fill') ) $temp['fill'] = (int)$this->fill;
		if ($key !== '') $temp['key'] = $key;

		$arr[] = $temp;
		foreach ($this->keys as $key) {
		// for ($i=0, $l=count($this->keys); $i<$l; $i++)
			$this->branches[$key]->collectJSON($arr, $keys, $index);
		}
	}

	public function toJSON() {  // инфа только о вложенных узлах
		$arr = [];
		foreach ($this->keys as $key)
			$this->branches[$key]->collectJSON($arr, $key, -1);

		return json_encode($arr);
	}
}
