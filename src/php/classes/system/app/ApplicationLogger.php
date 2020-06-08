<?php

namespace lx;

/**
 * Class ApplicationLogger
 * @package lx
 */
class ApplicationLogger implements LoggerInterface, FusionComponentInterface
{
    use ObjectTrait;
	use ApplicationToolTrait;
	use FusionComponentTrait;

	const DEFAULT_CATEGORY = 'common';
	const DEFAULT_LOG_PATH = '@site/log';

	/** @var string */
	protected $logPath;

	/** @var int */
	protected $messagesCountForFile = 10000;

	/**
	 * @param array|string $data
	 * @param string $category
	 */
	public function log($data, $category = null)
	{
		if ($category === null) {
			$category = self::DEFAULT_CATEGORY;
		}

		$logDir = $this->getLogDirectory();
		$dir = $logDir->getOrMakeDirectory($category);

		$date = new \DateTime();
		$date = $date->format('Y-m-d');

		$ff = $dir->getFiles($date . '*.log', Directory::FIND_NAME)->toArray();
		$lastFileIndex = -1;
		$lastFileName = '';
		foreach ($ff as $name) {
			preg_match('/_(\d+?)\./', $name, $match);
			$index = (int)$match[1];
			if ($index > $lastFileIndex) {
				$lastFileIndex = $index;
				$lastFileName = $name;
			}
		}

		$file = $lastFileName == ''
			? new File($date . '_0.log', $dir->getPath())
			: new File($lastFileName, $dir->getPath());

		if ($file->exists()) {
			$tail = $file->getTail(1);
			preg_match('/#(\d+?)\]/', $tail, $match);
			$messagesCount = (int)$match[1];

			if ($messagesCount == $this->messagesCountForFile) {
				$file = new File($date . '_' . ($lastFileIndex + 1) . '.log', $dir->getPath());
				$messagesCount = 0;
			}
		} else {
			$messagesCount = 0;
		}

		$msgIndex = $messagesCount + 1;
		$msg = $this->prepareData($data, $msgIndex);

		$file->put($msg, FILE_APPEND);
	}

	/**
	 * @return Directory
	 */
	protected function getLogDirectory()
	{
		$dir = new Directory($this->getLogPath());
		$dir->make();
		return $dir;
	}

	/**
	 * @return string
	 */
	protected function getLogPath()
	{
		$path = $this->logPath ?? self::DEFAULT_LOG_PATH;
		return $this->app->conductor->getFullPath($path);
	}

	/**
	 * @param array|string $data
	 * @param int $index
	 * @return string
	 */
	protected function prepareData($data, $index)
	{
		$result = '[[MSG_BEGIN #' . $index . ']]' . PHP_EOL;

		if (!is_string($data)) {
			$data = json_encode($data);
		}
		$result .= $data . PHP_EOL;

		$result .= '[[MSG_END #' . $index . ']]' . PHP_EOL;
		return $result;
	}
}
