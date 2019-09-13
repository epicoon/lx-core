<?php

namespace lx;

class ApplicationLogger extends ApplicationComponent implements LoggerInterface {
	const DEFAULT_CATEGORY = 'common';
	const DEFAULT_LOG_PATH = '@site/log';
	
	protected $logPath;
	protected $messagesCountForFile = 10000;
	
	public function log($data, $category = null) {
		if ($category === null) {
			$category = self::DEFAULT_CATEGORY;
		}

		$logDir = $this->getLogDirectory();
		$dir = $logDir->getOrMakeDirectory($category);

		$date = new \DateTime();
		$date = $date->format('Y-m-d');

		$ff = $dir->getFiles($date . '*.log', Directory::FIND_NAME)->getData();
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
	
	public function getLogDirectory() {
		$dir = new Directory($this->getLogPath());
		$dir->make();
		return $dir;
	}
	
	public function getLogPath() {
		$path = $this->logPath ?? self::DEFAULT_LOG_PATH;
		return $this->app->conductor->getFullPath($path);
	}

	protected function prepareData($data, $index) {
		$result = '[[MSG_BEGIN #' . $index . ']]' . PHP_EOL;

		/*
		TODO
		Доп.инфа, как минимум время записи 
		Нормально упаковывать инфу в зависимости от типа данных
		число
		строка
		массив
		объект
		*/
		if (!is_string($data)) {
			$data = json_encode($data);
		}
		$result .= $data . PHP_EOL;

		$result .= '[[MSG_END #' . $index . ']]' . PHP_EOL;
		return $result;
	}
}
