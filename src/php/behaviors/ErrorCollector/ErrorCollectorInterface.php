<?php

namespace lx;

/**
 * @see ErrorCollectorTrait
 */
interface ErrorCollectorInterface
{
	public function hasErrors(): bool;
	/**
	 * @param mixed $errorInfo
	 */
	public function addError($errorInfo): void;
	public function addErrors(array $errors): void;
    public function mergeErrors(ErrorCollectorInterface $collector): void;
	public function getErrors(): array;
	/**
	 * @return mixed
	 */
	public function getFirstError(); //TODO since 8.0 : \Stringable
}
