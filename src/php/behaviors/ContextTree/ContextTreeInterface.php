<?php

namespace lx;

interface ContextTreeInterface {
	public function ContextTreeTrait($config = null);
	public function getHead();
	public function getKey();
	public function setKey($key);
	public function getParent();
	public function setParent($parent);
	public function getNested();
	public function isHead();
	public function add();
	public function eachContext($func);
}
