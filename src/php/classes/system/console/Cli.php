<?php

namespace lx;

/**
 * Class Cli
 * @package lx\
 */
class Cli
{
	/** @var Service|null */
	private $service = null;

	/** @var Plugin|null */
	private $plugin = null;

	/** @var CliProcessor */
	private $processor;

	/** @var array */
	private $commandsList = [];

	/** @var array */
	private $processParams = [];

	/** @var string|false */
	private $inProcess = false;

	/** @var array */
	private $args = [];

	/** @var array */
	private $commandsHistory = [];

	/** @var int */
	private $commandsHistoryIndex = 0;

	/**
	 * Cli constructor.
	 */
	public function __construct()
	{
		$this->processor = new CliProcessor();
	}

	/**
	 * Cycle for console enter
	 */
	public function run()
	{
		$command = null;
		while (!$this->checkCommandType($command, 'exit')) {
			$command = $this->cycle($command);
		}
	}


	/**************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/

	/**
	 * @param string|null $command
	 * @return string|null
	 */
	private function cycle($command)
	{
		if ($this->inProcess) {
			$this->handleCommand($this->inProcess);
			return $command;
		}

		$locationText = $this->getLocationText();
		$input = Console::in([
			'hintText' => $locationText,
			'hintDecor' => ['color' => 'yellow', 'decor' => 'b'],
			'textDecor' => ['color' => 'yellow'],
			'callbacks' => [
				'up' => function () {
					if ($this->commandsHistoryIndex == 0) return;
					$this->commandsHistoryIndex--;
					Console::replaceInput($this->commandsHistory[$this->commandsHistoryIndex]);
				},
				'down' => function () {
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
					9 => function () use ($locationText) {
						$currentInput = Console::getCurrentInput();
						$command = $this->autoCompleteCommand($currentInput);
						if ($command) {
							if ($command['common'] == $currentInput) {
								Console::outln();
								Console::outln(implode('  ', $command['matches']));
								Console::out($locationText, ['color' => 'yellow', 'decor' => 'b']);
								Console::out($currentInput, ['color' => 'yellow']);
							} else {
								Console::replaceInput($command['common']);
							}
						}
					}
				]
			]
		]);

		if ($input == '') {
			return $command;
		}

		$this->commandsHistory[] = $input;
		$this->commandsHistoryIndex = count($this->commandsHistory);
		list ($command, $args) = $this->parseInput($input);

		$commandType = $this->identifyCommandType($command);
		if ($commandType == 'exit') {
			return $command;
		}
		if ($commandType === false) {
			Console::outln("Unknown command '$command'. Enter 'help' to see commands list");
			return $command;
		}

		$this->args = $args;
		$this->handleCommand($commandType);
		return $command;
	}

	/**
	 * @return string
	 */
	private function getLocationText()
	{
		$result = 'lx-cli<';
		if ($this->plugin !== null) {
			$result .= 'plugin:' . $this->plugin->name . '>: ';
		} elseif ($this->service !== null) {
			$result .= 'service:' . $this->service->name . '>: ';
		} else {
			$result .= 'app>: ';
		}
		return $result;
	}

	/**
	 * @param string $commandType
	 */
	private function handleCommand($commandType)
	{
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
		}

		$this->service = $this->processor->getService();
		$this->plugin = $this->processor->getPlugin();
	}

	/**
	 * Methos parses command name and arguments from console input
	 *
	 * Example for arguments as enumeration:
	 * lx-cli<app>: command arg1 arg2 "arg3 by several words"
	 *
	 * Example for arguments by keys:
	 * lx-cli<app>: command -k=arg1 --key="arg2 by several words"
	 *
	 * Enumetaion and keys don't work together. In that case will be used only arguments by keys.
	 *
	 * @param string $input
	 */
	private function parseInput($input)
	{
		$arr = StringHelper::smartSplit($input, ['delimiter' => ' ', 'save' => '"']);
		$command = array_shift($arr);

		$counted = [];
		$assoc = [];
		foreach ($arr as $item) {
			if ($item{0} != '-') {
				$counted[] = trim($item, '"');
				continue;
			}
			$pos = strpos($item, '=');
			$key = trim(substr($item, 0, $pos), '-');
			$value = trim(substr($item, $pos + 1, strlen($item)), '"');
			$assoc[$key] = $value;
		}

		$args = empty($assoc) ? $counted : $assoc;
		return [$command, $args];
	}

	/**
	 * Method converts command name to command key
	 *
	 * @param string $command
	 * @return string|false
	 */
	private function identifyCommandType($command)
	{
		$keywords = $this->getCommandsList();
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
	 * Method checks command name due to command type
	 *
	 * @param string $command
	 * @param string $commandsGroupName
	 * @return bool
	 */
	private function checkCommandType($command, $commandsGroupName)
	{
		$keywords = $this->getCommandsList()[$commandsGroupName] ?? null;
		if (is_array($keywords)) {
			return (array_search($command, $keywords) !== false);
		}
		return $command == $keywords;
	}

	/**
	 * Method tries to complete part of entered command
	 * If there are several due alternatives will be returned the closest common part and array of alternatives
	 *
	 * @param string $text
	 * @return array|false
	 */
	private function autoCompleteCommand($text)
	{
		$len = mb_strlen($text);
		if ($len == 0 || $text{0} == '\\') {
			return false;
		}

		$matches = [];
		foreach ($this->getCommandsList() as $keywords) {
			foreach ((array)$keywords as $command) {
				if ($command != $text && preg_match('/^' . $text . '/', $command)) {
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

	/**
	 * @return array
	 */
	private function getCommandsList()
	{
		if (empty($this->commandsList)) {
			$this->commandsList = $this->processor->getCommandsList([
				CliProcessor::COMMAND_TYPE_COMMON,
				CliProcessor::COMMAND_TYPE_CONSOLE_ONLY,
			]);
		}

		return $this->commandsList;
	}
}
