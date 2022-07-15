<?php

namespace lx;

use lx;

class Dialog implements FusionComponentInterface
{
	use FusionComponentTrait;

	const REQUEST_TYPE_COMMON = 'common';
	const REQUEST_TYPE_PAGE_LOAD = 'page_load';
	const REQUEST_ASSET = 'asset';
	const REQUEST_TYPE_AJAX = 'ajax';
	const REQUEST_TYPE_CORS = 'cors';

	private string $_serverName;
    private string $_serverAddr;
	private string $_type;
	private ?string $_clientIp = null;
	private ?string $_clientIpFromProxy = null;
	private ?string $_url = null;
	private ?string $_route = null;
	private ?array $_headers = null;
	private ?string $_method = null;
	private ?array $_params = null;
	private ?Cookie $_cookie = null;
	private ?DataObject $_location = null;

	protected function init(): void
	{
		$this->_serverName = lx::$app->getConfig('serverName') ?? $_SERVER['SERVER_NAME'];
		$this->_serverAddr = lx::$app->getConfig('serverAddr') ?? $_SERVER['SERVER_ADDR'];
		$this->defineType();
		$this->defineClientIp();
	}

	public function getServerName(): string
    {
        return $this->_serverName;
    }

	public function getUrl(): string
	{
		if ($this->_url === null) $this->retrieveUrl();
		return $this->_url;
	}

	public function getRoute(): string
	{
		if ($this->_route === null) $this->retrieveRoute();
		return $this->_route;
	}

	public function getHeaders(): array
	{
		if ($this->_headers === null) $this->retrieveHeaders();
		return $this->_headers;
	}

	public function getHeader(string $name): ?string
	{
		$name = strtoupper($name);
		$name = str_replace('-', '_', $name);
		$headers = $this->getHeaders();
		if (array_key_exists($name, $headers)) return $headers[$name];
		return null;
	}

	public function getMethod(): string
	{
		if ($this->_method === null) $this->retrieveMethod();
		return $this->_method;
	}

	public function isAjax(): bool
	{
		return $this->_type == self::REQUEST_TYPE_AJAX;
	}

	public function isCors(): bool
	{
		return $this->_type == self::REQUEST_TYPE_CORS;
	}

	public function isPageLoad(): bool
	{
		return $this->_type == self::REQUEST_TYPE_PAGE_LOAD;
	}

	public function isAssetLoad(): bool
	{
		return $this->_type == self::REQUEST_ASSET;
	}

	/**
	 * @return Cookie|mixed
	 */
	public function getCookie(?string $data = null)
	{
		if ($this->_cookie === null) $this->retrieveCookie();

		if ($data === null) {
			return $this->_cookie;
		}

		if (is_string($data)) {
			return $this->_cookie->$data;
		}

		return null;
	}

	/**
	 * @return mixed
	 */
	public function getParam(string $name)
	{
		if ($this->_params === null) {
			$this->retrieveParams();
		}

		if (array_key_exists($name, $this->_params)) {
			return $this->_params[$name];
		}

		return null;
	}

	public function getParams(): array
	{
		if ($this->_params === null) {
			$this->retrieveParams();
		}

		return $this->_params;
	}

	/**
     * TODO - сделать нормальный класс для Location
     *
	 * @return DataObject with parameters: [
	 *     'href' => "http://main:80/subplugin?lang=en",
	 *     'origin' => "http://main:80",
	 *     'protocol' => "http:",
	 *     'host' => "main:80",
	 *     'hostname' => "main",
	 *     'port' => "80",
	 *     'pathname' => "/subplugin",
	 *     'search' => "?lang=en",
	 *     'searchArray' => ['lang' => 'en']
	 * ]
	 */
	public function getLocation(): DataObject
	{
		if ($this->_location === null) {
			$this->_location = $this->urlToLocation(
				$_SERVER['REQUEST_SCHEME'] . '://'
				. $_SERVER['SERVER_NAME']
				. ':' . $_SERVER['SERVER_PORT']
				. $_SERVER['REQUEST_URI']
			);
		}

		return $this->_location;
	}

	public function urlToLocation(string $url): DataObject
	{
		$result = ['href' => $url];

		$parts = explode('?', $url);
		if (count($parts) > 1) {
			$result['search'] = '?' . $parts[1];
			$result['searchArray'] = $this->translateGetParams($parts[1]);
		} else {
			$result['search'] = '';
			$result['searchArray'] = [];
		}

		$str = $parts[0];
		preg_match('/^([^:]+?:)\/\/([^\/]+?)(\/.+)$/', $str, $matches);

		$result['protocol'] = $matches[1];
		$result['host'] = $matches[2];
		$result['pathname'] = $matches[3] ?? '';
		$result['origin'] = $result['protocol'] . '//' . $result['host'];

		$parts = explode(':', $result['host']);
		$result['hostname'] = $parts[0];
		$result['port'] = $parts[1] ?? '';

		return DataObject::create($result);
	}

