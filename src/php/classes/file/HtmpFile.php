<?php

namespace lx;

class HtmpFile extends File {
	public function get() {
		if (!$this->exists()) return null;

		$text = parent::get();
		return (new Htmp($text/*, $this->getParentDirPath()*/))->parse();
	}
}
