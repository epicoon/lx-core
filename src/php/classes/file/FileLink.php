<?php

namespace lx;

class FileLink extends BaseFile implements FileLinkInterface
{
	public function create(CommonFileInterface $file): void
	{
        if ($this->exists()) {
            $this->remove();
        }

        symlink($file->getPath(), $this->getPath());
	}

	public function remove(): bool
	{
		if (!$this->exists()) {
			return true;
		}

		return unlink($this->getPath());
	}

	public function getFile(): ?CommonFileInterface
	{
		$path = readlink($this->getPath());
		if (!$path) {
			return null;
		}

		return BaseFile::construct($path);
	}
}
