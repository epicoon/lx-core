<?php

namespace lx;

interface FusionComponentInterface
{
	public function init($config = []);
	public function constructFusionComponent($owner, $config = []);
	public function getFusionComponentProperty($name);
}
