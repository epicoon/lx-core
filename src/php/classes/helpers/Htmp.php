<?php

namespace lx;

class Htmp {
	private $text;

	public function __construct($text) {
		$this->text = $text;
	}

	public function parse() {
		if (preg_match('/^HTMP_TEST!!!/', $this->text))
			return 'EEE';
			

		return $this->text;
	}
}
