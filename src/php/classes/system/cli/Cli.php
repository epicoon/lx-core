<?php

namespace lx;

class Cli extends ApplicationTool {
	private $service = null;
	private $plugin = null;

	private $processor = null;
	private $processParams = [];
	private $inProcess = false;

	private $args = [];
	private $commandsHistory = [];
	private $commandsHistoryIndex = 0;

	public function __construct($app) {
		parent::__construct($app);
		$this->processor = new CliProcessor($app);
	}

	/**
	 * Крутит консольный ввод пока не будет осуществлен выход из cli
	 * */
	public function run() {
		$command = null;

		while (!$this->checkCommand($command, 'exit')) {
			if ($this->inProcess) {
				$this->handleCommand($this->inProcess);
				continue;
			}

			$text = 'lx-cli<';
			if ($this->plugin !== null) {
				$text .= 'plugin:' . $this->plugin->name . '>: ';
			} elseif ($this->service !== null) {
				$text .= 'service:' . $this->service->name . '>: ';
			} else {
				$text .= 'app>: ';
			}
			$input = Console::in(
				$text,
				['color' => 'yellow', 'decor' => 'b'],
				['color' => 'yellow'],
				[
					'up' => function() {
						if ($this->commandsHistoryIndex == 0) return;
						$this->commandsHistoryIndex--;
						Console::replaceInput($this->commandsHistory[$this->commandsHistoryIndex]);
					},
					'down' => function() {
						if ($this->commandsHistoryIndex == count($this->commandsHistory)) {
							return;
						}
						$this->commandsHistoryIndex++;
						if ($this->commandsHistoryIndex == count($this->commandsHistory)) {
							Console::replaceInput('');
							return;
						}
						Console::replaceInput($this->commandsHistory[$this->commandsHistoryIndex]);
					},
					'intercept' => [
						// TAB
						9 => function() use($text) {
							$currentInput = Console::getCurrentInput();
							$command = $this->autoCompleteCommand($currentInput);
							if ($command) {
								if ($command['common'] == $currentInput) {
									Console::outln();
									Console::outln(implode('  ', $command['matches']));
									Console::out($text, ['color' => 'yellow', 'decor' => 'b']);
									Console::out($currentInput, ['color' => 'yellow']);
								} else {
									Console::replaceInput($command['common']);
								}
							}
						}
					]
				]
			);

			if ($input == '') continue;

			$this->commandsHistory[] = $input;
			$this->commandsHistoryIndex = count($this->commandsHistory);
			list ($command, $args) = $this->parseInput($input);
			$this->args = $args;

			$commandType = $this->identifyCommandType($command);
			if ($commandType == 'exit') {
				break;
			}
			if ($commandType === false) {
				Console::outln("Unknown command '$command'. Enter 'help' to see commands list");
				continue;
			}

			$this->handleCommand($commandType);
		}
	}


	/**************************************************************************************************************************
	 * Обработка команд
	 *************************************************************************************************************************/

	/**
	 *
	 * */
	private function handleCommand($commandType) {
		$this->processor->setParams($this->processParams);
		$result = $this->processor->handleCommand($commandType, $this->args, $this->service, $this->plugin);
		foreach ($result['params'] as $key => $value) {
			$this->processParams[$key] = $value;
		}
		foreach ($result['invalidParams'] as $name) {
			unset($this->processParams[$name]);
		}
		foreach ($result['output'] as $row) {
			if ($row[0] == 'in') {
				$this->processParams[$result['need']] = Console::in($row[1], $row[2]);
				$this->inProcess = $commandType;
				return;
			}
			Console::{$row[0]}($row[1], $row[2]);
		}

		if ($result['keepProcess']) {
			$this->inProcess = $commandType;
		} else {
			$this->inProcess = false;
			$this->processParams = [];
			$this->service = $this->processor->getService();
			$this->plugin = $this->processor->getPlugin();
		}
	}


	/**************************************************************************************************************************
	 * Методы, обслуживающие базовую работу командной строки
	 *************************************************************************************************************************/

	/**
	 * Вычленяет из введенного текста имя команды и аргументы:
	 * Или так:
	 * lx-cli<app>: command arg1 arg2 "arg3 by several words"
	 * Или так:
	 * lx-cli<app>: command -k=arg1 --key="arg2 by several words"
	 * Но не перечислением и ключами одновременно (в этом случае ключи учтутся, перечисленные будут проигнорированы)
	 * @param $input string - строка консольного ввода
	 * */
	private function parseInput($input) {
		preg_match_all('/".*?"/', $input, $matches);
		$matches = $matches[0];
		$line = preg_replace('/".*?"/', '№№№', $input);
		$arr = explode(' ', $line);
		if (!empty($matches)) {
			$counter = 0;
			foreach ($arr as &$value) {
				if (strpos($value, '№№№') === false) {
					continue;
				}
				$value = str_replace('№№№', $matches[$counter++], $value);
			}
			unset($value);
		}

		$command = array_shift($arr);
		$counted = [];
		$assoc = [];

		foreach ($arr as $item) {
			if ($item{0} != '-') {
				$counted[] = $item;
				continue;
			}
			$pos = strpos($item, '=');
			$key = trim(substr($item, 0, $pos), '-');
			$value = trim(substr($item, $pos+1, strlen($item)), '"');
			$assoc[$key] = $value;
		}

		$args = empty($assoc) ? $counted : $assoc;
		return [$command, $args];
	}

	/**
	 * Конвертирует команду в её ключ
	 * */
	private function identifyCommandType($command) {
		$keywords = $this->processor->getCommandsList();
		foreach ($keywords as $key => $value) {
			$value = (array)$value;
			foreach ($value as $commandName) {
				if ($command == $commandName) {
					return $key;
				}
			}
		}
		return false;
	}

	/**
	 * Проверяет соответствует ли команда какой-то категории
	 * @param $command string - команда, уже вычлененная из строки консольного ввода
	 * */
	private function checkCommand($command, $key) {
		$keywords = $this->processor->getCommandsList()[$key];
		if (is_array($keywords)) {
			return (array_search($command, $keywords) !== false);
		}
		return $command == $keywords;
	}

	/**
	 * Пытается дополнить введенную команду:
	 * - находит ближайшее общее если подходящих команд несколько
	 * - помимо общего возвращает список подходящих команд
	 * @param $text string - строка, которую требуется дополнить
	 * */
	private function autoCompleteCommand($text) {
		if ($text{0} == '\\') {
			return false;
		}

		$len = mb_strlen($text);
		if ($len == 0) {
			return false;
		}

		$matches = [];

		foreach ($this->processor->getCommandsList() as $keywords) {
			foreach ((array)$keywords as $command) {
				if ($command != $text && preg_match('/^'. $text .'/', $command)) {
					$matches[] = $command;
				}
			}
		}

		if (empty($matches)) {
			return false;
		}

		$commonPart = $text;
		$i = $len;
		while (true) {
			$latterMatch = true;
			if ($i >= mb_strlen($matches[0])) break;
			$latter = $matches[0]{$i};
			foreach ($matches as $command) {
				if ($i >= mb_strlen($command)) break(2);
				if ($latter != $command{$i}) break(2);
			}
			$commonPart .= $latter;
			$i++;
		}

		return [
			'common' => $commonPart,
			'matches' => $matches
		];
	}
}
