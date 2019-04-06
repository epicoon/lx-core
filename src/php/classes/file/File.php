<?php

namespace lx;

class File extends BaseFile {
	protected
		$instance = null,
		$opened = '';

	public function __construct($name, $path=null) {
		if ($path === null) {
			parent::__construct($name);
			return;
		}

		if ($path instanceof Directory) $path = $path->getPath();
		if (!preg_match('/\/$/', $path)) $path .= '/';
		$this->path = $path . $name;

		$this->parentDir = dirname($path);

		preg_match_all('/\/([^\/]*)$/', $this->path, $matches);
		$this->name = $matches[1][0];
	}

	/**
	 *
	 * */
	public function getExtension() {
		$arr = explode('.', $this->name);
		if (count($arr) < 2) return '';
		return array_pop($arr);
	}

	/**
	 *
	 * */
	public function remove() {
		unlink($this->getPath());
	}

	/**
	 *
	 * */
	public function open($flags = 'r') {
		if ($flags == 'r' && !$this->exists()) return false;
		if ($this->opened == $flags) return $this;

		if ($this->opened != '') $this->close();
		$this->instance = fopen($this->path, $flags);
		$this->opened = $flags;

		return $this;
	}

	/*
	 * Можно предварительно открыть файл, писать в него несколько раз вызывая данный метод, после чего закрыть
	 * */
	public function write($info, $flags='w') {
		if (is_array($info)) $info = json_encode($info);
		if (is_object($info)) $info = json_encode($info);
		if (!is_string($info)) return false;

		$this->checkCanSave();
		// нужно ли оставлять его открытым
		$opened = ($this->opened != '');
		// нужно ли переоткрыть для записи
		if (!$this->opened != $flags) $this->open($flags);
		fwrite($this->instance, $info);
		if (!$opened) $this->close();
		return $this;
	}

	public function close() {
		if (!$this->exists() || !$this->instance) return false;
		if ($this->opened == '') return;
		fclose($this->instance);
		$this->instance = null;
		$this->opened = '';
		return $this;
	}

	/*
	 * Не нужно открывать/закрывать - делается само, но это делается при каждом вызове
	 * */
	public function put($info, $flags=0) {
		if (is_array($info)) $info = json_encode($info);
		if (is_object($info)) $info = json_encode($info);
		if (!is_string($info)) return false;

		$this->checkCanSave();
		file_put_contents($this->path, $info, $flags);
		return $this;
	}

	/*
	 * Не нужно открывать/закрывать - делается само, но это делается при каждом вызове
	 * */
	public function get() {
		return file_get_contents($this->path);
	}

	public function match($pattern) {
		if (!$this->exists()) return false;
		if ($pattern[0] != '/') $pattern = '/' . $pattern . '/';
		$text = file_get_contents($this->path);
		return preg_match($pattern, $text);
	}

	public function replace($pattern, $replacement) {
		if (!$this->exists()) return false;
		$text = file_get_contents($this->path);

		if ($pattern{0} != '/') $pattern = '/' . $pattern . '/';
		$text = preg_replace($pattern, $replacement, $text);

		file_put_contents($this->path, $text);
		return true;
	}

	public function load() {
		return require($this->path);
	}

	public function requireOnce() {
		require_once($this->path);
	}

	private function checkCanSave() {
		if (!file_exists($this->parentDir)) {
			mkdir($this->parentDir, 0777, true);
		}
	}
}
