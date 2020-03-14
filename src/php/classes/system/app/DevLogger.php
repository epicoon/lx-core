<?php

namespace lx;

/**
 * Class DevLogger
 * @package lx
 */
class DevLogger implements LoggerInterface
{
	/** @var int */
	private $truncateLimit = 1000;

	/** @var int */
	private $truncateGoal = 100;

	/**
	 * @param string|array $data
	 * @param string $category
	 */
	public function log($data, $category = null)
	{
		$msg = $this->prepareMessage($data);
		if (!$msg) {
			return;
		}

		$file = new File($this->getLogPath() . '/' . $category);
		$this->writeFile($file, $msg);
	}

	/**
	 * Truncates count of messages in the log-file
	 */
	public function truncate()
	{
		$dir = $this->getLogDirectory();
		$files = $dir->getFiles()->toArray();

		foreach ($files as $file) {
			$this->truncateFile($file);
		}
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @param string|array $data
	 * @return string
	 */
	private function prepareMessage($data)
	{
		if (empty($data)) {
			return '';
		}

		$rows = ['Date: ' . (new \DateTime())->format('Y-m-d H:i:s')];
		if (is_string($data)) {
			$data = ['msg' => $data];
		}

		if (array_key_exists('_', $data)) {
			$loc = $data['_'];
			unset($data['_']);
			$rows[] = 'Location:';
			$i = 0;
			$rows[] = '--- File: ' . $loc[$i++];
			$rows[] = '--- Class: ' . $loc[$i++];
			if (count($loc) == 5) {
				$rows[] = '--- Trait: ' . $loc[$i++];
			}
			$rows[] = '--- Method: ' . $loc[$i++];
			$rows[] = '--- Line: ' . $loc[$i];
		}

		if (array_key_exists('msg', $data)) {
			$msg = $data['msg'];
			unset($data['msg']);
			$rows[] = 'Message: ' . $msg;
		}

		$trace = null;
		if (array_key_exists('__trace__', $data)) {
			$trace = $data['__trace__'];
			unset($data['__trace__']);
		}

		if (!empty($data)) {
			$rows[] = 'Data:';
			foreach ($data as $key => $value) {
				if (!is_string($value)) {
					$value = json_encode($value);
				}
				$rows[] = '--- ' . $key . ': ' . $value;
			}
		}

		if ($trace) {
			$rows[] = 'Trace:';
			foreach ($trace as $item) {
				$args = $item['args'];
				foreach ($args as &$arg) {
					if (is_object($arg)) {
						$arg = 'object';
					} elseif (is_array($arg)) {
						$arg = 'array';
					} elseif (is_string($arg)) {
						$arg = "'$arg'";
					} elseif ($arg === null) {
						$arg = 'null';
					} elseif ($arg === true) {
						$arg = 'true';
					} elseif ($arg === false) {
						$arg = 'false';
					}
				}
				unset($arg);
				$args = implode(', ', $args);

				$rows[] = '- file: ' . $item['file'] . ' [line:' . $item['line'] . ']';
				if (array_key_exists('class', $item)) {
					$rows[] = '  ' . $item['class'] . '::' . $item['function'] . '(' . $args . ')';
				} else {
					$rows[] = '  ' . $item['function'] . '(' . $args . ')';
				}
			}
		}

		$result = implode(PHP_EOL, $rows);
		return $result;
	}

	/**
	 * @param File $file
	 */
	private function truncateFile($file)
	{
		$msgList = $this->readFile($file);
		if (count($msgList) <= $this->truncateLimit) {
			return;
		}

		$offset = count($msgList) - $this->truncateGoal;

		$msgList = array_slice($msgList, $offset);
		$this->rewriteFile($file, $msgList);
	}

	/**
	 * @param File $file
	 * @return array
	 */
	private function readFile($file)
	{
		$text = $file->get();
		$list = explode($this->msgDelimiter(), $text);
		return $list;
	}

	/**
	 * @param File $file
	 * @param string $msg
	 */
	private function writeFile($file, $msg)
	{
		if (!$file->exists()) {
			$file->put($msg);
			return;
		}

		$msg = $this->msgDelimiter() . $msg;
		$file->append($msg);
	}

	/**
	 * @param File $file
	 * @param array $msgList
	 */
	private function rewriteFile($file, $msgList)
	{
		$text = implode($this->msgDelimiter(), $msgList);
		$file->put($text);
	}

	/**
	 * @return string
	 */
	private function msgDelimiter()
	{
		return PHP_EOL . PHP_EOL
			. '-------------------------------'
			. PHP_EOL . PHP_EOL;
	}

	/**
	 * @return Directory
	 */
	private function getLogDirectory()
	{
		$dir = new Directory($this->getLogPath());
		$dir->make();
		return $dir;
	}

	/**
	 * @return string
	 */
	private function getLogPath()
	{
		return \lx::$conductor->devLog;
	}
}
