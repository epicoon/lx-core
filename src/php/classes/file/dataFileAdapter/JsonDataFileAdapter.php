<?php

namespace lx;

/**
 * Class JsonDataFileAdapter
 * @package lx
 */
class JsonDataFileAdapter extends DataFileAdapter
{
	/**
	 * @return array
	 */
	public function parse()
	{
		$data = $this->file->get();
		return json_decode($data, true);
	}

	/**
	 * @param array $data
	 * @param int $style
	 * @return string
	 */
	public function dataToString($data, $style)
	{
		switch ($style) {
			case DataFile::STYLE_LINE: return $this->dataToLineString($data);
			case DataFile::STYLE_PREATY:
			case DataFile::STYLE_COMBINE: return $this->dataToPreatyString($data);
		}
	}

	/**
	 * @param array $data
	 * @return string
	 */
	private function dataToLineString($data)
	{
		return json_encode($data);
	}

	/**
	 * @param array $data
	 * @return string
	 */
	private function dataToPreatyString($data)
	{
		return json_encode($data, JSON_PRETTY_PRINT);
	}
}
