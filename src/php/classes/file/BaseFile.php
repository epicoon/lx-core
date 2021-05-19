<?php

namespace lx;

/**
 * Class BaseFile
 * @package lx
 */
class BaseFile implements CommonFileInterface
{
	const WRONG = 0;
	const DIR = 1;
	const FILE = 2;

	/** @var string */
	protected $path;

	/** @var string */
	protected $name;

	/** @var string */
	protected $parentDir;

	/**
	 * BaseFile constructor.
	 * @param string $path
	 */
	public function __construct($path)
	{
		$this->setPath($path);
	}

	/**
	 * @param string $path
	 */
	public function setPath($path)
	{
        if ($path[0] == '@') {
            $path = \lx::$app->conductor->getFullPath($path);
        }

		$this->path = $path;
		$arr = explode('/', $path);

		$this->name = array_pop($arr);
		// If $path ends on '/'
		if ($this->name == '') $this->name = array_pop($arr);

		$this->parentDir = implode('/', $arr);
	}

	/**
	 * @param string $path
	 * @return BaseFile|null
	 */
	public static function construct($path)
	{
		if (is_link($path)) {
			return new FileLink($path);
		}

		if (is_dir($path)) {
			return new Directory($path);
		}

		if (is_file($path)) {
			return new File($path);
		}

		return null;
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getParentDirPath()
	{
		return $this->parentDir;
	}

	/**
	 * @return Directory
	 */
	public function getParentDir()
	{
		return (new Directory($this->parentDir));
	}

	/**
	 * @return bool
	 */
	public function exists()
	{
		return file_exists($this->path);
	}

    /**
     * @param string $path
     * @return FileLink|null
     */
	public function createLink($path)
	{
		if (!$this->exists()) {
			return null;
		}

		if ($path[0] != '/') {
			$path = $this->getParentDirPath() . '/' . $path;
		}

		$link = new FileLink($path);
		$link->create($this);
		return $link;
	}

	/**
	 * @param string|Directory $parent
	 * @return bool
	 */
	public function belongs($parent)
	{
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

	/**
	 * @param string|Directory $parent
	 * @return string|false
	 */
	public function getRelativePath($parent)
	{
		if (!$this->belongs($parent)) {
			return false;
		}

		$path = false;
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

	/**
	 * @return int
	 */
	public function createdAt()
	{
		if (!$this->exists()) {
			return INF;
		}

		return filemtime($this->getPath());
	}

	/**
	 * @param BaseFile $file
	 * @return bool
	 */
	public function isNewer($file)
	{
		return $this->createdAt() > $file->createdAt();
	}

	/**
	 * @param BaseFile $file
	 * @return bool
	 */
	public function isOlder($file)
	{
		return $this->createdAt() < $file->createdAt();
	}

	/**
	 * @return bool
	 */
	public function isDir()
	{
		return is_dir($this->path);
	}

	/**
	 * @return bool
	 */
	public function isFile()
	{
		return is_file($this->path);
	}

	/**
	 * @return int
	 */
	public function getType()
	{
		if ($this->isDir()) return self::DIR;
		if ($this->isFile()) return self::FILE;
		return self::WRONG;
	}

	/**
	 * @param string $newName
	 * @return bool
	 */
	public function rename($newName)
	{
		return $this->moveTo($this->parentDir, $newName);
	}

	/**
	 * @param Directory $dir
	 * @param string $newName
	 */
	public function moveTo($dir, $newName = null)
	{
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
}
