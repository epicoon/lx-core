<?php

namespace lx;

interface FusionInterface
{
	public function initFusionComponents($list, $defaults = []);
	public function getFusionComponent($name);
	public function getFusionComponentsDefaultConfig();
}
