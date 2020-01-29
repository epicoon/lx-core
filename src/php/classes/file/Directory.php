<?php

namespace lx;

/*
	public function scan()
	public function contains($name)
	public function makeDirectory($name, $mode=0777, $recursive=false)
	public function makeFile($name, $type=null)
	public function getContent($rule = [])
	public function get($name, $cond=self::FIND_OBJECT)
	public function getFiles($pattern=null, $cond=self::FIND_OBJECT)
	public function getDirs($cond=self::FIND_OBJECT)
	public function find($filename, $flag = Directory::FIND_OBJECT)
	public function getAllFilesByPattern($pattern, $flag = Directory::FIND_OBJECT)
	public function getAllFiles($pattern, $flag = Directory::FIND_OBJECT)
	public function replaceInFiles($fileMask, $pattern, $replacement)
	public function getFilesByCallback($fileMask, $func, $flag = Directory::FIND_OBJECT)
	public function loadFile($name)
	public function fileContent($name)

	protected function parseMask($mask)

	private static function staticFind($dir, $tosearch)
*/
class Directory extends BaseFile {
	const
		FIND_NAME = 1,
		FIND_OBJECT = 2;

	/**
	 *
	 * */
	public function scan() {
		return new Vector(scandir($this->path));
	}

	/**
	 * Проверяет, содержит ли данный файл/каталог
	 * */
	public function contains($name) {
		return file_exists($this->path . '/' . $name);
	}
	
	public function make($mode=0777) {
		if ($this->exists()) {
			return;
		}

		mkdir($this->getPath(), $mode, true);
	}

	/**
	 *
	 * */
	public function makeDirectory($name, $mode=0777, $recursive=false) {
		$path = $this->getPath() . '/' . $name;
		mkdir($path, $mode, $recursive);
		return new self($path);
	}

	/**
	 *
	 * */
	public function getOrMakeDirectory($name, $mode=0777, $recursive=false) {
		if ($this->contains($name)) {
			return $this->get($name);
		}
		return $this->makeDirectory($name, $mode, $recursive);
	}

	/**
	 *
	 * */
	public function makeFile($name, $type=null) {
		if ($type === null) $type = File::class;
		return new $type($this->getPath() . '/' . $name);
	}

