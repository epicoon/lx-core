<?php

namespace lx;

use lx;

/**
 * Class Directory
 * @package lx
 */
class Directory extends BaseFile
{
	const FIND_NAME = 1;
	const FIND_OBJECT = 2;

	/**
	 * @return Vector
	 */
	public function scan()
	{
		return new Vector(scandir($this->path));
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function contains($name)
	{
		return file_exists($this->path . '/' . $name);
	}

	/**
	 * @param int $mode
	 */
	public function make($mode = 0777)
	{
		if ($this->exists()) {
			return;
		}

		mkdir($this->getPath(), $mode, true);
	}

	/**
	 * @param string $name
	 * @param int $mode
	 * @param bool $recursive
	 * @return Directory
	 */
	public function makeDirectory($name, $mode = 0777, $recursive = false)
	{
		$path = $this->getPath() . '/' . $name;
		mkdir($path, $mode, $recursive);
		return new self($path);
	}

	/**
	 * @param Directory $dir
	 * @param bool $rewrite
	 * @return bool
	 */
	public function copy($dir, $rewrite = false)
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
		if ($selfPath{-1} != '/') {
			$selfPath .= '/';
		}
		$files = $dir->getAllFiles();
		foreach ($files as $file) {
			$path = $selfPath . $file->getRelativePath($dirPath);
			$file->clone($path);
		}

		return true;
	}

	/**
	 * @param string $path
	 * @param bool $rewrite
	 * @return Directory|false
	 */
	public function clone($path, $rewrite = false)
	{
		$dir = new Directory($path);
		if (!$dir->copy($this, $rewrite)) {
			return false;
		}

		return $dir;
	}

	/**
	 * @param string $name
	 * @param int $mode
	 * @param bool $recursive
	 * @return Directory
	 */
	public function getOrMakeDirectory($name, $mode = 0777, $recursive = false)
	{
		if ($this->contains($name)) {
			return $this->get($name);
		}
		return $this->makeDirectory($name, $mode, $recursive);
	}

	/**
	 * @param string $name
	 * @param string $type - File class name or interface name
	 * @return File
	 */
	public function makeFile($name, $type = null)
	{
	    if (lx::$app && lx::$app->diProcessor) {
            return lx::$app->diProcessor->createByInterface(
                $type,
                [$this->getPath() . '/' . $name],
                [],
                File::class
            );
        }

	    return new File($this->getPath() . '/' . $name);
	}

	/**
	 * Recursive removing directory with content
	 */
	public function remove()
	{
		if ( ! $this->exists()) {
			return;
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
			rmdir ($dir);
		};
		$dirDel($this->getPath());
	}

	/**
	 * Files and/or directories wich are located in this directory
	 *
	 * @param array $rules
	 * @return Vector
	 *
	 * $rules = [
	 *	'findType' : Directory::FIND_OBJECT | Directory::FIND_NAME - return objects or names
	 *	'sort'     : SCANDIR_SORT_DESCENDING | SCANDIR_SORT_NONE - sort type
	 *	'mask'     : string - example '*.(php|js)'
	 * 	'all'      : boolean - recursuve search
	 *	'files'    : boolean - return only files
	 *	'dirs'     : boolean - return only directories
	 *	'ext'      : boolean - return names with/without extensions (for name returning only)
	 *	'fullname' : boolean - return full/self file names (for name returning only)
	 * ]
	 */
	public function getContent($rules = [])
	{
		$rules = DataObject::create($rules);
		$files = new Vector();
		$this->getContentRe($this->path, $rules, $files);

		if ($rules->findType != self::FIND_OBJECT) {
			if ($rules->ext === false) {
				$files->each(function($a, $i, $t) {
					$a = preg_replace('/\.[^.]*$/', '', $a);
					$t[$i] = $a;
				});
			}

			$dirPath = $this->path;
			if ($dirPath{-1} != '/') $dirPath .= '/';
			if ($rules->fullname) {
				$files->each(function($file, $i, $t) use ($dirPath) {
					$file = $dirPath . $file;
					$t[$i] = $file;
				});
			}
		}

		return $files;
	}

	/**
	 * @param string $name
	 * @param int $cond
	 * @return BaseFile|string|null
	 */
	public function get($name, $cond=self::FIND_OBJECT)
	{
		if ($name == '') {
			return $this;
		}

		if ( ! $this->contains($name)) {
			return null;
		}

		$path = $this->path . '/' . $name;
		if ($cond == self::FIND_NAME) {
			return $path;
		}

		return BaseFile::construct($path);
	}

	/**
	 * @param string $pattern
	 * @param array|int $condition
	 * @return Vector
	 */
	public function getFiles($pattern = null, $condition = self::FIND_OBJECT)
	{
		$rules = is_array($condition) ? $condition : ['findType' => $condition];
		$rules['mask'] = $pattern;
		$rules['files'] = true;
		return $this->getContent($rules);
	}

	/**
	 * @param string $pattern
	 * @param array|int $condition
	 * @return Vector
	 */
	public function getAllFiles($pattern = null, $condition = Directory::FIND_OBJECT)
	{
		$rules = is_array($condition) ? $condition : ['findType' => $condition];
		$rules['mask'] = $pattern;
		$rules['files'] = true;
		$rules['all'] = true;
		return $this->getContent($rules);
	}

