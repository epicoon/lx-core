<?php

namespace lx;

class ConsoleApplication extends AbstractApplication {
	protected $argv;

	public function __construct($argv = null) {
		parent::__construct();

		$this->argv = $argv;
	}

	 /**
	  * Запуск консольного приложения
	  * */
	public function run() {
		if (!$this->argv) {
			return;
		}

	 	$command = array_pop($this->argv);
	 	switch ($command) {
	 		case 'cli':
	 			(new Cli())->run();
	 			break;

	 		default:
	 			/*
	 			//todo - надо ли вообще делать на таком уровне обработку запросов?
	 			Можно сделать так, чтобы консольные команды для сервисов работали только из-под CLI
	 			Зашел в CLI, зашел в модуль, работаешь с ним через его команды
	 			*/
	 			break;
	 	}
	}
}
