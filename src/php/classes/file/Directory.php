<?php

namespace lx;

/*
	public function scan()
	public function contain($name)
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
	public function contain($name) {
		return file_exists($this->path . '/' . $name);
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
		if ($this->contain($name)) {
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
	 * $rule = [
	 *	'findType' : Directory::FIND_NAME | Directory::FIND_OBJECT - вернуть объекты или имена, по умолчанию - объекты
	 *	'sort'     : SCANDIR_SORT_DESCENDING | SCANDIR_SORT_NONE, если не установлен - сортировка в алфавитном порядке
	 *	'mask'     : string - различные маски для файлов, н-р '*.(php|js)'
	 *	'files'    : boolean - вернуть только файлы, исключает 'dirs'
	 *	'dirs'     : boolean - вернуть только каталоги - не должно быть указано 'files'
	 *	'ext'      : boolean - только для возвращаемых имен - с расширениями / без расширений
	 *	'fullname' : boolean - только для возвращаемых имен - полные пути / неполные пути
	 * ]
	 * */
	public function getContent($rule = []) {
		// Тип возвращаемых данных - объекты или текстовые имена
		$findType = isset($rule['findType'])
			? $rule['findType']
			: self::FIND_OBJECT;

		// Правило сортировки
		$sort = isset($rule['sort']) ? $rule['sort'] : null;
		$arr = $sort
			? scandir($this->path, $sort)
			: scandir($this->path);

		// Фильтр имен файлов по маске
		if (isset($rule['mask'])) {
			$temp = $arr;
			$arr = [];
			$masks = $this->parseMask($rule['mask']);
			foreach ($temp as $fileName) {
				foreach ($masks as $mask) {
					if (fnmatch($mask, $fileName)) $arr[] = $fileName;
				}
			}
		}

		$files = new Vector();
		if (isset($rule['files']) && $rule['files']) {
			foreach ($arr as $value) {
				$path = $this->path . '/' . $value;
				if (is_dir($path)) continue;
				if ($findType == self::FIND_OBJECT) $value = new File($path);
				$files->push($value);
			}
		} else if (isset($rule['dirs']) && $rule['dirs']) {
			foreach ($arr as $value) {
				$path = $this->path . '/' . $value;
				if (!is_dir($path) || $value == '.' || $value == '..') continue;
				if ($findType == self::FIND_OBJECT) $value = new Directory($path);
				$files->push($value);
			}
		} else {
			if ($findType == self::FIND_OBJECT) {
				foreach ($arr as $value) {
					if ($value == '.' || $value == '..') continue;
					$path = $this->path . '/' . $value;
					$files->push( (new BaseFile($path))->toFileOrDir() );
				}
			} else $files->init(array_diff($arr, ['.', '..']));
		}

		// Правила только для FIND_NAME!
		if ($findType == self::FIND_NAME) {
			// Правило - вернуть имена файлов без расширений
			if (isset($rule['ext']) && !$rule['ext']) {
				$files->each(function($a, $i, $t) {
					$a = preg_replace('/\.[^.]*$/', '', $a);
					$t->set($i, $a);
				});
			}
			// Правило - вернуть полный путь к файлам
			if (isset($rule['fullname']) && $rule['fullname']) {
				$files->each(function($a, $i, $t) {
					$a = $this->path . $a;
					$t->set($i, $a);
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

		if (!$this->contain($name)) {
			return false;
		}

		$path = $this->path . '/' . $name;
		if ($cond == self::FIND_NAME) return $path;
		return (new BaseFile($path))->toFileOrDir();
	}

	/**
	 * Все файлы, непосредственно лежащие в директории, по регулярке + маска имени файла типа *.(php|js)
	 * */
	public function getFiles($pattern=null, $cond=self::FIND_OBJECT) {
		$rules = is_array($cond)
			? $cond
			: ['findType' => $cond];
		$rules['files'] = true;
		if ($pattern !== null) $rules['mask'] = $pattern;

		return $this->getContent($rules);
	}

	/**
	 * Все директории, непосредственно лежащие в директории
	 * */
	public function getDirs($cond=self::FIND_OBJECT) {
		$rules = [
			'findType' => $cond,
			'dirs' => true
		];

		return $this->getContent($rules);
	}

	/**
	 * Рекурсивный поиск файла, включая вложенные директории
	 * */
	public function find($filename, $flag = Directory::FIND_OBJECT) {
		$arr = explode('/', $filename);
		$fn = array_pop($arr);
		$path = (count($arr))
				? $this->path . '/' . implode('/', $arr)
				: $this->path;
		$f = self::staticFind($path, $fn);
		if (!$f) return false;
		if ($flag == self::FIND_NAME) return $f;
		return (new BaseFile($f))->toFileOrDir();
	}

	/**
	 * Все файлы, лежащие во всех вложенных директориях, по регулярке
	 * */
	public function getAllFilesByPattern($pattern, $flag = Directory::FIND_OBJECT) {
		$rules = is_array($flag)
			? $flag
			: ['findType' => $flag];

		$findAllRec = function($pattern, $basePath, $path, &$arr) use (&$findAllRec) {
			$dirs = [];
			$handle = opendir($basePath . $path);
			if (!$handle) return;
			while ($fn = readdir($handle)) {
				if ($fn == '.' || $fn == '..') continue;
				if ($path != '' && !preg_match('/\/$/', $path)) $path .= '/';
				$fullname = $basePath . $path . $fn;
				if (is_dir($fullname)) $dirs[] = $path . $fn;
				else if (fnmatch($pattern, $fn)) $arr[] = $path . $fn;
			}
			closedir($handle);

			foreach ($dirs as $name) $findAllRec($pattern, $basePath, $name, $arr);
		};

		$arr = [];
		$basePath = $this->path;
		if (!preg_match('/\/$/', $basePath)) $basePath .= '/';
		$findAllRec($pattern, $basePath, '', $arr);
		if ($flag == self::FIND_NAME) return new Vector($arr);
		foreach ($arr as &$fn) $fn = new File($basePath . $fn);
		unset($fn);
		return new Vector($arr);
	}

	/**
	 * Все файлы, лежащие во всех вложенных директориях, по регулярке + маска имени файла типа *.(php|js)
	 * */
	public function getAllFiles($pattern, $flag = Directory::FIND_OBJECT) {
		$masks = $this->parseMask($pattern);
		$v = new Vector();
		foreach ($masks as $mask)
			$v->merge( $this->getAllFilesByPattern($mask, $flag) );
		return $v;
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
	private static function staticFind($dir, $tosearch) {
		if (!file_exists($dir)) return false;
		$files = array_diff(scandir($dir), ['.', '..']);
		if ($dir[strlen($dir) - 1] != '/') $dir .= '/';
		foreach ($files as $d) {
			$path = $dir . $d; 
			if ($d == $tosearch) return $path;
			if (is_dir($path)) {
				$res = self::staticFind($dir . $d, $tosearch);
				if ($res) return $res;
			}

			// if (!is_dir($dir . $d)) {
			// 	if ($d == $tosearch)
			// 	return $dir . $d;
			// } else {
			// 	$res = self::staticFind($dir . $d, $tosearch);
			// 	if ($res) return $res;
			// }
		}
		return false;
	}
}
