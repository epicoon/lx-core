<?php

namespace lx;

class RequestButton extends Button {
	public function __construct($config=[]) {
		$config = Data::create($config);
		parent::__construct($config);

		if ($config->respondent) $this->respondent = $config->respondent;
		if ($config->onResponse) $this->onResponse = $config->onResponse;
		if ($config->fields) $this->fields = $config->fields;
	}	
}
