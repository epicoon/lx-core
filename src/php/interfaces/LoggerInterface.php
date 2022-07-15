<?php

namespace lx;

interface LoggerInterface
{
    public function setFilePath(string $path): void;
	/**
	 * @param mixed $data
	 */
	public function log($data, ?string $category = null): void;
    public function error(\Throwable $exception, array $additionalData = []): void;
}
