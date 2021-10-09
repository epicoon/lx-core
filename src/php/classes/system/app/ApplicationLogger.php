<?php

namespace lx;

use lx;
use Exception;

class ApplicationLogger implements LoggerInterface, FusionComponentInterface
{
	use FusionComponentTrait;

	const DEFAULT_CATEGORY = 'common';
	const DEFAULT_LOG_PATH = '@site/log';

	protected ?string $path = null;
	protected int $messagesCountForFile = 10000;
	protected ?array $allowedCategories = null;
	protected array $ignoredCategories = [];

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

    public function error(\Throwable $exception, array $additionalData = []): void
    {
        $errorString = ErrorHelper::renderErrorString($exception, $additionalData);
        $this->log($errorString, 'error');
    }

    protected function getLogDirectory(): DirectoryInterface
	{
		$dir = new Directory($this->getLogPath());
		$dir->make();
		return $dir;
	}

	protected function getLogPath(): string
	{
		$path = $this->path ?? self::DEFAULT_LOG_PATH;
		return lx::$app->conductor->getFullPath($path);
	}

	/**
	 * @param array|string $data
	 */
	protected function prepareData($data, array $source, int $index): string
	{
        $date = new \DateTime();
        $date = $date->format('Y-m-d h:i:s');

        $result = ($index > 1) ? PHP_EOL : '';
		$result .= '[[--- MSG_BEGIN #' . $index
            . ' (time: ' . $date
            . ', source: ' . $source['file'] . '::' . $source['line']
            . ') ---]]' . PHP_EOL;

		if (!is_string($data)) {
			$data = json_encode($data, JSON_PRETTY_PRINT);
		}
		$result .= $data . PHP_EOL;

		$result .= '[[--- MSG_END #' . $index . ' ---]]' . PHP_EOL;
		return $result;
	}
}
