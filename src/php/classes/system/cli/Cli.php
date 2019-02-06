<?php

namespace lx;

class Cli {
	const COMMANDS = [
		'exit' => '\q',
		'help' => ['\h', 'help'],
		'commands_list' => ['\cl', 'commands-list'],
		'move' => ['\g', 'goto'],
		'full_path' => ['\p', 'fullpath'],
		'reset_autoload_map' => ['\amr', 'autoload-map-reset'],

		'show_services' => ['\sl', 'services-list'],
		'show_modules' => ['\ml', 'modules-list'],
		'show_models' => 'models-list',

		'migrate_check' => 'migrate-check',
		'migrate_run' => 'migrate-run',

		'create_service' => ['\cs', 'create-service'],
		'create_module' => ['\cm', 'create-module'],
	];
	/*
	//todo
	выбор создаваемых компонентов для нового модуля - какие каталоги, надо ли файл пееропределяющий сам модуль...
	удаление модуля

	запрос на какую-нибудь модель

	??? надо ли с блоками отсюда работать
		создание вью-блоков
		просмотр дерева имеющихся блоков
	*/

	private $service = null;
	private $module = null;

	private $processor = null;
	private $processParams = [];
	private $inProcess = false;

	private $args = [];
	private $commandsHistory = [];
	private $commandsHistoryIndex = 0;

	public function __construct() {
		$this->processor = new CliProcessor(self::COMMANDS);
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
			if ($this->module !== null) {
				$text .= 'module:' . $this->module->name . '>: ';
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
							$command = $this->tryFinishCommand($currentInput);
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
	 * PRIVATE
	 *************************************************************************************************************************/

	/**
	 *
	 * */
	private function handleCommand($commandType) {
		$this->processor->setParams($this->processParams);
		$result = $this->processor->handleCommand($commandType, $this->args, $this->service, $this->module);
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
			$this->module = $this->processor->getModule();
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
	 *
	 * */
	private function identifyCommandType($command) {
		$keywords = self::COMMANDS;
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
		$keywords = self::COMMANDS[$key];
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
	private function tryFinishCommand($text) {
		if ($text{0} == '\\') {
			return false;
		}

		$len = mb_strlen($text);
		if ($len == 0) {
			return false;
		}

		$matches = [];

		foreach (self::COMMANDS as $keywords) {
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
