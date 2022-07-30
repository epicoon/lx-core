<?php

namespace lx;

Class CurlRequest
{
	private bool $returnAsString;
	private ?string $url = null;
	private string $method = 'post';
	private array $headers = [];
	private array $options = [];
	private array $params = [];
	private ?string $response = null;
	private ?array $info = null;
	private ?string $error = null;

	public function __construct(?string $url = null, array $params = [])
	{
		if ($url) {
			$tempUrl = explode('?', $url);
			if (count($tempUrl) > 1) {
				$url = $tempUrl[0];
				$params = array_merge($params, \lx::$app->request->translateGetParams($tempUrl[1]));
			}

			$this->url = $url;
			$this->params = $params;
		}

		$this->returnAsString = true;
	}

	public function setMethod(string $method): void
	{
		$this->method = strtolower($method);
	}

	public function checkMethod(string $method): bool
	{
		return $this->method == strtolower($method);
	}

	public function addHeaders(array $headers): void
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

	public function addHeader(string $header, string $value): void
	{
		$this->headers[$header] = $value;
	}

	public function setOptions(array $options): void
	{
		$this->options = $options;
	}

	/**
	 * @param mixed $value
	 */
	public function setOption(int $key, $value): void
	{
		$this->options[$key] = $value;
	}

	public function addOptions(array $options, bool $rewrite = false): void
	{
		foreach ($options as $key => $value) {
			if (!$rewrite && array_key_exists($key, $this->options)) {
				continue;
			}

			$this->options[$key] = $value;
		}
	}

	public function setParams(array $params): void
	{
		$this->params = $params;
	}

	/**
	 * @param mixed $value
	 */
	public function setParam(string $name, $value): void
	{
		$this->params[$name] = $value;
	}

	public function addParams(array $params, bool $rewrite = false): void
	{
		foreach ($params as $key => $param) {
			if (!$rewrite && array_key_exists($key, $this->params)) {
				continue;
			}

			$this->params[$key] = $param;
		}
	}

	public function getResponse(): string
	{
		return $this->response;
	}

	/**
	 * @return mixed
	 */
	public function getInfo(?string $key = null)
	{
		if ($key === null) {
			return $this->info;
		}

		if (array_key_exists($key, $this->info)) {
			return $this->info['key'];
		}

		return null;
	}

	public function getError(): ?string
	{
		return $this->error;
	}

	public function send(array $params = []): ?string
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

		$response = curl_exec($ch);
		if ($response === false) {
			$this->error = curl_error($ch);
		} else {
		    $this->response = $response;
        }

		$this->info = curl_getinfo($ch);

		curl_close($ch);
		return $this->response;
	}
}
