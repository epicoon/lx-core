<?php

namespace lx;

interface ContextTreeInterface {
	public function ContextTreeTrait($parent = null);
	public function getKey();
	public function getHead();
	public function getParent();
	public function getNested();
	public function nest($context);
	public function add();
	public function eachContext($func);
}