	/**
	 * @param array|int $condition
	 * @return Vector
	 */
	public function getDirs($condition = self::FIND_OBJECT)
	{
		$rules = is_array($condition) ? $condition : ['findType' => $condition];
		$rules['dirs'] = true;
		return $this->getContent($rules);
	}

	/**
	 * Recursive search
	 *
	 * @param string $filename
	 * @param int $flag
	 * @return BaseFile|string|null
	 */
	public function find($filename, $flag = Directory::FIND_OBJECT)
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

		if ($flag == self::FIND_NAME) {
			return $f;
		}

		return BaseFile::construct($f);
	}

	/**
	 * @param string $fileMask
	 * @param string $pattern
	 * @param string $replacement
	 */
	public function replaceInFiles($fileMask, $pattern, $replacement)
	{
		$arr = $this->getAllFiles($fileMask);
		$arr->each(function($file) use ($pattern, $replacement) {
			$file->replace($pattern, $replacement);
		});
	}

	/**
	 * @param string $fileMask
	 * @param callable $func
	 * @param int $flag
	 * @return Vector
	 */
	public function getFilesByCallback($fileMask, $func, $flag = Directory::FIND_OBJECT)
	{
		$arr = $this->getAllFiles($fileMask);
		$v = new Vector();
		$arr->each(function($file) use (&$v, $func, $flag) {
			if (!$func($file)) return;
			if ($flag == Directory::FIND_OBJECT) $v->push($file);
			else $v->push($file->getPath());
		});
		return $v;
	}

	/**
	 * @param string $name
	 * @return mixed|null
	 */
	public function loadFile($name)
	{
		$file = $this->get($name);
		if (!$file) $file = $this->find($name);
		if ($file instanceof File) return $file->load();
		return null;
	}

	/**
	 * @param string $name
	 * @return mixed|null
	 */
	public function fileContent($name)
	{
		$file = $this->get($name);
		if (!$file) $file = $this->find($name);
		if ($file instanceof File) return $file->get();
		return null;
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * Example: '*.(php|js)' ==> ['*.php', '*.js']
	 *
	 * @param string $mask
	 * @return array
	 */
	private function parseMask($mask)
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

	/**
	 * @param string $dirName
	 * @param string $fileName
	 * @return string|false
	 */
	private static function staticFind($dirName, $fileName)
	{
		if ( ! file_exists($dirName)) {
			return false;
		}

		$files = array_diff(scandir($dirName), ['.', '..']);
		if ($dirName{-1} != '/') $dirName .= '/';
		foreach ($files as $f) {
			$path = $dirName . $f;
			if ($f == $fileName) return $path;
			if (is_dir($path)) {
				$res = self::staticFind($path, $fileName);
				if ($res) return $res;
			}
		}
		return false;
	}

	/**
	 * @param string $dirName
	 * @param string $medDirName
	 * @param string $fileName
	 * @return string|false
	 */
	private static function staticFindExt($dirName, $medDirName, $fileName)
	{
		if (!file_exists($dirName)) return false;
		$files = array_diff(scandir($dirName), ['.', '..']);
		if ($dirName{-1} != '/') $dirName .= '/';
		if ($medDirName{-1} != '/') $medDirName .= '/';
		foreach ($files as $f) {
			$path = $dirName . $f;
			if ($f == $medDirName) {
				$fullPath = $path . $fileName;
				if (file_exists($fullPath)) {
					return $fullPath;
				}
			}
			if (is_dir($path)) {
				$res = self::staticFindExt($path, $medDirName, $fileName);
				if ($res) return $res;
			}
		}
		return false;
	}

	/**
	 * @param string $dirPath
	 * @param DataObject $rules
	 * @param Vector $list
	 */
	private function getContentRe($dirPath, $rules, &$list)
	{
		$findType = $rules->findType ?? self::FIND_OBJECT;

		$sort = $rules->sort ?? null;
		$arr = $sort
			? scandir($dirPath, $sort)
			: scandir($dirPath);

		if ($dirPath{-1} != '/') {
			$dirPath .= '/';
		}
		
		$masks = $rules->mask ? $this->parseMask($rules->mask) : null;
		$files = new Vector();
		foreach ($arr as $value) {
			if ($value == '.' || $value == '..') continue;

			$path = $dirPath . $value;
			if (is_dir($path)) {
				if (!$rules->files && $this->checkMasks($value, $masks)) {
					$files->push($this->getItem($value, $path, $findType));
				}
				if ($rules->all) {
					$this->getContentRe($path, $rules, $files);
				}
			} else {
				if (!$rules->dirs && $this->checkMasks($value, $masks)) {
					$files->push($this->getItem($value, $path, $findType));
				}
			}
		}

		$list->merge($files);
	}

	/**
	 * @param string $name
	 * @param string $fullPath
	 * @param int $type
	 * @return BaseFile|string|null
	 */
	private function getItem($name, $fullPath, $type)
	{
		if ($type == self::FIND_OBJECT) {
			return BaseFile::construct($fullPath);
		}

		$thisPath = $this->path;
		if ($thisPath{-1} != '/') $thisPath .= '/';
		$path = str_replace($thisPath, '', $fullPath);
		return $path;
	}

	/**
	 * @param string $fileName
	 * @param array $masks
	 * @return bool
	 */
	private function checkMasks($fileName, $masks)
	{
		if ( ! $masks) {
			return true;
		}

		foreach ($masks as $mask) {
			if (fnmatch($mask, $fileName)) return true;
		}
	}
}
