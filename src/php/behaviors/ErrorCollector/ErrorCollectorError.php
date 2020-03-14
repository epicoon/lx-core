<?php

namespace lx;

/**
 * Class ErrorCollectorError
 * @package lx
 */
class ErrorCollectorError implements ToStringConvertableInterface
{
	/** @var string */
	private $title;

	/** @var string */
	private $description;

	/** @var array */
	private $data;

	/** @var \Exception */
	private $exception;

	/**
	 * ErrorCollectorError constructor.
	 * @param string|array $config
	 */
	public function __construct($config)
	{
		if (is_string($config)) {
			$config = ['description' => $config];
		}
		
		if ( ! is_array($config)) {
			return;
		}

		$this->title = $config['title'] ?? '';
		$this->description = $config['description'] ?? '';
		$this->data = $config['data'] ?? [];
		$this->exception = $config['exception'] ?? null;
	}

	/**
	 * @return array
	 */
	public function getInfo()
	{
		$result = [];
		if ($this->title != '') {
			$result['title'] = $this->title;
		}
		
		if ($this->description != '') {
			$result['description'] = $this->description;
		}
		
		if ( ! empty($this->data)) {
			$result['data'] = $this->data;
		}
		
		if ($this->exception) {
			$result['exception'] = $this->exception;
		}
		
		return $result;
	}

	/**
	 * @param callable $callback
	 * @return string
	 */
	public function toString($callback = null)
	{
		if ($callback === null || !is_callable($callback)) {
			return $this->__toString();
		}
		
		return $callback(
			$this->title,
			$this->description,
			$this->data,
			$this->exception
		);
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		$result = [];
		if ($this->title != '') {
			$result[] = 'Title: ' . $this->title;
		}
		
		if ($this->description != '') {
			$result[] = 'Desription: ' . $this->description;
		}
		
		if ( ! empty($this->data)) {
			$data = [];
			foreach ($this->data as $key => $value) {
				$string = $this->dataValueToString($value);
				if ($string) {
					$data[$key] = $value;
				}
			}
			$result[] = implode('; ', $data);
		}
		
		$result = implode('.' . PHP_EOL, $result) . '.' . PHP_EOL;

		if ($this->exception) {
			$exceptionString = 'Exception: '
				. '[' . $this->exception->getCode() . '] '
				. $this->exception->getMessage() . PHP_EOL
				. '-- file: ' . $this->exception->getFile() . '(' . $this->exception->getLine() . ')' . PHP_EOL
				. '-- trace:' . PHP_EOL
				. $this->exception->getTraceAsString();
			$result .= $exceptionString . PHP_EOL;
		}

		return  $result;
	}

	/**
	 * @param mixed $value
	 * @return string
	 */
	private function dataValueToString($value)
	{
		if (is_string($value)) {
			return $value;
		}

		//TODO array, object...
		return '';
	}
}
