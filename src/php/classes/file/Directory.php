<?php

namespace lx;

use lx;

class Directory extends BaseFile implements DirectoryInterface
{
	const FIND_NAME = 1;
	const FIND_OBJECT = 2;

	public function scan(): Vector
	{
		return new Vector(scandir($this->path));
	}

	public function contains(string $name): bool
	{
		return file_exists($this->path . '/' . $name);
	}

	public function make(int $mode = 0777): void
	{
		if ($this->exists()) {
			return;
		}

		mkdir($this->getPath(), $mode, true);
	}

	public function makeDirectory(string $name, int $mode = 0777, bool $recursive = false): DirectoryInterface
	{
		$path = $this->getPath() . '/' . $name;
		mkdir($path, $mode, $recursive);
		return new self($path);
	}

	public function copy(DirectoryInterface $dir, bool $rewrite = false): bool
	{
		if ($this->exists()) {
			if (!$rewrite) {
				return false;
			}

			$this->remove();
			$this->make();
		}

		$dirPath = $dir->getPath();
		$selfPath = $this->getPath();
		if ($selfPath[-1] != '/') {
			$selfPath .= '/';
		}
		$files = $dir->getAllFiles();
		foreach ($files as $file) {
			$path = $selfPath . $file->getRelativePath($dirPath);
			$file->clone($path);
		}

		return true;
	}

	public function clone(string $path, bool $rewrite = false): ?DirectoryInterface
	{
		$dir = new Directory($path);
		if (!$dir->copy($this, $rewrite)) {
			return null;
		}

		return $dir;
	}

	public function getOrMakeDirectory(string $name, int $mode = 0777, bool $recursive = false): DirectoryInterface
	{
		if ($this->contains($name)) {
			return $this->get($name);
		}
		return $this->makeDirectory($name, $mode, $recursive);
	}

	public function makeFile(string $name, ?string $classOrInterface = null): FileInterface
	{
        if ($classOrInterface === null) {
            $classOrInterface = File::class;
        }

	    if (lx::$app && lx::$app->diProcessor) {
            return lx::$app->diProcessor->build()
                ->setClass($classOrInterface)
                ->setParams([$this->getPath() . '/' . $name])
                ->setDefaultClass(File::class)
                ->getInstance();
        }

	    return new File($this->getPath() . '/' . $name);
	}

	/**
	 * Recursive removing directory with content
	 */
	public function remove(): bool
	{
		if (!$this->exists()) {
			return true;
		}

		$dirDel = function($dir) use(&$dirDel) {
			$d = opendir($dir);
			while (($entry = readdir($d)) !== false) {
				if ($entry != '.' && $entry != '..') {
					$path = "$dir/$entry";
					if (is_dir($path)) {
						$dirDel($path);
					} else {
						unlink ($path);
					}
				}
			}
			closedir($d);
			if (!rmdir ($dir)) {
			    return false;
            }
			return true;
		};
		
		return $dirDel($this->getPath());
	}

	/**
	 * Files and/or directories wich are located in this directory
	 *
	 * $rules = [
	 *	'findType'  : Directory::FIND_OBJECT | Directory::FIND_NAME - return objects or names
     *  'fileClass' : string
	 *	'sort'      : SCANDIR_SORT_DESCENDING | SCANDIR_SORT_NONE - sort type
	 *	'mask'      : string - example '*.(php|js)'
	 * 	'all'       : bool - recursuve search
	 *	'files'     : bool - return only files
	 *	'dirs'      : bool - return only directories
	 *	'ext'       : bool - return names with/without extensions (for name returning only)
	 *	'fullname'  : bool - return full/self file names (for name returning only)
	 * ]
     * 
     * @return Vector<CommonFileInterface>|Vector<string>
	 */
	public function getContent(array $rules = []): Vector
	{
        $files = new Vector();
        if (!$this->exists()) {
            return $files;
        }
        
		$rules = DataObject::create($rules);
		$this->getContentRe($this->path, $rules, $files);

		if ($rules->findType != self::FIND_OBJECT) {
			if ($rules->ext === false) {
				$files->each(function($a, $i, $t) {
					$a = preg_replace('/\.[^.]*$/', '', $a);
					$t[$i] = $a;
				});
			}

			$dirPath = $this->path;
			if ($dirPath[-1] != '/') $dirPath .= '/';
			if ($rules->fullname) {
				$files->each(function($file, $i, $t) use ($dirPath) {
					$file = $dirPath . $file;
					$t[$i] = $file;
				});
			}
		}

		return $files;
	}

	public function get(string $name): ?CommonFileInterface
	{
		if ($name == '') {
			return $this;
		}

		if (!$this->contains($name)) {
			return null;
		}

		$path = $this->path . '/' . $name;
		return BaseFile::construct($path);
	}

    /**
     * @return Vector<FileInterface>
     */
	public function getFiles(?string $pattern = null): Vector
	{
		return $this->getContent([
            'findType' => self::FIND_OBJECT,
            'mask' => $pattern,
            'files' => true,
        ]);
	}

    /**
     * @return Vector<string>
     */
    public function getFileNames(?string $pattern = null): Vector
    {
        return $this->getContent([
            'findType' => self::FIND_NAME,
            'mask' => $pattern,
            'files' => true,
        ]);
    }

