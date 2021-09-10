<?php

namespace lx;

class DevLogger implements LoggerInterface
{
	private int $truncateLimit = 1000;
	private int $truncateGoal = 100;

	/**
	 * @param string|array $data
	 */
	public function log($data, ?string $category = null): void
	{
		$msg = $this->prepareMessage($data);
		if (!$msg) {
			return;
		}

		$file = new File($this->getLogPath() . '/' . $category);
		$this->writeFile($file, $msg);
	}

    public function init(array $config): void
    {
        // pass
    }

	/**
	 * Truncates count of messages in the log-file
	 */
	public function truncate(): void
	{
		$dir = $this->getLogDirectory();
		$files = $dir->getFiles()->toArray();

		foreach ($files as $file) {
			$this->truncateFile($file);
		}
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * @param string|array $data
	 */
	private function prepareMessage($data): string
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

				if (array_key_exists('file', $item)) {
                    $rows[] = '- file: ' . $item['file'] . ' [line:' . $item['line'] . ']';
                } else {
                    $rows[] = '- file: ?';
                }

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

	private function truncateFile(FileInterface $file): void
	{
		$msgList = $this->readFile($file);
		if (count($msgList) <= $this->truncateLimit) {
			return;
		}

		$offset = count($msgList) - $this->truncateGoal;

		$msgList = array_slice($msgList, $offset);
		$this->rewriteFile($file, $msgList);
	}

	private function readFile(FileInterface $file): array
	{
		$text = $file->get();
		$list = explode($this->msgDelimiter(), $text);
		return $list;
	}

	private function writeFile(FileInterface $file, string $msg): void
	{
		if (!$file->exists()) {
			$file->put($msg);
			return;
		}

		$msg = $this->msgDelimiter() . $msg;
		$file->append($msg);
	}

	private function rewriteFile(FileInterface $file, array $msgList): void
	{
		$text = implode($this->msgDelimiter(), $msgList);
		$file->put($text);
	}

	private function msgDelimiter(): string
	{
		return PHP_EOL . PHP_EOL
			. '-------------------------------'
			. PHP_EOL . PHP_EOL;
	}

	private function getLogDirectory(): DirectoryInterface
	{
		$dir = new Directory($this->getLogPath());
		$dir->make();
		return $dir;
	}

	private function getLogPath(): string
	{
		return \lx::$conductor->devLog;
	}
}
