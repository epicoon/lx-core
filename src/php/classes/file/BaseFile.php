<?php

namespace lx;

abstract class BaseFile implements CommonFileInterface
{
	const WRONG = 0;
	const DIRECTORY = 1;
	const FILE = 2;

	protected string $path;
	protected string $name;
	protected string $parentDir;

	public function __construct(string $path)
	{
		$this->setPath($path);
	}

	abstract public function remove(): bool;

    public function setPath(string $path): void
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

	public static function construct(string $path): ?BaseFile
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

	public function getPath(): string
	{
		return $this->path;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getParentDirPath(): string
	{
		return $this->parentDir;
	}

	public function getParentDir(): Directory
	{
		return (new Directory($this->parentDir));
	}

	public function exists(): bool
	{
		return file_exists($this->path);
	}

	public function createLink(string $path): ?FileLink
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
	 */
	public function belongs($parent): bool
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
	 */
	public function getRelativePath($parent): ?string
	{
		if (!$this->belongs($parent)) {
			return null;
		}

		$path = null;
		if (is_string($parent)) {
			$path = $parent;
		} else {
			if ($parent instanceof Directory) {
				$path = $parent->getPath();
			} else {
				return null;
			}
		}

		$selfPath = $this->getPath();
		$path = addcslashes($path, '/');
		return preg_replace('/^' . $path . '\//', '', $selfPath);
	}

	public function createdAt(): int
	{
		if (!$this->exists()) {
			return INF;
		}

		return filemtime($this->getPath());
	}

	public function isNewer(BaseFile $file): bool
	{
		return $this->createdAt() > $file->createdAt();
	}

	public function isOlder(BaseFile $file): bool
	{
		return $this->createdAt() < $file->createdAt();
	}

	public function isDirectory(): bool
	{
		return is_dir($this->path);
	}

	public function isFile(): bool
	{
		return is_file($this->path);
	}

	public function getType(): int
	{
		if ($this->isDirectory()) return self::DIRECTORY;
		if ($this->isFile()) return self::FILE;
		return self::WRONG;
	}

	public function rename(string $newName): bool
	{
		return $this->moveTo($this->parentDir, $newName);
	}

	public function moveTo(Directory $dir, ?string $newName = null)
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
