<?php

namespace lx;

interface ToStringConvertableInterface //TODO since 8.0 extends \Stringable
{
	public function toString(callable $callback = null): string;
	public function __toString(): string;
}
