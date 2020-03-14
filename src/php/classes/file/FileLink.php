<?php

namespace lx;

class FileLink extends BaseFile
{
	/**
	 * @param BaseFile $file
	 */
	public function create($file)
	{
		if ($this->exists()) {
			$this->remove();
		}

		symlink($file->getPath(), $this->getPath());
	}

	/**
	 * Remove symlink
	 */
	public function remove()
	{
		if (!$this->exists()) {
			return;
		}

		unlink($this->getPath());
	}

	/**
	 * @return BaseFile|null
	 */
	public function getFile()
	{
		$path = readlink($this->getPath());
		if (!$path) {
			return null;
		}

		return BaseFile::construct($path);
	}
}
