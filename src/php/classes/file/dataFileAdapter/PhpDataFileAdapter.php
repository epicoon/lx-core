<?php

namespace lx;

class PhpDataFileAdapter extends DataFileAdapter
{
	public function parse(): array
	{
		return require($this->file->getPath());
	}

	public function dataToString(array $data, int $style): string
	{
		switch ($style) {
			case DataFile::STYLE_LINE: return $this->dataToLineString($data);
			case DataFile::STYLE_PREATY: return $this->dataToPreatyString($data);
			case DataFile::STYLE_COMBINE: return $this->dataToCombineString($data);
		}
	}

	private static function getStep(): string
	{
		return '    ';
	}

	private function dataToLineString(array $data): string
	{
		$arr = $this->prePack($data);
		$pack = function($arr) use (&$pack) {
			if ( ! is_array($arr)) {
				return $arr;
			}

			$result = [];
			foreach ($arr as $key => $value) {
				if (is_array($value)) {
					$result[] = "'$key' => {$pack($value)}";
				} else {
					$result[] = "'$key' => $value";
				}
			}

			$result = '[' . implode(', ', $result) . ']';
			return $result;
		};

		$result = '<?php return ' . $pack($arr) . ';';
		return $result;
	}

	private function dataToPreatyString(array $data): string
	{
		$arr = $this->prePack($data);
		$pack = function($arr, $step) use (&$pack) {
			if ( ! is_array($arr)) {
				return $arr;
			}

			$innerStep = $step . self::getStep();

			$result = [];
			$isAssoc = ArrayHelper::isAssoc($arr);
			foreach ($arr as $key => $value) {
				if (is_array($value)) {
					if ($isAssoc) {
						$result[] = "$innerStep'$key' => {$pack($value, $innerStep)}";
					} else {
						$result[] = "$innerStep{$pack($value, $innerStep)}";
					}
				} else {
					if ($isAssoc) {
						$result[] = "$innerStep'$key' => $value";
					} else {
						$result[] = "$innerStep$value";
					}
				}
			}

			$result = '[' . PHP_EOL
				. implode(',' . PHP_EOL, $result) . PHP_EOL
				. $step . ']';
			return $result;
		};

		$step = '';
		$result = '<?php' . PHP_EOL . PHP_EOL . 'return ' . $pack($arr, $step) . ';' . PHP_EOL;
		return $result;
	}

	private function dataToCombineString(array $data): string
	{
		$arr = $this->prePack($data);
		$pack = function($arr, $step) use (&$pack) {
			if ( ! is_array($arr)) {
				return $arr;
			}

			$innerStep = $step . self::getStep();
			$result = [];
			$isAssoc = ArrayHelper::isAssoc($arr);
			$arrs = 0;
			foreach ($arr as $key => $value) {
				if (is_array($value)) {
					if ($isAssoc) {
						$result[] = "$innerStep'$key' => {$pack($value, $innerStep)}";
					} else {
						$result[] = "$innerStep{$pack($value, $innerStep)}";
					}
					$arrs++;
				} else {
					if ($isAssoc) {
						$result[] = "$innerStep'$key' => $value";
					} else {
						$result[] = "$innerStep$value";
					}
				}
			}

			if (!$arrs && count($result) <= 3) {
				$temp = [];
				foreach ($result as $item) {
					$temp[] = trim($item);
				}
				$str = '[' . implode(', ', $temp) . ']';
				if (mb_strlen($step . $str) <= 121) {
					return $str;
				}
			}

			$result = '[' . PHP_EOL
				. implode(',' . PHP_EOL, $result) . PHP_EOL
				. $step . ']';
			return $result;
		};

		$step = '';
		$result = '<?php' . PHP_EOL . PHP_EOL . 'return ' . $pack($arr, $step) . ';' . PHP_EOL;
		return $result;
	}

	/**
	 * @param mixed $elem
	 * @return array|string
	 */
	private function prePack($elem)
	{
		$str = self::packPrimitiveType($elem);
		if ($str !== null) {
			return $str;
		}

		if (is_string($elem)) {
			$elem = addcslashes($elem, "'");
			return "'$elem'";
		}

		if (is_array($elem)) {
			$arr = [];
			foreach ($elem as $key => $value) {
				$arr[$key] = $this->prePack($value);
			}

			return $arr;
		}

		return "'[[undefined type]]'";
	}
}
