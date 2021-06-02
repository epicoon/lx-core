<?php

namespace lx;

use Exception;

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
	protected $path;

	/** @var int */
	protected $messagesCountForFile = 10000;

	/** @var array|null */
	protected $allowedCategories = null;

	/** @var array */
	protected $ignoredCategories = [];

    public function init(array $config): void
    {
        $this->path = $config['path'] ?? null;
    }

	/**
	 * @param array|string $data
	 */
	public function log($data, ?string $category = null): void
	{
	    $trace = debug_backtrace();
        $source = $trace[1];

		if ($category === null) {
			$category = self::DEFAULT_CATEGORY;
		}

		if (in_array($category, $this->ignoredCategories)) {
		    return;
        }

		if (is_array($this->allowedCategories) && !in_array($category, $this->allowedCategories)) {
		    return;
        }

		$logDir = $this->getLogDirectory();
		$dir = $logDir->getOrMakeDirectory($category);

		$date = new \DateTime();
		$date = $date->format('Y-m-d');

		$ff = $dir->getFileNames($date . '*.log')->toArray();
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
			preg_match('/#(\d+?) ---\]/', $tail, $match);
			$messagesCount = array_key_exists(1, $match) ? (int)$match[1] : 0;

			if ($messagesCount == $this->messagesCountForFile) {
				$file = new File($date . '_' . ($lastFileIndex + 1) . '.log', $dir->getPath());
				$messagesCount = 0;
			}
		} else {
			$messagesCount = 0;
		}

		$msgIndex = $messagesCount + 1;
		$msg = $this->prepareData($data, $source, $msgIndex);

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
		$path = $this->path ?? self::DEFAULT_LOG_PATH;
		return $this->app->conductor->getFullPath($path);
	}

	/**
	 * @param array|string $data
     * @param array $source
	 * @param int $index
	 * @return string
	 */
	protected function prepareData($data, $source, $index)
	{
        $date = new \DateTime();
        $date = $date->format('Y-m-d h:i:s');

        $result = ($index > 1) ? PHP_EOL : '';
		$result .= '[[--- MSG_BEGIN #' . $index
            . ' (time: ' . $date
            . ', source: ' . $source['file'] . '::' . $source['line']
            . ') ---]]' . PHP_EOL;

		if (!is_string($data)) {
			$data = json_encode($data);
		}
		$result .= $data . PHP_EOL;

		$result .= '[[--- MSG_END #' . $index . ' ---]]' . PHP_EOL;
		return $result;
	}
}
