<?php

namespace lx;

/**
 * Class YamlDataFileAdapter
 * @package lx
 */
class YamlDataFileAdapter extends DataFileAdapter
{
	/**
	 * @return array
	 */
	public function parse()
	{
		$text = $this->file->get();
		return (new Yaml($text, $this->file->getParentDirPath()))->parse();
	}

	/**
	 * Yaml format is about text formatting.
	 * It doesn't matter what style has choised, the result will be formatted only one way
	 *
	 * @param array $data
	 * @param int $style
	 * @return string
	 */
	public function dataToString($data, $style)
	{
		switch ($style) {
			case DataFile::STYLE_LINE:
			case DataFile::STYLE_COMBINE:
			case DataFile::STYLE_PREATY: return $this->dataToPreatyString($data);
		}
	}

	/**
	 * @return string
	 */
	private static function getStep()
	{
		return '  ';
	}

	/**
	 * @param array $data
	 * @return string
	 */
	private function dataToPreatyString($data)
	{
		$arr = $this->prePack($data);
		$pack = function($arr, $step, $useFirstStep = true) use (&$pack) {
			if ( ! is_array($arr)) {
				return $arr;
			}

			$innerStep = $step;
			$result = [];
			$first = true;
			foreach ($arr as $key => $value) {
				if (is_array($value)) {
					if (ArrayHelper::isAssoc($value)) {
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

		$step = '';
		$result = $pack($arr, $step);
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
