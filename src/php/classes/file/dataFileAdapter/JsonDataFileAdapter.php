<?php

namespace lx;

class JsonDataFileAdapter extends DataFileAdapter
{
	public function parse(): array
	{
		$data = $this->file->get();
		return json_decode($data, true);
	}

	public function dataToString(array $data, int $style): string
	{
		switch ($style) {
			case DataFile::STYLE_LINE: return $this->dataToLineString($data);
			case DataFile::STYLE_PREATY:
			case DataFile::STYLE_COMBINE: return $this->dataToPreatyString($data);
		}
	}

	private function dataToLineString(array $data): string
	{
		return json_encode($data);
	}

	private function dataToPreatyString(array $data): string
	{
		return json_encode($data, JSON_PRETTY_PRINT);
	}
}
