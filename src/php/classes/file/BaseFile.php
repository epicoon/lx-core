<?php

namespace lx;

class BaseFile {
	const
		WRONG = 0,
		DIR = 1,
		FILE = 2;

	protected
		$path,
		$name,
		$parentDir;

	public function __construct($path) {
		$this->setPath($path);
	}

	public static function getFileOrDir($path) {
		if (is_dir($path)) {
			return new Directory($path);
		}

		if (is_file($path)) {
			return new File($path);
		}

		return null;
	}

	public function setPath($path) {
		$this->path = $path;
		$arr = explode('/', $path);

		$this->name = array_pop($arr);
		// если $path заканчивался на '/'
		if ($this->name == '') $this->name = array_pop($arr);

		$this->parentDir = implode('/', $arr);

		return $this;
	}

	public function getPath() {
		return $this->path;
	}

	public function getName() {
		return $this->name;
	}

	public function getParentDirPath() {
		return $this->parentDir;
	}

	public function getParentDir() {
		return (new Directory($this->parentDir));
	}

	public function exists() {
		return file_exists($this->path);
	}

	public function belongs($parent) {
		$path;
		if (is_string($parent)) {
			$path = $parent;
		} else {
			if ($parent instanceof Directory) {
				$path = $parent->getPath();
			} else {
				return false;
			}
		}

		$path = addcslashes($path, '/');
		return preg_match('/^' . $path . '/', $this->getPath());
	}

	public function getRelativePath($parent) {
		if (!$this->belongs($parent)) {
			return false;
		}

		$path;
		if (is_string($parent)) {
			$path = $parent;
		} else {
			if ($parent instanceof Directory) {
				$path = $parent->getPath();
			} else {
				return false;
			}
		}

		$selfPath = $this->getPath();
		$path = addcslashes($path, '/');
		return preg_replace('/^' . $path . '\//', '', $selfPath);
	}

	public function createdAt() {
		if (!$this->exists()) {
			return INF;
		}

		return filemtime($this->getPath());
	}

	public function isNewer($file) {
		return $this->createdAt() > $file->createdAt();
	}

	public function isOlder($file) {
		return $this->createdAt() < $file->createdAt();
	}

	public function isDir() {
		return is_dir($this->path);
	}

	public function isFile() {
		return is_file($this->path);
	}

	public function getType() {
		if ($this->isDir()) return self::DIR;
		if ($this->isFile()) return self::FILE;
		return self::WRONG;
	}

	public function rename($newName) {
		return $this->moveTo($this->parentDir, $newName);
	}

	/**
	 * Пересестить файл в переданную директорию
	 * @param $dir
	 * @param $newName - если задан перемещение будет с переименованием
	 * */
	public function moveTo($dir, $newName = null) {
		if ($newName === null) {
			$newName = $this->getName();
		}

		$dirPath = null;
		if (is_string($dir)) {
			$dirPath = $dir;
		} elseif ($dir instanceof Directory) {
			$dirPath = $dir->getPath();
		}

		$newPath = $dirPath . '/' . $newName;
		if (file_exists($newPath)) {
			return false;
		}
		$oldPath = $this->getPath();

		if (!rename($oldPath, $newPath)) {
			return false;
		}

		$this->path = $newPath;
		$this->name = $newName;
		$this->parentDir = $dirPath;

		return true;
	}

	public function toFileOrDir() {
		$type = $this->getType();
		switch ($type) {
			case self::WRONG : return null;
			case self::DIR   : return new Directory($this->path);
			case self::FILE  : return new File($this->path);
		}
		return null;
	}
}
