<?php

namespace lx;

class MultiBox extends Box {
	/**
	 * $config = [
	 *	'markHeight' => height for marks row,
	 *	'animation'  => boolean,
	 *	'sheets' => array
	 * ]
	 * 
	 * Массив 'sheets' может содердать элементы в форматах:
	 * 1. 'sheets' => [ markName ];  // по умолчанию создается lx\Box (в данном случае без особых конфигураций)
	 * 2. 'sheets' => [ markName => widgetClassName ];
	 * 3. 'sheets' => [ markName => widgetConfig ];  // по умолчанию создается lx\Box (в данном слуае с переданными конфигурациями)
	 * 4. 'sheets' => [ markName => [widgetClassName, widgetConfig] ];
	 * 5. 'sheets' => [ markName => blockPath ];
	 */
	public function __construct($config=[]) {
		$config = DataObject::create($config);
		parent::__construct($config);

		$sheets = $config->getFirstDefined('sheets', []);
		$markH = $config->getFirstDefined('markHeight', '25px');
		$amt = count($sheets);
		if (!$amt) return;
		$markW = 100 / $amt;

		$i = 0;
		$this->begin();
		foreach ($sheets as $key => $value) {
			if (is_numeric($key)) {
				$mark = $value;
				$sheet = null;
			} else {
				$mark = $key;
				$sheet = $value;
			}

			new Box([
				'key' => 'mark',
				'geom' => [($markW * $i++).'%', 0, $markW.'%', $markH],
				'text' => $mark,
				'css' => 'lx-MultiBox-mark',
			]);

			$this->createSheet($sheet, $markH);
		}
		$this->end();

		$this->select(0);
		if ($config->animation) $this->__timer = $config->animation;
	}

	public function mark($num) {
		if (!$this->contain('mark') || $num >= $this->get('mark')->len) return null;
		return $this->get('mark')->at($num);
	}

	public function sheet($num) {
		if (!$this->contain('sheet') || $num >= $this->get('sheet')->len) return null;
		return $this->get('sheet')->at($num);
	}

	public function activeMark() {
		return $this->mark($this->activeSheetNum);
	}

	public function activeSheet() {
		return $this->sheet($this->activeSheetNum);
	}

	public function marks() {
		if (!$this->contain('mark')) return null;
		return new Collection($this->get('mark'));
	}

	public function sheets() {
		if (!$this->contain('sheet')) return null;
		return new Collection($this->get('sheet'));
	}

	public function setCondition($num, $func) {
		$this->mark($num)->condition = JsCompiler::compileCodeInString($func);
	}

	public function select($num) {
		if ($this->activeSheetNum !== null) {
			$this->activeMark()->removeClass('lx-MultiBox-active');
			$this->activeSheet()->hide();
		}
		$this->activeSheetNum = $num;
		$this->activeMark()->addClass('lx-MultiBox-active');
		$this->activeSheet()->show();
	}

	/**
	 * Получаем информацию для создания в одном из форматов:
	 * 1. null // по умолчанию создается lx\Box (в данном случае без особых конфигураций)
	 * 2. widgetClassName
	 * 3. widgetConfig  // по умолчанию создается lx\Box (в данном слуае с переданными конфигурациями)
	 * 4. [widgetClassName, widgetConfig]
	 * 5. blockPath
	 * */
	protected function createSheet($sheetInfo, $markH) {
		$className = Box::class;
		$config = [
			'key' => 'sheet',
			'top' => $markH,
		];
		$block = false;

		// Дефолтная коробка
		if (is_string($sheetInfo)) {
			//todo - разобраться
			// if (ClassHelper::exists($sheetInfo)) $className = $sheetInfo;
			//todo проверка на существование блока не помешает
			// else
				$block = $sheetInfo;
		} else if (is_array($sheetInfo)) {
			//todo реализовать
		}

		$newSheet = new $className($config);
		if ($block) $newSheet->setBlock($block);
		$newSheet->hide();
	}
}
