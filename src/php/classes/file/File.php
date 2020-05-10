<?php

namespace lx;

/**
 * Class File
 * @package lx
 */
class File extends BaseFile
{
	/** @var resource */
	protected $instance = null;

	/** @var string */
	protected $opened = '';

	/**
	 * File constructor.
	 * @param string $name
	 * @param string $path
	 */
	public function __construct($name, $path = null)
	{
		if ($path === null) {
			parent::__construct($name);
			return;
		}

		if ($path instanceof Directory) {
			$path = $path->getPath();
		}
		
		if ($path{-1} != '/') {
			$path .= '/';
		}
		
		parent::__construct($path . $name);
	}

	/**
	 * @return string
	 */
	public function getExtension()
	{
		$arr = explode('.', $this->name);
		if (count($arr) < 2) {
			return '';
		}

		return array_pop($arr);
	}

	/**
	 * @return string
	 */
	public function getCleanName()
	{
		$ext = $this->getExtension();
		if ($ext == '') {
			return $this->getName();
		}

		return preg_replace('/\.'.$ext.'$/', '', $this->getName());
	}

	/**
	 * Remove file
	 */
	public function remove()
	{
		if (!$this->exists()) {
			return;
		}

		unlink($this->getPath());
	}

	/**
	 * @param File $file
	 * @return bool
	 */
	public function copy($file)
	{
		$dir = $this->getParentDir();
		$dir->make();
		return copy($file->getPath(), $this->getPath());
	}

	/**
	 * @param string $path
	 * @return File|false
	 */
	public function clone($path)
	{
		$file = new File($path);
		if (!$file->copy($this)) {
			return false;
		}

		return $file;
	}

	/**
	 * @param string $flags
	 * @return $this|false
	 */
	public function open($flags = 'r')
	{
		if ($flags == 'r' && !$this->exists()) {
			return false;
		}

		if ($this->opened == $flags) {
			return $this;
		}

		if ($this->opened != '') {
			$this->close();
		}

		$this->instance = fopen($this->path, $flags);
		$this->opened = $flags;

		return $this;
	}

	/**
	 * You can open file before use this method multiple times and close after that
	 *
	 * @param mixed $info
	 * @param string $flags
	 * @return $this|false
	 */
	public function write($info, $flags='w')
	{
		if (is_array($info) || is_object($info)) {
			$info = json_encode($info);
		}
		if ( ! is_string($info)) {
			return false;
		}

		$this->checkCanSave();
		$opened = ($this->opened != '');
		if ($this->opened != $flags) {
			$this->open($flags);
		}

		fwrite($this->instance, $info);
		if ( ! $opened) {
			$this->close();
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function close()
	{
		if ($this->instance) {
			fclose($this->instance);
			$this->instance = null;
			$this->opened = '';
		}

		return $this;
	}

	/**
	 * Automatic file opening and closing for each using of the method
	 *
	 * @param mixed $info
	 * @param int $flags
	 * @return $this|false
	 */
	public function put($info, $flags = 0)
	{
		if (is_array($info) || is_object($info)) {
			$info = json_encode($info);
		} elseif (is_numeric($info)) {
		    $info = (string)$info;
        }

		if ( ! is_string($info)) {
			return false;
		}

		$this->checkCanSave();
		file_put_contents($this->path, $info, $flags);
		return $this;
	}

	/**
	 * @param mixed $info
	 * @return $this|false
	 */
	public function append($info)
	{
		return $this->put($info, FILE_APPEND);
	}

	/**
	 * Automatic file opening and closing for each using of the method
	 *
	 * @return false|string
	 */
	public function get()
	{
		return file_get_contents($this->path);
	}

	/**
	 * @param int $rowsCount
	 * @return string
	 */
	public function getTail($rowsCount)
	{
		$file = file($this->getPath());
		$count = count($file);
		$result = '';
		for ($i = max(0, $count - $rowsCount); $i < $count; $i++) {
			$result .= $file[$i];
		}
		
		return $result;
	}

	/**
	 * @param string $pattern
	 * @return bool|false
	 */
	public function match($pattern)
	{
		if ( ! $this->exists()) {
			return false;
		}
		
		if ($pattern{0} != '/') {
			$pattern = '/' . $pattern . '/';
		}
		
		$text = file_get_contents($this->path);
		return preg_match($pattern, $text);
	}

	/**
	 * @param string $pattern
	 * @param string $replacement
	 * @return bool
	 */
	public function replace($pattern, $replacement)
	{
		if ( ! $this->exists()) {
			return false;
		}
		
		$text = file_get_contents($this->path);
		if ($pattern{0} != '/') {
			$pattern = '/' . $pattern . '/';
		}
		$text = preg_replace($pattern, $replacement, $text);
		file_put_contents($this->path, $text);
		return true;
	}

	/**
	 * @return mixed
	 */
	public function load()
	{
		return require($this->path);
	}

	/**
	 * Make directories according to file path
	 */
	private function checkCanSave()
	{
		if ( ! file_exists($this->parentDir)) {
			mkdir($this->parentDir, 0777, true);
		}
	}
}
