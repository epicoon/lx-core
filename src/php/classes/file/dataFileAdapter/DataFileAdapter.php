<?php

namespace lx;

abstract class DataFileAdapter
{
	protected FileInterface $file;

	public function __construct(FileInterface $file)
	{
		$this->file = $file;
	}

	/**
	 * @param mixed $elem
	 */
	protected static function packPrimitiveType($elem): ?string
	{
		if (is_numeric($elem)) {
			return $elem;
		}

		if ($elem === true) {
			return 'true';
		}

		if ($elem === false) {
			return 'false';
		}

		if ($elem === null) {
			return 'null';
		}

		return null;
	}

	abstract public function parse(): array;
	abstract public function dataToString(array $data, int $style): string;
}