	/**
	 * @return Vector<FileInterface>
	 */
	public function getAllFiles(?string $pattern = null): Vector
	{
		return $this->getContent([
            'findType' => self::FIND_OBJECT,
            'mask' => $pattern,
            'files' => true,
            'all' => true,
        ]);
	}

    /**
     * @return Vector<string>
     */
    public function getAllFileNames(?string $pattern = null): Vector
    {
        return $this->getContent([
            'findType' => self::FIND_NAME,
            'mask' => $pattern,
            'files' => true,
            'all' => true,
        ]);
    }

	/**
	 * @return Vector<DirectoryInterface>
	 */
	public function getDirectories(): Vector
	{
		return $this->getContent([
            'findType' => self::FIND_OBJECT,
            'dirs' => true,
        ]);
	}

    /**
     * @return Vector<string>
     */
    public function getDirectoryNames(): Vector
    {
        return $this->getContent([
            'findType' => self::FIND_NAME,
            'dirs' => true,
        ]);
    }

	/**
	 * Recursive search
	 */
	public function find(string $filename): ?CommonFileInterface
	{
		$arr = explode('/', $filename);

		if (count($arr) == 1) {
			$f = self::staticFind($this->path, $filename);
		} else {
			$medName = array_shift($arr);
			$fn = implode('/', $arr);
			$f = self::staticFindExt($this->path, $medName, $fn);
		}

		if (!$f) {
			return null;
		}
		
		return BaseFile::construct($f);
	}

	/**
	 * @return Vector<FileInterface>
	 */
	public function getFilesByCallback(string $fileMask, callable $func): Vector
	{
		$arr = $this->getAllFiles($fileMask);
		$v = new Vector();
		$arr->each(function($file) use (&$v, $func) {
			if (!$func($file)) return;
			$v->push($file);
		});
		return $v;
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Example: '*.(php|js)' ==> ['*.php', '*.js']
	 */
	private function parseMask(string $mask): array
	{
		$temp = explode('.(', $mask);
		if (count($temp) < 2) $masks = [$mask];
		else {
			$exts = $temp[1];
			$exts = preg_replace('/\)$/', '', $exts);
			$exts = explode('|', $exts);
			$masks = [];
			foreach ($exts as $ext) {
				$masks[] = "{$temp[0]}.$ext";
			}
		}
		return $masks;
	}

	private static function staticFind(string $dirName, string $fileName): ?string
	{
		if (!file_exists($dirName)) {
			return null;
		}

		$files = array_diff(scandir($dirName), ['.', '..']);
		if ($dirName[-1] != '/') $dirName .= '/';
		foreach ($files as $f) {
			$path = $dirName . $f;
			if ($f == $fileName) return $path;
			if (is_dir($path)) {
				$res = self::staticFind($path, $fileName);
				if ($res) return $res;
			}
		}
		return null;
	}

	private static function staticFindExt(string $dirName, string $medDirName, string $fileName): ?string
	{
		if (!file_exists($dirName)) return null;
		$files = array_diff(scandir($dirName), ['.', '..']);
		if ($dirName[-1] != '/') $dirName .= '/';
		foreach ($files as $f) {
			$path = $dirName . $f;
			if ($f == $medDirName) {
				$fullPath = $path . '/' . $fileName;
				if (file_exists($fullPath)) {
					return $fullPath;
				}
			}
			if (is_dir($path)) {
				$res = self::staticFindExt($path, $medDirName, $fileName);
				if ($res) return $res;
			}
		}
		return null;
	}

	private function getContentRe(string $dirPath, DataObject $rules, Vector &$list): void
	{
		$findType = $rules->findType ?? self::FIND_OBJECT;

		$sort = $rules->sort ?? null;
		$arr = $sort
			? scandir($dirPath, $sort)
			: scandir($dirPath);

		if ($dirPath[-1] != '/') {
			$dirPath .= '/';
		}
		
		$masks = $rules->mask ? $this->parseMask($rules->mask) : null;
		$files = new Vector();
		foreach ($arr as $value) {
			if ($value == '.' || $value == '..') continue;

			$path = $dirPath . $value;
			if (is_dir($path)) {
				if (!$rules->files && $this->checkMasks($value, $masks)) {
					$files->push($this->getItem($value, $path, $findType, $rules->fileClass));
				}
				if ($rules->all) {
					$this->getContentRe($path, $rules, $files);
				}
			} else {
				if (!$rules->dirs && $this->checkMasks($value, $masks)) {
					$files->push($this->getItem($value, $path, $findType, $rules->fileClass));
				}
			}
		}

		$list->merge($files);
	}

	/**
	 * @return BaseFile|string|null
	 */
	private function getItem(string $name, string $fullPath, int $type, ?string $fileClass)
	{
		if ($type == self::FIND_OBJECT) {
		    if ($fileClass) {
                return lx::$app->diProcessor->create($fileClass, [$fullPath]);
            } else {
                return BaseFile::construct($fullPath);
            }
		}

		$thisPath = $this->path;
		if ($thisPath[-1] != '/') $thisPath .= '/';
		$path = str_replace($thisPath, '', $fullPath);
		return $path;
	}

	private function checkMasks(string $fileName, ?array $masks): bool
	{
		if (!$masks) {
			return true;
		}

		foreach ($masks as $mask) {
			if (fnmatch($mask, $fileName)) return true;
		}

		return false;
	}
}
