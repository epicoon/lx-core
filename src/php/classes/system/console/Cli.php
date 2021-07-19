<?php

namespace lx;

class Cli
{
	private ?Service $service = null;
	private ?Plugin $plugin = null;
	private CliProcessor $processor;
	private ?CliCommandsList $commandsList = null;
	private array $processParams = [];
	private ?string $inProcess = null;
	private array $args = [];
	private array $commandsHistory = [];
	private int $commandsHistoryIndex = 0;

	public function __construct()
	{
		$this->processor = new CliProcessor();
	}

	public function run(): void
	{
		$command = null;
		while (!$this->checkCommand($command, '\q')) {
			$command = $this->cycle($command);
		}
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function cycle(?string $command): ?string
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
						
						$command = $this->processor->autoCompleteCommand($currentInput, $this->getCommandsList());
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
		list ($command, $args) = $this->processor->parseInput($input);

		if (!$this->validateCommandName($command)) {
            Console::outln("Unknown command '$command'. Enter 'help' to see commands list");
            return $command;
        }

		if ($command == '\q') {
			return $command;
		}

		$this->args = $args;
		$this->handleCommand($command);
		return $command;
	}

	private function getLocationText(): string
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

	private function handleCommand(string $commandName): void
	{
		$this->processor->setParams($this->processParams);
		$result = $this->processor->handleCommand(
		    $commandName,
            CliProcessor::COMMAND_TYPE_CONSOLE,
            $this->args,
            $this->service,
            $this->plugin
        );
		foreach ($result['params'] as $key => $value) {
			$this->processParams[$key] = $value;
		}
		foreach ($result['invalidParams'] as $name) {
			unset($this->processParams[$name]);
		}
		foreach ($result['output'] as $row) {
			if ($row[0] == 'in') {
				$this->processParams[$result['need']] = Console::in(['hintText' => $row[1], 'hintDecor' => $row[2]]);
				$this->inProcess = $commandName;
				return;
			}
			Console::{$row[0]}($row[1], $row[2]);
		}

		if ($result['keepProcess']) {
			$this->inProcess = $commandName;
		} else {
			$this->inProcess = null;
			$this->processParams = [];
		}

		$this->service = $this->processor->getService();
		$this->plugin = $this->processor->getPlugin();
	}

	private function validateCommandName(string $command): bool
	{
	    $commands = $this->getCommandsList();
	    return $commands->commandExists($command);
	}

	private function checkCommand(?string $command, string $expectedCommand): bool
	{
	    if ($command === null) {
	        return false;
        }

	    return $command == $expectedCommand;
	}

	private function getCommandsList(): CliCommandsList
	{
		if (!$this->commandsList) {
            $this->commandsList = $this->processor->getCommandsList()->getSubList([
				CliProcessor::COMMAND_TYPE_COMMON,
				CliProcessor::COMMAND_TYPE_CONSOLE,
			]);
		}

		return $this->commandsList;
	}
}
