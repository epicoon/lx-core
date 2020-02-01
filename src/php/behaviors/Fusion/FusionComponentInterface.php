<?php

namespace lx;

interface FusionComponentInterface
{
	public function constructFusionComponent($owner, $config = []);
	public function initAsFusionComponent($config = []);
	public function getFusionComponentProperty($name);
}
