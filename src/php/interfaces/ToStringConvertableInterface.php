<?php

namespace lx;

interface ToStringConvertableInterface
{
	public function toString(callable $callback = null): string;
	public function __toString(): string;
}
