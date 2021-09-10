<?php

namespace lx;

class YamlDataFileAdapter extends DataFileAdapter
{
	public function parse(): array
	{
		$text = $this->file->get();
		return (new Yaml($text, $this->file->getParentDirPath()))->parse();
	}

	public function dataToString(array $data, int $style): string
	{
		switch ($style) {
			case DataFile::STYLE_LINE:
			case DataFile::STYLE_COMBINE:
			case DataFile::STYLE_PREATY: return $this->dataToPreatyString($data);
		}
	}

	private static function getStep(): string
	{
		return '  ';
	}

	private function dataToPreatyString(array $data): string
	{
		$arr = $this->prePack($data);
		$pack = function($arr, $step, $useFirstStep = true) use (&$pack) {
			if (!is_array($arr)) {
				return $arr;
			}

			$innerStep = $step;
			$result = [];
			$first = true;
			foreach ($arr as $key => $value) {
				if (is_array($value)) {
				    if (empty($value)) {
				        $result[] = $innerStep . $key . ': []';
                    } elseif (ArrayHelper::isAssoc($value)) {
						$result[] = $innerStep . $key . ':' . PHP_EOL . $pack($value, $step . self::getStep());
					} else {
						$str = [$innerStep . $key . ':'];
						$arrStep = $innerStep . self::getStep();
						foreach ($value as $item) {
							$str[] = $arrStep . '- ' . $pack($item, $arrStep . self::getStep(), false);
						}
						$result[] = implode(PHP_EOL, $str);
					}
				} else {
					$currStep = ($useFirstStep || !$first) ? $innerStep : '';
					$result[] = $currStep . "$key: $value";
				}
				$first = false;
			}

			$result = implode(PHP_EOL, $result);
			return $result;
		};

		$result = $pack($arr, '');
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
			return $elem;
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
