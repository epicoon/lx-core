<?php

namespace lx;

use lx;

abstract class BaseFile implements CommonFileInterface
{
	const WRONG = 0;
	const DIRECTORY = 1;
	const FILE = 2;
    const LINK = 3;

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

	public static function construct(string $path): ?CommonFileInterface
	{
        $builder = lx::$app->diProcessor->build()->setParams([$path]);
        if (is_link($path)) {
            $builder
                ->setInterface(FileLinkInterface::class)
                ->setDefaultClass(FileLink::class);
        } elseif (is_dir($path)) {
            $builder
                ->setInterface(DirectoryInterface::class)
                ->setDefaultClass(Directory::class);
        } elseif (is_file($path)) {
            $builder
                ->setInterface(FileInterface::class)
                ->setDefaultClass(File::class);
        } else {
            return null;
        }
        return $builder->getInstance();
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
        return lx::$app->diProcessor->build()
            ->setParams([$this->parentDir])
            ->setInterface(DirectoryInterface::class)
            ->setDefaultClass(Directory::class)
            ->getInstance();
	}

	public function exists(): bool
	{
		return file_exists($this->path);
	}

	public function createLink(string $path): ?FileLinkInterface
	{
		if (!$this->exists()) {
			return null;
		}

		if ($path[0] != '/') {
			$path = $this->getParentDirPath() . '/' . $path;
		}

        /** @var FileLinkInterface $link */
        $link = lx::$app->diProcessor->build()
            ->setParams([$path])
            ->setInterface(FileLinkInterface::class)
            ->setDefaultClass(FileLink::class)
            ->getInstance();
		$link->create($this);
		return $link;
	}

	/**
	 * @param string|Directory $parent  //TODO since 8.0 : \Stringable + make all files \Stringable with __toString() == getPath()
	 */
	public function belongs($parent): bool
	{
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
	 * @param string|Directory $parent  //TODO since 8.0 : \Stringable + make all files \Stringable with __toString() == getPath()
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

	public function isNewer(CommonFileInterface $file): bool
	{
		return $this->createdAt() > $file->createdAt();
	}

	public function isOlder(CommonFileInterface $file): bool
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

    public function isLink(): bool
    {
        return is_link($this->path);
    }

	public function getType(): int
	{
		if ($this->isDirectory()) return self::DIRECTORY;
		if ($this->isFile()) return self::FILE;
        if ($this->isLink()) return self::LINK;
		return self::WRONG;
	}

	public function rename(string $newName): bool
	{
		return $this->moveTo($this->parentDir, $newName);
	}

	public function moveTo(DirectoryInterface $dir, ?string $newName = null)
	{
		if ($newName === null) {
			$newName = $this->getName();
		}

		$dirPath = null;
		if (is_string($dir)) {
			$dirPath = $dir;
		} elseif ($dir instanceof DirectoryInterface) {
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
