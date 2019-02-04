<?php

namespace lx;

class SourcePluger {
	
	/**
	 * Подключает скрипты, указанные в js-файлах
	 * */
	public function plugScripts($code) {
		return preg_replace_callback('/(?<!\/\/ )(?<!\/\/)#script [\'"]?(.*?)[\'"]?;/', function($matches) {
			$path = $matches[1];
			if (!preg_match('/\.js$/', $path)) $path .= '.js';
			ModuleBuilder::active()->getModule()->script($path);
			return '';
		}, $code);
	}

	/**
	 * Загрузка js-даннфх из yaml-файла
	 * */
	public static function loadYaml($code, $parentDir) {
		$pattern = '/(?<!\/ )(?<!\/)#yaml [\'"]?(.*?)[\'"]?([;,)])/';
		$code = preg_replace_callback($pattern, function($matches) use ($parentDir) {
			$fileName = $matches[1];
			$file = self::findFile($fileName, $parentDir, ['yaml', 'yml']);
			if (!$file->exists()) return "null{$matches[2]}";

			$text = $file->get();
			$result = (new Yaml($text, $file->getParentDirPath()))->parseToJs();
			return "$result{$matches[2]}";
		}, $code);

		return $code;
	}

	/**
	 * Поиск файла
	 * */
	private static function findFile($fileName, $parentDir, $extensions=[]) {
		/*
		Алгоритм поиска файла:
		1. Определение расширения
			- если расширения не переданы - считается, что имя файла передано полное
			- если расширения переданы, и одно из них уже есть в имени файла, считается, что имя файла полное и проверяется только оно
			- если расширения в имени файла нет - будут проверены все варианты имени со всеми переданными расширениями
		2. Если имя файла начинается на '/'
			- файл будет искаться от корня сайта - если найден он вернется
			- если файл не найден будет возвращен false
		3. Файл начинается не на '/':
			- будет проверен путь относительно $parentDir - если найден он вернется
			- если файл не найден - будет проверен относительно рендерящегося модуля - если найден он вернется
			- если не найден - вернется false
		*/

		// 1. Определение имен файлов, которые будем искать с учетом переданных расширений
		$fileNames = [];
		if ($extensions == [] || !is_array($extensions)) $fileNames = [$fileName];
		foreach ($extensions as $extension) {
			if (preg_match('/\.' . $extension . '$/', $fileName)) {
				$fileNames = [$fileName];
				break;
			}
			$fileNames[] = "$fileName.$extension";
		}

		foreach ($fileNames as $name) {
			// 2. Проверка на корневой путь
			if ($name{0} == '/') {
				$fullName = \lx::sitePath() . $name;
				if (file_exists($fullName)) return new File($fullName);
			// 3. Локальные пути
			} else {
				$fullName = $parentDir . '/' . $name;
				if (file_exists($fullName)) return new File($fullName);

				$fullName = ModuleBuilder::active()->getModule()->getPath() . '/' . $name;
				if (file_exists($fullName)) return new File($fullName);
			}
		}

		return false;
	}
}
