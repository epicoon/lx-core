<?php

namespace lx;

abstract class ServiceResponse {
	public static function renderModule($module) {
		return new RenderServiceResponse($module);
	}

	abstract public function send();
}
