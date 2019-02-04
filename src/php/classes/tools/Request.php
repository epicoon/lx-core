<?php

namespace lx;

Class Request {
	private $returnAsString;
	private $url = null;
	private $method = 'post';
	private $headers = [];
	private $options = [];
	private $params = [];
	private $info = null;
	private $error = null;

	/**
	 *
	 * */
	public function __construct($url = null, $params = []) {
		if ($url) {
			$tempUrl = explode('?', $url);
			if (count($tempUrl) > 1) {
				$urlParams = \lx::$dialog->translateGetParams($tempUrl[1]);
			} else {
				$urlParams = [];
			}
			if ($params) $this->params = array_merge($urlParams, $params);
			$this->url = $tempUrl[0];
		}

		$this->returnAsString = true;
	}

	/**
	 *
	 * */
	public function setMethod($method) {
		$this->method = strtolower($method);
	}

	/**
	 *
	 * */
	public function checkMethod($method) {
		return $this->method == strtolower($method);
	}

	/**
	 *
	 * */
	public function setHeaders($headers) {
		foreach ($headers as $key => $value) {
			if (is_numeric($key)) {
				$header = preg_split('/\s*:\s*/', $value);
				$this->headers[$header[0]] = $header[1];
			} else {
				$this->headers[$key] = $value;
			}
		}
	}

	/**
	 *
	 * */
	public function setHeader($header, $value) {
		$this->headers[$header] = $value;
	}

	/**
	 *
	 * */
	public function setOptions($options) {
		$this->options = $options;
	}

	/**
	 *
	 * */
	public function setParams($params) {
		$this->params = $params;
	}

	/**
	 *
	 * */
	public function setParam($name, $value) {
		$this->params[$name] = $value;
	}

	/**
	 *
	 * */
	public function getInfo($key = null) {
		if ($key === null) {
			return $this->info;
		}

		if (array_key_exists($key, $this->info)) {
			return $this->info['key'];
		}

		return null;
	}

	/**
	 *
	 * */
	public function getError() {
		return $this->error;
	}

	/**
	 *
	 * */
	public function send($params = []) {
		// Разбираемся с урлом
		$url = null;
		if ($this->url) $url = $this->url;
		if (!empty($params)) {
			$params = array_merge( $this->params, $params );
		} else {
			$params = $this->params;
		}
		if ($url) {
			if (!empty($params) && $this->checkMethod('get')) {
				$url .= '?' . http_build_query($params);
			}
		}

		// Пошла жара
		$ch = $url ? curl_init($url) : curl_init();

		// Заголовки
		if (!empty($this->headers)) {
			$headers = [];
			foreach ($this->headers as $key => $value) {
				$headers[] = "$key:$value";
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}

		// Прочие опции
		if ($this->returnAsString) {
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		}
		foreach ($this->options as $option => $value) {
			curl_setopt($ch, $option, $value);
		}
		if ($this->checkMethod('post')) {
			curl_setopt($ch, CURLOPT_POST, true);
			if (!empty($params)) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
			}
		}

		// Выполняем
		$result = curl_exec($ch);
		if ($result === false) {
			$this->error = curl_error($ch);
		}

		$this->info = curl_getinfo($ch);

		curl_close($ch);
		return $result;
	}

	/**
	 * //todo убрать отсюда
	 * */
	public static function parseJson($res) {
		if (!preg_match('/\{/', $res)) return $res;

		$res = preg_replace('/^[^{]*?\{/', '{', $res);
		$res = preg_replace('/\}[^}]$/', '}', $res);
		$result = json_decode($res, true);
		if ($result === null) $res;
		return $result;
	}
}
