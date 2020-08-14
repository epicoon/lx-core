<?php

namespace lx;

/**
 * Class Request
 * @package lx
 */
Class Request
{
	/** @var bool */
	private $returnAsString;

	/** @var string */
	private $url = null;

	/** @var string */
	private $method = 'post';

	/** @var array */
	private $headers = [];

	/** @var array */
	private $options = [];

	/** @var array */
	private $params = [];

	/** @var string */
	private $response = null;

	/** @var array */
	private $info = null;

	/** @var string */
	private $error = null;

	/**
	 * Request constructor.
	 * @param string $url
	 * @param array $params
	 */
	public function __construct($url = null, $params = [])
	{
		if ($url) {
			$tempUrl = explode('?', $url);
			if (count($tempUrl) > 1) {
				$url = $tempUrl[0];
				$params = array_merge($params, \lx::$app->dialog->translateGetParams($tempUrl[1]));
			}

			$this->url = $url;
			$this->params = $params;
		}

		$this->returnAsString = true;
	}

	/**
	 * @param string $method
	 */
	public function setMethod($method)
	{
		$this->method = strtolower($method);
	}

	/**
	 * @param string $method
	 * @return bool
	 */
	public function checkMethod($method)
	{
		return $this->method == strtolower($method);
	}

	/**
	 * @param array $headers
	 */
	public function addHeaders($headers)
	{
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
	 * @param string $header
	 * @param string $value
	 */
	public function addHeader($header, $value)
	{
		$this->headers[$header] = $value;
	}

	/**
	 * @param array $options
	 */
	public function setOptions($options)
	{
		$this->options = $options;
	}

	/**
	 * @param int $key
	 * @param mixed $value
	 */
	public function setOption($key, $value)
	{
		$this->options[$key] = $value;
	}

	/**
	 * @param array $options
	 * @param bool $rewrite
	 */
	public function addOptions($options, $rewrite = false)
	{
		foreach ($options as $key => $value) {
			if (!$rewrite && array_key_exists($key, $this->options)) {
				continue;
			}

			$this->options[$key] = $value;
		}
	}

	/**
	 * @param array $params
	 */
	public function setParams($params)
	{
		$this->params = $params;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setParam($name, $value)
	{
		$this->params[$name] = $value;
	}

	/**
	 * @param array $params
	 * @param bool $rewrite
	 */
	public function addParams($params, $rewrite = false)
	{
		foreach ($params as $key => $param) {
			if (!$rewrite && array_key_exists($key, $this->params)) {
				continue;
			}

			$this->params[$key] = $param;
		}
	}

	/**
	 * @return string
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * @param string $key
	 * @return array|string|null
	 */
	public function getInfo($key = null)
	{
		if ($key === null) {
			return $this->info;
		}

		if (array_key_exists($key, $this->info)) {
			return $this->info['key'];
		}

		return null;
	}

	/**
	 * @return string
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * @param array $params
	 * @return string|false
	 */
	public function send($params = [])
	{
		$url = null;
		if ($this->url) $url = $this->url;
		if (!empty($params)) {
			$params = array_merge($this->params, $params);
		} else {
			$params = $this->params;
		}
		if ($url) {
			if (!empty($params) && $this->checkMethod('get')) {
				$url .= '?' . http_build_query($params);
			}
		}

		$ch = $url ? curl_init($url) : curl_init();

		if (!empty($this->headers)) {
			$headers = [];
			foreach ($this->headers as $key => $value) {
				$headers[] = "$key:$value";
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}

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

		$this->response = curl_exec($ch);
		if ($this->response === false) {
			$this->error = curl_error($ch);
		}

		$this->info = curl_getinfo($ch);

		curl_close($ch);
		return $this->response;
	}
}
