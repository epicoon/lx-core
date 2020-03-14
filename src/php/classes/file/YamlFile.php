<?php

namespace lx;

/**
 * Class YamlFile
 * @package lx
 */
class YamlFile extends File
{
	/**
	 * @return array|false|null
	 */
	public function get()
	{
		if ( ! $this->exists()) {
			return null;
		}

		$text = parent::get();
		return (new Yaml($text, $this->getParentDirPath()))->parse();
	}
}
