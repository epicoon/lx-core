<?php

namespace lx;

class ErrorCollectorList
{
    /** @var array<ErrorCollectorError> */
	private array $list = [];

	public function hasErrors(): bool
	{
		return !empty($this->list);
	}

	/**
	 * @param string|array|ErrorCollectorError|\Throwable $errorInfo
	 */
	public function addError($errorInfo): void
	{
	    if ($errorInfo instanceof ErrorCollectorError) {
            $this->list[] = $errorInfo;
        } else {
            $this->list[] = new ErrorCollectorError($errorInfo);
        }
	}

	/**
	 * @return array<ErrorCollectorError>
	 */
	public function getErrors(): array
	{
		return $this->list;
	}

	public function getFirstError(): ?ErrorCollectorError
	{
		return $this->list[0] ?? null;
	}
}
