<?php

namespace lx;

interface DataFileInterface extends FileInterface
{
    public function getText(): string;

	/**
	 * @param mixed $value
	 * @param array|string|null $group
	 */
	public function insertParam(string $name, $value, $group = null, ?int $style = null): bool;
}
