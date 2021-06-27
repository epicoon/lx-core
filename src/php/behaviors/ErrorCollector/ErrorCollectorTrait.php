<?php

namespace lx;

/**
 * @see ErrorCollectorInterface
 */
trait ErrorCollectorTrait
{
	private ?ErrorCollectorList $errorCollectorList = null;

	public function hasErrors(): bool
	{
		if ($this->errorCollectorList === null) {
			return false;
		}

		return $this->errorCollectorList->hasErrors();
	}

	/**
	 * @param string|array|ErrorCollectorError|\Throwable $errorInfo
	 */
	public function addError($errorInfo): void
	{
		$this->getErrorCollectorList()->addError($errorInfo);
	}

	public function addErrors(array $errors): void
	{
        foreach ($errors as $error) {
            $this->addError($error);
        }
	}

    public function mergeErrors(ErrorCollectorInterface $collector): void
    {
        $this->addErrors($collector->getErrors());
    }

	/**
	 * @return array<ErrorCollectorError>
	 */
	public function getErrors(): array
	{
        if ($this->errorCollectorList === null) {
            return [];
        }

		return $this->getErrorCollectorList()->getErrors();
	}

    /**
     * @return ErrorCollectorError|null
     */
	public function getFirstError() //TODO since 8.0 : \Stringable
	{
        if ($this->errorCollectorList === null) {
            return null;
        }

		return $this->getErrorCollectorList()->getFirstError();
	}

	private function getErrorCollectorList(): ErrorCollectorList
	{
		if ($this->errorCollectorList === null) {
			$this->errorCollectorList = new ErrorCollectorList();
		}

		return $this->errorCollectorList;
	}
}