	public function translateGetParams(string $text): array
	{
		$temp = urldecode($text);
        $pairs = explode('&', $temp);
        $params = [];
        foreach ($pairs as $pare) {
            list($name, $value) = explode('=', $pare, 2);

            if (isset($params[$name])) {
                if (is_array($params[$name])) {
                    $params[$name][] = $value;
                } else {
                    $params[$name] = [$params[$name], $value];
                }
            } else {
                $params[$name] = $value;
            }
        }

        return $params;
	}

	public function send(ResponseInterface $response): void
	{
	    $response->beforeSend();

        if ($this->isCors()) {
            $this->addCorsHeaders();
        }

        if (!lx::$app->user || lx::$app->user->isGuest()) {
            header('lx-user-status: guest');
        }

        $this->echo($response->getDataAsString());
	}

	public function retrieveAll(): void
	{
		$this->getUrl();
		$this->getRoute();
		$this->getHeaders();
		$this->getMethod();
		$this->getParams();
		$this->getLocation();
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function echo(string $data): void
	{
		ignore_user_abort(true);
		set_time_limit(0);
		ob_start(/*'ob_gzhandler'*/);
		echo $data;
		header('Connection: close');
		header('Content-Length: ' . ob_get_length());
		ob_end_flush();
		ob_flush();
		flush();
		if (session_id()) session_write_close();
		fastcgi_finish_request();
	}

	private function addCorsHeaders(): void
	{
		$corsProcessor = lx::$app->corsProcessor;
		if (!$corsProcessor) {
			return;
		}

		$requestHeaders = [
			'origin' => $this->getHeader('ORIGIN'),
			'method' => $this->getHeader('Access-Control-Request-Method'),
			'headers' => $this->getHeader('Access-Control-Request-Headers'),
		];

		$headers = $corsProcessor->getHeaders($requestHeaders);
		foreach ($headers as $header) {
			header($header);
		}
	}

	private function defineClientIp(): void
	{
		$this->_clientIp = $_SERVER['REMOTE_ADDR'];

		$fromProxy = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
        if ($fromProxy) {
            $fromProxy = trim(end(explode(',', $fromProxy)));
        }
		if (filter_var($fromProxy, FILTER_VALIDATE_IP)) {
			$this->_clientIpFromProxy = $fromProxy;
		}
	}

	private function defineType(): void
	{
		$userAgent = $this->getHeader('USER_AGENT');
		if (!$userAgent) {
			$this->_type = self::REQUEST_TYPE_COMMON;
			return;
		}

		$origin = $this->getHeader('ORIGIN');
		if (!$origin) {
			if (preg_match('/\.(js|css)$/', $this->getUrl())) {
				$this->_type = self::REQUEST_ASSET;
			} else {
				$this->_type = self::REQUEST_TYPE_PAGE_LOAD;
			}
			return;
		}

		preg_match('/[^:]+?:\/\/(.+)/', $origin, $matches);
		$originName = $matches[1] ?? null;

		if ($originName == $this->_serverName || $originName == $this->_serverAddr) {
			$this->_type = self::REQUEST_TYPE_AJAX;
		} else {
			$this->_type = self::REQUEST_TYPE_CORS;
		}
	}

	private function retrieveUrl(): void
	{
		$requestUri = '';
		if (isset($_SERVER['REQUEST_URI'])) {
			$requestUri = $_SERVER['REQUEST_URI'];
			if ($requestUri !== '' && $requestUri[0] !== '/') {
				$requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $requestUri);
			}
		} elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // IIS
			$requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
		} elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0 CGI
			$requestUri = $_SERVER['ORIG_PATH_INFO'];
			if (!empty($_SERVER['QUERY_STRING'])) {
				$requestUri .= '?' . $_SERVER['QUERY_STRING'];
			}
		}
		$this->_url = $requestUri;
	}

	private function retrieveRoute(): void
	{
		$url = $this->getUrl();
		$pos = strpos($url, '?');
		if ($pos !== false) {
			$url = substr($url, 0, $pos);
		}
		$url = urldecode($url);
		if (strlen($url) > 1 && $url[0] == '/') {
			$url = substr($url, 1);
		}
		$this->_route = $url;
	}

	private function retrieveHeaders(): void
	{
		$this->_headers = [];
		$headers = getallheaders();
		foreach ($headers as $key => $value) {
			$key = strtoupper($key);
			$key = str_replace('-', '_', $key);
			$this->_headers[$key] = $value;
		}
	}

	private function retrieveMethod(): void
	{
		$this->_method = strtolower($_SERVER['REQUEST_METHOD']);
	}

	private function retrieveParams(): void
	{
		$get = [];
		$url = $this->getUrl();
		$temp = explode('?', $url);
		if (count($temp) > 1) {
			$get = $this->translateGetParams($temp[1]);
		}

		$method = $this->getMethod();
		$post = [];
		if ($method == 'post') {
			$post = $_POST ?? [];
			if (empty($post)) {
				$input = file_get_contents('php://input');
				$post = json_decode($input, true);
                if (!is_array($post)) {
                    $post = [];
                }
			}
		}

        $this->_params = $post + $get;
	}

	private function retrieveCookie(): void
	{
		$this->_cookie = new Cookie();
	}
}
