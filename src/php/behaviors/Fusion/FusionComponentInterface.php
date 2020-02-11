<?php

namespace lx;

interface FusionComponentInterface
{
	public function constructFusionComponent($config = []);
	public function initAsFusionComponent($config = []);
	public function getFusionComponentProperty($name);
}
