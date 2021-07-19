<?php

namespace lx;

interface LoggerInterface
{
	/**
	 * @param mixed $data
	 */
	public function log($data, ?string $category = null): void;
	public function init(array $config): void;
}
