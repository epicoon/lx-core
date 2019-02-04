<?php

namespace lx;

class YamlFile extends File {
	public function get() {
		if (!$this->exists()) return null;

		$text = parent::get();
		return (new Yaml($text, $this->getParentDirPath()))->parse();
	}
}