	/**
	 *
	 * */
	public function remove() {
		if (!$this->exists()) {
			return;
		}

		$dirDel = function($dir) use(&$dirDel) {
			$d = opendir($dir);
			while (($entry = readdir($d)) !== false) {
				if ($entry != '.' && $entry != '..') {
					$path = "$dir/$entry";
					if (is_dir($path)) {
						dirDel($path);
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
	 * Все файлы и(или) директории, непосредственно лежащие в директории
	 * $rules = [
	 *	'findType' : Directory::FIND_NAME | Directory::FIND_OBJECT - вернуть объекты или имена, по умолчанию - объекты
	 *	'sort'     : SCANDIR_SORT_DESCENDING | SCANDIR_SORT_NONE, если не установлен - сортировка в алфавитном порядке
	 *	'mask'     : string - различные маски для файлов, н-р '*.(php|js)'
	 * 	'all'      : boolean - пройти рекурсивно по подкаталогам
	 *	'files'    : boolean - вернуть только файлы, исключает 'dirs'
	 *	'dirs'     : boolean - вернуть только каталоги - не должно быть указано 'files'
	 *	'ext'      : boolean - только для возвращаемых имен - с расширениями / без расширений
	 *	'fullname' : boolean - только для возвращаемых имен - полные пути / неполные пути
	 * ]
	 * */
	public function getContent($rules = []) {
		$rules = DataObject::create($rules);
		$files = new Vector();
		$this->getContentRe($this->path, $rules, $files);

		// Правила только для FIND_NAME!
		if ($rules->findType != self::FIND_OBJECT) {
			// Правило - вернуть имена файлов без расширений
			if ($rules->ext === false) {
				$files->each(function($a, $i, $t) {
					$a = preg_replace('/\.[^.]*$/', '', $a);
					$t->set($i, $a);
				});
			}

			// Правило - вернуть полный путь к файлам
			$dirPath = $this->path;
			if ($dirPath[-1] != '/') $dirPath .= '/';
			if ($rules->fullname) {
				$files->each(function($file, $i, $t) use ($dirPath) {
					$file = $dirPath . $file;
					$t->set($i, $file);
				});
			}
		}

		return $files;
	}

	/**
	 * Вернет содержащийся файл/каталог
	 * */
	public function get($name, $cond=self::FIND_OBJECT) {
		if ($name == '') {
			return $this;
		}

		if (!$this->contains($name)) {
			return false;
		}

		$path = $this->path . '/' . $name;
		if ($cond == self::FIND_NAME) return $path;
		return (new BaseFile($path))->toFileOrDir();
	}

	/**
	 * Все файлы, непосредственно лежащие в директории, по регулярке + маска имени файла типа *.(php|js)
	 * */
	public function getFiles($pattern = null, $condition = self::FIND_OBJECT) {
		$rules = is_array($condition) ? $condition : ['findType' => $condition];
		$rules['mask'] = $pattern;
		$rules['files'] = true;
		return $this->getContent($rules);
	}

	/**
	 * Все файлы, лежащие во всех вложенных директориях, по регулярке + маска имени файла типа *.(php|js)
	 * */
	public function getAllFiles($pattern = null, $condition = Directory::FIND_OBJECT) {
		$rules = is_array($condition) ? $condition : ['findType' => $condition];
		$rules['mask'] = $pattern;
		$rules['files'] = true;
		$rules['all'] = true;
		return $this->getContent($rules);
	}

	/**
	 * Все директории, непосредственно лежащие в директории
	 * */
	public function getDirs($condition = self::FIND_OBJECT) {
		$rules = is_array($condition) ? $condition : ['findType' => $condition];
		$rules['dirs'] = true;
		return $this->getContent($rules);
	}

	/**
	 * Рекурсивный поиск файла, включая вложенные директории
	 * */
	public function find($filename, $flag = Directory::FIND_OBJECT) {
		$arr = explode('/', $filename);

		if (count($arr) == 1) {
			$f = self::staticFind($this->path, $filename);
		} else {
			$medName = array_shift($arr);
			$fn = implode('/', $arr);
			$f = self::staticFindExt($this->path, $medName, $fn);
		}

		if (!$f) return false;
		if ($flag == self::FIND_NAME) return $f;
		return (new BaseFile($f))->toFileOrDir();
	}

	/**
	 *
	 * */
	public function replaceInFiles($fileMask, $pattern, $replacement) {
		$arr = $this->getAllFiles($fileMask);
		$arr->each(function($file) use ($pattern, $replacement) {
			$file->replace($pattern, $replacement);
		});
	}

	/**
	 *
	 * */
	public function getFilesByCallback($fileMask, $func, $flag = Directory::FIND_OBJECT) {
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
	 *
	 * */
	public function loadFile($name) {
		$file = $this->get($name);
		if (!$file) $file = $this->find($name);
		if ($file instanceof File) return $file->load();
		return null;
	}

	/**
	 *
	 * */
	public function fileContent($name) {
		$file = $this->get($name);
		if (!$file) $file = $this->find($name);
		if ($file instanceof File) return $file->get();
		return null;
	}

	/**
	 * Парсит маску имени файла, н-р '*.(php|js)', приводя к массиву ['*.php', '*.js']
	 * */
	protected function parseMask($mask) {
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
	 * Рекурсивный поиск файла, включая вложенные директории
	 * */
	private static function staticFind($dirName, $fileName) {
		if (!file_exists($dirName)) return false;
		$files = array_diff(scandir($dirName), ['.', '..']);
		if ($dirName[strlen($dirName) - 1] != '/') $dirName .= '/';
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

	private static function staticFindExt($dirName, $medDirName, $fileName) {
		if (!file_exists($dirName)) return false;
		$files = array_diff(scandir($dirName), ['.', '..']);
		if ($dirName[strlen($dirName) - 1] != '/') $dirName .= '/';
		if ($medDirName[strlen($medDirName) - 1] != '/') $medDirName .= '/';
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
	 * @param $dirPath
	 * @param $rules
	 * @param $list
	 */
	private function getContentRe($dirPath, $rules, &$list) {
		// Тип возвращаемых данных - объекты или текстовые имена
		$findType = $rules->findType ?? self::FIND_OBJECT;

		// Правило сортировки
		$sort = $rules->sort ?? null;
		$arr = $sort
			? scandir($dirPath, $sort)
			: scandir($dirPath);

		if ($dirPath[-1] != '/') $dirPath .= '/';
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
	 * @param $name
	 * @param $fullPath
	 * @param $type
	 * @return Directory|File|mixed|null
	 */
	private function getItem($name, $fullPath, $type) {
		if ($type == self::FIND_OBJECT) {
			return BaseFile::getFileOrDir($fullPath);
		}

		$thisPath = $this->path;
		if ($thisPath[-1] != '/') $thisPath .= '/';
		$path = str_replace($thisPath, '', $fullPath);
		return $path;
	}

	/**
	 * @param $fileName
	 * @param $masks
	 * @return bool
	 */
	private function checkMasks($fileName, $masks) {
		if (!$masks) {
			return true;
		}

		foreach ($masks as $mask) {
			if (fnmatch($mask, $fileName)) return true;
		}
	}
}
