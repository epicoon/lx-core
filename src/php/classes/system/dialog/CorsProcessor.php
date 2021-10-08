<?php

namespace lx;

class CorsProcessor implements FusionComponentInterface
{
	use FusionComponentTrait;

	protected array $originMap = [];

	public function __construct(array $config = [])
	{
	    $this->__objectConstruct($config);

		$originMap = [];
		foreach ($this->originMap as $key => $value) {
			if (is_string($key)) {
				$originMap[$key] = $value;
			} else {
				$originMap[$value] = [];
			}
		}
		$this->originMap = $originMap;
	}

	public function getHeaders(array $requestHeaders): array
	{
		if (!isset($requestHeaders['origin'])) {
			return [];
		}

		$origin = $requestHeaders['origin'];
		if (!array_key_exists($origin, $this->originMap)) {
			return [];
		}

		$result = ["Access-Control-Allow-Origin: $origin"];
		$originData = $this->originMap[$origin];
		$firstStep = ($requestHeaders['method'] !== null || $requestHeaders['headers'] !== null);
		if ($firstStep) {
			if ($requestHeaders['method'] !== null && array_key_exists('allow-methods', $originData)) {
				$result[] = "Access-Control-Allow-Methods: {$originData['allow-methods']}";
			}

			if ($requestHeaders['headers'] !== null && array_key_exists('allow-headers', $originData)) {
				$result[] = "Access-Control-Allow-Headers: {$originData['allow-headers']}";
			}

			if (array_key_exists('max-age', $originData)) {
				$result[] = "Access-Control-Max-Age: {$originData['max-age']}";
			}
		} else {
			if (array_key_exists('expose-headers', $originData)) {
				$result[] = "Access-Control-Expose-Headers: {$originData['expose-headers']}";
			}
		}

		return $result;
	}
}
