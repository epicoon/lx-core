<?php

namespace lx;

class File extends BaseFile implements FileInterface
{
	/** @var resource */
	protected $instance = null;
	protected string $opened = '';

	public function __construct(string $name, ?string $path = null)
	{
		if ($path === null) {
			parent::__construct($name);
			return;
		}

		if ($path instanceof Directory) {
			$path = $path->getPath();
		}
		
		if ($path[-1] != '/') {
			$path .= '/';
		}
		
		parent::__construct($path . $name);
	}

	public function getExtension(): string
	{
		$arr = explode('.', $this->name);
		if (count($arr) < 2) {
			return '';
		}

		return array_pop($arr);
	}

	public function getCleanName(): string
	{
		$ext = $this->getExtension();
		if ($ext == '') {
			return $this->getName();
		}

		return preg_replace('/\.'.$ext.'$/', '', $this->getName());
	}

	public function remove(): bool
	{
		if (!$this->exists()) {
			return true;
		}

		return unlink($this->getPath());
	}

 	public function copy(FileInterface $file): bool
	{
		$dir = $this->getParentDir();
		$dir->make();
		return copy($file->getPath(), $this->getPath());
	}

	public function clone(string $path): ?FileInterface
	{
		$file = new File($path);
		if (!$file->copy($this)) {
			return null;
		}

		return $file;
	}

	public function open(string $flags = 'r'): bool
	{
		if ($flags == 'r' && !$this->exists()) {
			return false;
		}

		if ($this->opened == $flags) {
			return true;
		}

		if ($this->opened != '') {
			$this->close();
		}

		$this->instance = fopen($this->path, $flags);
		$this->opened = $flags;
		return true;
	}

	/**
	 * @param mixed $info
	 */
	public function write($info, string $flags='w'): bool
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
		if (!$opened) {
			$this->close();
		}

		return true;
	}

	public function close(): bool
	{
		if ($this->instance) {
			fclose($this->instance);
			$this->instance = null;
			$this->opened = '';
		}

		return true;
	}

	/**
	 * @param mixed $info
	 */
	public function put($info, int $flags = 0): bool
	{
		if (is_array($info) || is_object($info)) {
			$info = json_encode($info);
		} elseif (is_numeric($info)) {
		    $info = (string)$info;
        }

		if (!is_string($info)) {
			return false;
		}

		$this->checkCanSave();
		file_put_contents($this->path, $info, $flags);
		return true;
	}

	public function append($info): bool
	{
		return $this->put($info, FILE_APPEND);
	}

    /**
     * @return mixed
     */
	public function get()
	{
	    $result = file_get_contents($this->path);
	    if ($result === false) {
	        return null;
        }

	    return $result;
	}

	public function getTail(int $rowsCount): string
	{
		$file = file($this->getPath());
		$count = count($file);
		$result = '';
		for ($i = max(0, $count - $rowsCount); $i < $count; $i++) {
			$result .= $file[$i];
		}
		
		return $result;
	}

	public function match(string $pattern): bool
	{
		if ( ! $this->exists()) {
			return false;
		}
		
		if ($pattern[0] != '/') {
			$pattern = '/' . $pattern . '/';
		}
		
		$text = file_get_contents($this->path);
		return (bool)preg_match($pattern, $text);
	}

	public function replace(string $pattern, string $replacement): bool
	{
		if (!$this->exists()) {
			return false;
		}
		
		$text = file_get_contents($this->path);
		if ($pattern[0] != '/') {
			$pattern = '/' . $pattern . '/';
		}
		$text = preg_replace($pattern, $replacement, $text);
		file_put_contents($this->path, $text);
		return true;
	}

	/**
	 * Make directories according to file path
	 */
	private function checkCanSave(): void
	{
		if (!file_exists($this->parentDir)) {
			mkdir($this->parentDir, 0777, true);
		}
	}
}
