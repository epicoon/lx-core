<?php

namespace lx;

/**
 * Объекту класса не обязательно указывать расширение - он сам может найти подходящий файл
 * */
class ConfigFile extends File {
	protected $extensions = ['php', 'yaml', 'json'];
	protected $extension = null;

	/**
	 *
	 * */
	public function __construct($name, $path=null) {
		parent::__construct($name, $path);

		preg_match_all('/\.([^.\/]+)$/', $this->path, $matches);
		if (!empty($matches[1])) {
			$this->extension = $matches[1][0];
		} else {
			foreach ($this->extensions as $extension) {
				if ( file_exists($this->path . '.' . $extension) ) {
					$this->extension = $extension;
					$this->path .= '.' . $extension;
					$this->name .= '.' . $extension;
					break;
				}
			}
		}

		if (!$this->extension) {
			$this->extension = 'php';
			$this->path .= '.php';
			$this->name .= '.php';
		}
	}

	/**
	 *
	 * */
	public function getExtension() {
		return $this->extension;
	}

	/**
	 *
	 * */
	public function get() {
		$extension = $this->getExtension();
		if (!$extension) return null;

		$path = $this->getPath();

		if ($extension == 'php') {
			return $this->load();
		}

		if ($extension == 'yaml') {
			return (new YamlFile($path))->get();
		}

		if ($extension == 'json') {
			$data = parent::get();
			return json_decode($data, true);
		}
	}

	//todo public function put($data, $format=ConfigFile::FORMAT_PREATY) {}


	/**
	 * //todo - метод требует серьезных доработок. Код сюда пришел из логики, которая стала неактуальна,
	 * но функционал перспективный. Заново это писать не хочется
	 * Сейчас метод нигде не используется
	 * */
	public function insertParam($name, $value, $group = null) {
		$text = parent::get();
		$ext = $this->getExtension();
		if ($ext == 'php') {
			if ($group === null) {
				//todo - реализовать добаление нового параметра в корень просто, но этого в исходном коде не было

			} else {
				//todo - предусмотреть, что не был объявлен массив ('group' => 'value', а не 'group' => ['value'])
				//todo - найти число пробелов. Сейчас в регулярке их всегда 4

				// Если группы не было - она добавляется с пустым массивом
				if (!preg_match('/\''. $group .'\'\s*=>\s*\[/', $text)) {
					$text = preg_replace('/(\];\s*$)/', '    \''. $group .'\' => [' . chr(10) . '    ],' . chr(10) . '$1', $text);
				}

				// Ищем группу с рекурсивной подмаской
				$regexp = '/(\''. $group .'\')\s*=>\s*(?P<therec>\[((?>[^\[\]]+)|(?P>therec))*\])/';
				preg_match_all($regexp, $text, $matches);
				$match = $matches[0][0];

				$replacement = $match;
				// Если после последнего элемента не было запятой - надо добавить
				//todo - а что если элементов вообще не было? Н-р см. выше "Если группы не было"
				if (!preg_match('/,\s*\]$/', $replacement)) {
					$replacement = preg_replace('/(\s*\]$)/', ',$1', $replacement);
				}

				// Собственно вставка
				$replacement = preg_replace('/\]$/', '    \''.$name.'\' => \''.$path.'\',' . chr(10) . '    ]', $replacement);
				$text = str_replace($match, $replacement, $text);
			}
		} elseif ($ext == 'yaml') {
			if (!preg_match('/(^|\r)'. $group .':/', $text)) {
				$text = preg_replace('/(\s*$)/', $group . ':' . chr(10) . '$1', $text);
			}
			$text = preg_replace('/(^|\r)'. $group .':/', $group . ':' . chr(10) . '  ' . $name . ': ' . $path, $text);
		} elseif ($ext == 'json') {
			//todo
		}
		parent::put($text);
	}
}
