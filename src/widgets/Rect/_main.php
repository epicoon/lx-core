<?php

namespace lx;

/**
 * @group {i18n:widgets}
 * */
class Rect extends DataObject {
	public
		$located = false; //todo

	protected
		$tag,
		$html = null,
		$attrs = [],
		$style = [],
		$classList,
		$geomBuffer = [],

		$_parent = null;

	//---------------------------------------------------------
	/* 1. Constructor */
	public function __construct($config = []) {
		$config = $this->preBuild($config);

		$this->classList = new Vector();

		$this->type = $this->className(false);
		$namespace = $this->namespaceName();
		if ($namespace != 'lx') $this->_namespace = str_replace('\\', '.', $namespace);

		$this->tag = $config->tag ? $config->tag : $this->tagForDOM();

		$this->key = $config->key
			? $config->key
			: ($config->field ? $config->field : Renderer::getKey());
		$this->addClass('lx');

		$this->setParent($config);

		$this->applyConfig($config);
		if (!$config->postBuildOff) $this->postBuild();
	}

	/**
	 * Тэг класса
	 * */
	protected function tagForDOM() {
		return 'div';
	}

	/**
	 * Метод, вызываемый в конструкторе и модифицирующий конфиг конструирования до начала непосредственного конструирования
	 * */
	protected function preBuild($config) {
		return DataObject::create($config);
	}

	/**
	 * Метод, вызываемый в конструкторе для выполенния действий, когда сущность действительно построена, связи (с родителем) выстроены
	 * */
	public function postBuild() {

	}

	/**
	 *	field
	 *	html
	 *	baseCss
	 *	css
	 *	style
	 *	click
	 *	move | parentResize | parentMove
	 * */
	public function applyConfig($config=null) {
		if ($config === null) return $this;

		if ($config->field) $this->_field = $config->field;
		if ($config->html) $this->html($config->html);

		if ($config->baseCss) $this->setBaseCss($config->baseCss);
		else $this->addClass( $this->getBaseCss() );

		if ($config->css) $this->addClass($config->css);

		if ($config->disabled !== null) $this->disabled($config->disabled);

		if ($config->style) {
			foreach ($config->style as $key => $value) {
				if (method_exists($this, $key)) {
					$arr = (is_array($value)) ? $value : [$value];
					call_user_func_array(array($this, $key), $arr);
				} else $this->style[$key] = $value;
			}
		}

		if ($config->click) $this->click($config->click);
		if ($config->blur) $this->blur($config->blur);

		if ($config->move) $this->move($config->move);
		else if ($config->parentResize) $this->move(['parentResize' => true]);
		else if ($config->parentMove) $this->move(['parentMove' => true]);

		return $this;
	}

	/**
	 * Статический метод для массового создания сущностей
	 * */
	public static function construct() {
		$arguments = func_get_args();
		$count = $arguments[0];
		$config = (count($arguments) > 1) ? $arguments[1] : [];
		$configurator = (count($arguments) > 2) ? $arguments[2] : [];

		$parent = null;
		$next = null;
		if (isset($args['before'])) {
			$next = $args['before'];
			$parent = $next->parent;
			unset($args['before']);
			$args['parent'] = '';
		} else if (isset($args['after'])) {
			$parent = $args['after']->parent;
			$next = $args['after']->nextSibling();
			unset($args['after']);
			$args['parent'] = '';
		} else if (isset($args['parent'])) {
			$parent = $args['parent'];
			$args['parent'] = '';
		}

		$result = new Collection();
		$config['postBuildOff'] = true;
		for ($i=0; $i<$count; $i++) {
			if (isset($configurator['preBuild']))
				$config = $configurator['preBuild']($config, $i);
			$cnstr = self::className();
			$obj = new $cnstr($config);
			$result->add($obj);
		}

		if ($parent) $parent->insert($result, $next);

		$result->each(function($a, $i) use ($configurator) {
			$a->postBuild();
			if (isset($configurator['postBuild']))
				$configurator['postBuild']($a, $i);
		});

		return $result;
	}
	/* 1. Constructor */
	//---------------------------------------------------------


	//---------------------------------------------------------
	/* 2. Common */
	public function &__get($prop) {
		if ($prop == 'parent') {
			//todo если достаточно получения родителя false, то вообще можно не переопределять геттеры и сеттеры
			// если корневой блок-элемент модуля - у него поле родителя явно в false стоит
			if ($this->_parent === false) {
				$this->nullCash = null;
				return $this->nullCash;
			}
			return $this->_parent;
		}

		return parent::__get($prop);
	}

	public function __set($prop, $val) {
		if ($prop == 'parent') {
			$this->_parent = $val;
			return;
		}

		parent::__set($prop, $val);
	}

	/**
	 * Строка, показывающая расположение в иерархии элементов - перечисление ключей всех родительских элементов через /
	 * */
	public function path() {
		$arr = new Vector();
		$temp = $this;
		$path = '';

		while ($temp) {
			$arr->push( $temp->index ? $temp->key . '[' . $temp->index . ']' : $temp->key );
			$temp = $temp->parent;
		}

		$arr->eachRevert(function($a, $i) use ($path) {
			$path .=  ($i ? '/' : '') . $a;
		});

		return $path;
	}

	/**
	 * Если ключ у элемента групповой, то к нему добавляется индекс
	 * */
	public function fullKey() {
		if ($this->index === null) return $this->key;
		return $this->key . '['. $this->index .']';
	}

	/**
	 * Путь для запроса изображений (идея единого хранилища изображений в рамках модуля)
	 * */
	public function imagePath($name='') {
		return $this->getModule()->getImageRoute($name);
	}

	/**
	 * Управление активностью элемента
	 * */
	public function disabled($bool = null) {
		if ($bool === null) return $this->_disabled;

		$this->removeClass( $this->getBaseCss() );
		$this->_disabled = $bool;
		$this->addClass( $this->getBaseCss() );
		return $this;
	}

	/*
	 * проверяет есть ли свойство в массиве lx, любой глубины вложенности
	 * */
	private function lxIsSet($names) {
		if (!is_array($names)) $names = [$names];
		$arr = $this->_prop;
		for ($i=0; $i<count($names); $i++) {
			$name = $names[$i];
			if (!isset($arr[$name])) return false;
			$arr = $arr[$name];
		}
		return true;
	}

	/*
	 * Свойство в массиве lx, любой глубины вложенности, при необходимости создаст путь
	 * */
	private function setLx($names, $val) {
		if (!is_array($names)) $names = [$names];
		$arr = &$this->_prop;
		for ($i=0, $l=count($names); $i<$l-1; $i++) {
			$name = $names[$i];
			if (!isset($arr[$name])) $arr[$name] = [];
			$arr = &$arr[$name];
		}
		$arr[$names[$i]] = $val;
	}
	/* 2. Common */
	//---------------------------------------------------------


	//---------------------------------------------------------
	/* 3. Html and Css */
	public function tag() {
		return $this->tag;
	}

	public function attr($name, $val) {
		$this->attrs[$name] = $val;
		return $this;
	}

	public function style($name, $val=null) {
		if ($val===null && is_array($name)) {
			foreach ($name as $key => $value)
				$this->style($key, $value);
			return $this;
		}

		$this->style[$name] = $val;
		return $this;
	}

	public function html($html = null) {
		if ($html == null) return $this->html;
		$this->html = $html;
		return $this;
	}

	/**
	 * Можно передавать аргументы двумя путями:
	 * 1. $elem->addClass($class1, $class2);
	 * 2. $elem->addClass([$class1, $class2]);
	 * */
	public function addClass(/*...args*/) {
		$args = func_get_args();
		if (is_array($args[0])) $args = $args[0];

		foreach ($args as $name)
			if ($name != '') $this->classList->pushUnique($name);
		return $this;
	}

	/**
	 * Можно передавать аргументы двумя путями:
	 * 1. $elem->removeClass($class1, $class2);
	 * 2. $elem->removeClass([$class1, $class2]);
	 * */
	public function removeClass(/*...args*/) {
		$args = func_get_args();
		if (is_array($args[0])) $args = $args[0];

		foreach ($args as $name)
			if ($name != '') $this->classList->remove($name);
		return $this;
	}

	/**
	 * Проверить имеет ли элемент css-класс
	 * */
	public function hasClass($name) {
		return $this->classList->contain($name);
	}

	/**
	 * Если элемент имеет один из классов, то он будет заменен на второй
	 * Если передан только один класс - он будет установлен, если его не было, либо убран, если он был у элемента
	 * */
	public function toggleClass($class1, $class2='') {
		if ($this->hasClass($class1)) {
			$this->removeClass($class1);
			$this->addClass($class2);
		} else {
			$this->addClass($class1);
			$this->removeClass($class2);
		}
	}

	public function clearClasses() {
		$this->classList->reset();
		return $this;
	}

	public function setBaseCss($classes) {
		if (!$this->css) $this->css = [];

		$enabled = null;
		$disabled = null;

		if (is_string($classes)) $enabled = $classes;
		else {
			if (isset($classes['enabled'])) $enabled = $classes['enabled'];
			if (isset($classes['disabled'])) $disabled = $classes['disabled'];
		}

		if ($enabled !== null) {
			$this->removeClass( $this->getEnabledClass() );
			$this->removeClass( $this->getDisabledClass() );
			$this->css['enabled'] = $enabled;
		}

		if ($disabled !== null) {
			$this->removeClass( $this->getDisabledClass() );
			$this->css['disabled'] = $disabled;
		}

		$this->addClass( $this->getBaseCss() );

		return $this;
	}

	public function getEnabledClass() {
		if ($this->css) {
			if ($this->css['enabled']) return $this->css['enabled'];
			else return '';
		}
		return 'lx-' . $this->type;
	}

	public function getDisabledClass() {
		if ($this->lxIsSet(['css', 'disabled'])) return $this->css['disabled'];
		return 'lx-' . $this->type . '-disabled';
	}

	public function getBaseCss() {
		return $this->disabled()
			? $this->getDisabledClass()
			: $this->getEnabledClass();
	}

	public function opacity($opacity = null) {
		if ($opacity == null) return $this->style['opacity'];

		$this->style['opacity'] = $opacity;
		return $this;
	}

	public function fill($color) {
		$this->style['background-color'] = $color;
		return $this;
	}

	public function overflow($val) {
		$this->style['overflow'] = $val;
		if ($val == 'auto') $this->on('scroll', 'lx.checkDisplay');
		return $this;
	}

	public function picture($pic = null) {
		if ($pic === null) {
			$arr = explode('"', $this->style['backgroundImage']);
			return $arr[1];
		}

		if (!file_exists( \lx::sitePath() . '/' . $pic)) $pic = $this->imagePath($pic);
		if (!file_exists( \lx::sitePath() . '/' . $pic)) return;

		$this->style['background-image'] = 'url(\"'.$pic.'\")';
		$this->style['background-repeat'] = 'no-repeat';
		$this->style['background-size'] = '100% 100%';

		return $this;
	}

	public function border($info = []) {  // info = [width, color, style, side]
		$width = (isset($info['width']) ? $info['width'] : 1).'px';
		$color = isset($info['color']) ? $info['color'] : '#000000';
		$style = isset($info['style']) ? $info['style'] : 'solid';
		$sides = isset($info['sides']) ? $info['sides'] : 'ltrb';
		$side = [false, false, false, false];
		$sideName = ['left', 'top', 'right', 'bottom'];
		$side[0] = (strripos($sides, 'l') !== false);
		$side[1] = (strripos($sides, 't') !== false);
		$side[2] = (strripos($sides, 'r') !== false);
		$side[3] = (strripos($sides, 'b') !== false);

		if ($side[0] && $side[1] && $side[2] && $side[3]) {
			$this->style['border'] = "$width $style $color";
		} else {
			for ($i=0; $i<4; $i++) if ($side[$i]) {
				$this->style[ 'border-'.$sideName[$i] ] = "$width $style $color";
			}
		}

		return $this;
	}

	public function roundCorners($val) {
		$arr = [];
		if (is_array($val)) {
			if (!array_key_exists('side', $val) || !array_key_exists('value', $val)) {
				return $this;
			}

			$t = false; $b = false; $l = false; $r = false;

			if (array_search('tl', $val['side']) !== false) { $t = true; $l = true; $arr[] = 'top-left'; }
			if (array_search('tr', $val['side']) !== false) { $t = true; $r = true; $arr[] = 'top-right'; }
			if (array_search('bl', $val['side']) !== false) { $b = true; $l = true; $arr[] = 'bottom-left'; }
			if (array_search('br', $val['side']) !== false) { $b = true; $r = true; $arr[] = 'bottom-right'; }

			if (array_search('t', $val['side'])) { $arr[] = 'top-left'; $arr[] = 'top-right'; }
			if (array_search('b', $val['side']) !== false) { $arr[] = 'bottom-left'; $arr[] = 'bottom-right'; }
			if (array_search('l', $val['side']) !== false) { $arr[] = 'top-left'; $arr[] = 'bottom-left'; }
			if (array_search('r', $val['side']) !== false) { $arr[] = 'bottom-right'; $arr[] = 'top-right'; }

			$val = $val['value'];
		}

		if ( is_numeric($val) ) $val .= 'px';

		if (empty($arr)) {
			$this->style['border-radius'] = $val;
		}

		foreach ($arr as $key => $value) {
			$this->style['border-'. $value .'-radius'] = $val;
		}

		return $this;
	}

	public function rotate($angle) {
		$this->style('transform', "rotate(".$angle."deg)");
		return $this;
	}

	public function visibility($vis = null) {
		if ($vis == null) return $this->style['visibility'];
		$this->style['visibility'] = $vis;
		return $this;
	}

	public function show() {
		$this->style['visibility'] = 'inherit';
		return $this;
	}

	public function hide() {
		$this->style['visibility'] = 'hidden';
		return $this;
	}
	/* 3. Html and Css */
	//---------------------------------------------------------


	//---------------------------------------------------------
	/* 4. Geometry */
	/**
	 * Пока стратегия позиционирования не актуализирована, все параметры кэшируются в буфер
	 * стратегия актуализируется только в момент рендеринга
	 * при актуализации стратегия на основании буфера вычисляет значения геометрических параметров и записывает их в стиль
	 * */
	public function setGeomBuffer($param, $val) {
		$this->geomBuffer[$param] = $val;
		return $this;
	}

	/**
	 * Установка значения геометрическому параметру с учетом проверки родительской стратегией позиционирования
	 * todo - тут проверка как раз не нужна, возможно она нужна в setGeomBuffer
	 * */
	public function setGeomParam($param, $val) {
		// if ($this->parent) $this->parent->tryChildReposition($this, $param, $val);
		// else {

		if (Geom::directionByGeom($param) == \lx::HORIZONTAL)
			$this->geomPriorityH($param);
		else $this->geomPriorityV($param);
		$this->style[Geom::geomName($param)] = $val;

		// }
		return $this;
	}

	/**
	 * По константе получить значение геометрического параметра
	 * */
	public function getGeomParam($param) {
		switch ($param) {
			case \lx::TOP   : return $this->top();
			case \lx::HEIGHT: return $this->height();
			case \lx::BOTTOM: return $this->bottom();
			case \lx::LEFT  : return $this->left();
			case \lx::WIDTH : return $this->width();
			case \lx::RIGHT : return $this->right();
		}
	}

	/**
	 * Принимает аргументы:
	 * object.left(val)  // установит позицию в переданное значение
	 * object.left()  // вернет значение
	 * */
	public function left($val=null) {
		if ($val !== null) {
			$this->geomPriorityH(\lx::LEFT);
			return $this->setGeomBuffer(\lx::LEFT, $val);
		}

		if (isset($this->geomBuffer[\lx::LEFT])) return $this->geomBuffer[\lx::LEFT];
		if (!isset($this->geomBuffer[\lx::RIGHT]) || !isset($this->geomBuffer[\lx::WIDTH])) return 0;

		return Geom::calculate('r - w', [
			'r' => $this->geomBuffer[\lx::RIGHT],
			'w' => $this->geomBuffer[\lx::WIDTH]
		]);
	}

	public function right($val=null) {
		if ($val !== null) {
			$this->geomPriorityH(\lx::RIGHT);
			return $this->setGeomBuffer(\lx::RIGHT, $val);
		}

		if (isset($this->geomBuffer[\lx::RIGHT])) return $this->geomBuffer[\lx::RIGHT];
		if (!$this->parent) return false;
		if (!isset($this->geomBuffer[\lx::LEFT]) || !isset($this->geomBuffer[\lx::WIDTH])) return 0;

		return Geom::calculate('p - l - w', [
			'p' => $this->parent->geomBuffer[\lx::WIDTH],
			'l' => $this->geomBuffer[\lx::LEFT],
			'w' => $this->geomBuffer[\lx::WIDTH]
		]);
	}

	public function top($val=null) {
		if ($val !== null) {
			$this->geomPriorityV(\lx::TOP);
			return $this->setGeomBuffer(\lx::TOP, $val);
		}

		if (isset($this->geomBuffer[\lx::TOP])) return $this->geomBuffer[\lx::TOP];
		if (!isset($this->geomBuffer[\lx::BOTTOM]) || !isset($this->geomBuffer[\lx::HEIGHT])) return 0;

		return Geom::calculate('b - h', [
			'b' => $this->geomBuffer[\lx::BOTTOM],
			'h' => $this->geomBuffer[\lx::HEIGHT]
		]);
	}

	public function bottom($val=null) {
		if ($val !== null) {
			$this->geomPriorityV(\lx::BOTTOM);
			return $this->setGeomBuffer(\lx::BOTTOM, $val);
		}

		if (isset($this->geomBuffer[\lx::BOTTOM])) return $this->geomBuffer[\lx::BOTTOM];
		if (!$this->parent) return false;
		if (!isset($this->geomBuffer[\lx::TOP]) || !isset($this->geomBuffer[\lx::HEIGHT])) return 0;

		return Geom::calculate('p - t - h', [
			'p' => $this->parent->geomBuffer[\lx::HEIGHT],
			't' => $this->geomBuffer[\lx::TOP],
			'h' => $this->geomBuffer[\lx::HEIGHT]
		]);
	}

	public function width($val=null) {
		if ($val !== null) {
			$this->geomPriorityH(\lx::WIDTH);
			return $this->setGeomBuffer(\lx::WIDTH, $val);
		}

		if (isset($this->geomBuffer[\lx::WIDTH])) return $this->geomBuffer[\lx::WIDTH];

		if (!isset($this->geomBuffer[\lx::LEFT]) || !isset($this->geomBuffer[\lx::RIGHT])) return 0;
		return Geom::calculate('r - l', [
			'r' => $this->geomBuffer[\lx::RIGHT],
			'l' => $this->geomBuffer[\lx::LEFT]
		]);
	}

	public function height($val=null) {
		if ($val !== null) {
			$this->geomPriorityV(\lx::HEIGHT);
			return $this->setGeomBuffer(\lx::HEIGHT, $val);
		}

		if (isset($this->geomBuffer[\lx::HEIGHT])) return $this->geomBuffer[\lx::HEIGHT];
		if (!isset($this->geomBuffer[\lx::TOP]) || !isset($this->geomBuffer[\lx::BOTTOM])) return 0;

		return Geom::calculate('b - t', [
			'b' => $this->geomBuffer[\lx::BOTTOM],
			't' => $this->geomBuffer[\lx::TOP]
		]);
	}

	//todo для остальных предусмотреть, вообще до ума довести
	public function heightIsSet() {
		if (isset($this->geomBuffer[\lx::HEIGHT])) return true;
		if (isset($this->style['height'])) return true;
		if (isset($this->geomBuffer[\lx::TOP]) && isset($this->geomBuffer[\lx::BOTTOM])) return true;
		if (isset($this->style['top']) && isset($this->style['bottom'])) return true;
		if ($this->inGrid && $this->inGrid[\lx::HEIGHT]) return true;

		return false;
	}

	public function coords($left, $top) {
		$this->left( $left );
		$this->top( $top );
		return $this;
	}

	public function size($width, $height) {
		$this->width( $width );
		$this->height( $height );
		return $this;
	}

	public function copyGeom($el) {
		$pH = $el->geomPriorityH();
		$pV = $el->geomPriorityV();
		$this->setGeomBuffer($pH[1], $el->geomBuffer[$pH[1]]);
		$this->setGeomBuffer($pH[0], $el->geomBuffer[$pH[0]]);
		$this->setGeomBuffer($pV[1], $el->geomBuffer[$pV[1]]);
		$this->setGeomBuffer($pV[0], $el->geomBuffer[$pV[0]]);
		if ($el->lxIsSet('geom'))
			$this->geom = (new \ArrayObject($el->geom))->getArrayCopy();
		return $this;
	}

	public function geomPriorityH($val=null, $val2=null) {
		if ($val === null) {
			if ($this->lxIsSet(['geom', 'bpg']))
				return $this->geom['bpg'];
			return [\lx::LEFT, \lx::WIDTH];
		}

		if ($val2 !== null) {
			if (!$this->geom) $this->geom = [];
			$this->geom['bpg'] = [$val, $val2];
			if (array_search(\lx::LEFT, $this->geom['bpg']) === false) unset($this->geomBuffer[\lx::LEFT]);
			else if (array_search(\lx::WIDTH, $this->geom['bpg']) === false) unset($this->geomBuffer[\lx::WIDTH]);
			else if (array_search(\lx::RIGHT, $this->geom['bpg']) === false) unset($this->geomBuffer[\lx::RIGHT]);
			return $this;
		}

		if (!$this->lxIsSet(['geom', 'bpg']))
			$this->setLx(['geom', 'bpg'], [\lx::LEFT, \lx::WIDTH]);

		if ($this->geom['bpg'][0] === $val) return $this;

		if ($this->geom['bpg'][1] !== $val) switch ($this->geom['bpg'][1]) {
			case \lx::LEFT:   unset($this->geomBuffer[\lx::LEFT]); break;
			case \lx::WIDTH: unset($this->geomBuffer[\lx::WIDTH]); break;
			case \lx::RIGHT:  unset($this->geomBuffer[\lx::RIGHT]); break;
		}

		$this->geom['bpg'][1] = $this->geom['bpg'][0];
		$this->geom['bpg'][0] = $val;

		if ($this->geom['bpg'][0] == \lx::LEFT && $this->geom['bpg'][1] == \lx::WIDTH)
			unset($this->geom['bpg']);

		return $this;
	}

	public function geomPriorityV($val=null, $val2=null) {
		if ($val === null) {
			if ($this->lxIsSet(['geom', 'bpv']))
				return $this->geom['bpv'];
			return [\lx::TOP, \lx::HEIGHT];
		}

		if ($val2 !== null) {
			if (!$this->geom) $this->geom = [];
			$this->geom['bpv'] = [$val, $val2];
			if (array_search(\lx::TOP, $this->geom['bpv']) === false) unset($this->geomBuffer[\lx::TOP]);
			else if (array_search(\lx::HEIGHT, $this->geom['bpv']) === false) unset($this->geomBuffer[\lx::HEIGHT]);
			else if (array_search(\lx::BOTTOM, $this->geom['bpv']) === false) unset($this->geomBuffer[\lx::BOTTOM]);
			return $this;
		}

		if (!$this->lxIsSet(['geom', 'bpv']))
			$this->setLx(['geom', 'bpv'], [\lx::TOP, \lx::HEIGHT]);

		if ($this->geom['bpv'][0] === $val) return $this;

		if ($this->geom['bpv'][1] !== $val) switch ($this->geom['bpv'][1]) {
			case \lx::TOP:    unset($this->geomBuffer[\lx::TOP]);    break;
			case \lx::HEIGHT: unset($this->geomBuffer[\lx::HEIGHT]); break;
			case \lx::BOTTOM: unset($this->geomBuffer[\lx::BOTTOM]); break;
		}

		$this->geom['bpv'][1] = $this->geom['bpv'][0];
		$this->geom['bpv'][0] = $val;

		if ($this->geom['bpv'][0] == \lx::TOP && $this->geom['bpv'][1] == \lx::HEIGHT)
			unset($this->geom['bpv']);

		return $this;
	}
	/* 4. Geometry */
	//---------------------------------------------------------


	//---------------------------------------------------------
	/* 5. Environment managment */
	public function setParent($config=null) {
		$this->dropParent();

		if ($config === null) return null;
		
		$parent = null;
		$next = null;
		if ($config instanceof Box) $parent = $config;
		else {
			$config = DataObject::create($config);

			if ($config->isNull('parent')) return null;

			if ($config->before && $config->before->parent) {
				$parent = $config->before->parent;
				$next = $config->before;
			} else if ($config->after && $config->after->parent) {
				$parent = $config->after->parent;
				$next = $config->after->nextSibling();
			} else {
				$parent = $config->parent ? $config->parent : Renderer::active()->getAutoParent();
				if (!$parent) return null;
				if ($config->index)
					$next = array_key_exists($this->key, $parent->children)
						? $parent->children[$this->key][$config->index]
						: $parent->_children->at($config->index);
			}
		}

		if ($next) $config->nextSibling = $next;
		$parent->addChild($this, $config);
		return $parent;
	}

	public function dropParent() {
		if ($this->parent) $this->parent->del($this);
		return $this;
	}

	public function after($el) {
		return $this->setParent(['after' => $el]);
	}

	public function before($el) {
		return $this->setParent(['before' => $el]);
	}

	public function del() {
		if ($this->parent)
			$this->parent->del($this);
	}

	public function setField($name/*todo ,$func*/) {
		$this->_field = $name;
	}
	/* 5. Environment managment */
	//---------------------------------------------------------


	//---------------------------------------------------------
	/* 6. Environment navigation */
	public function getSibling($shift) {
		$parent = $this->parent;
		if (!$parent) return null;
		$index = $parent->_children->indexOf($this);
		return $parent->_children->at($index + $shift);
	}

	public function nextSibling() {
		return $this->getSibling(1);
	}

	public function prevSibling() {
		return $this->getSibling(-1);
	}

	/**
	 * Поиск первого ближайшего предка, удовлетворяющего условию из переданной конфигурации:
	 * 1. is - точное соответствие переданному конструктору
	 * 2. hasProperties - имеет свойство(ва), при передаче значений проверяется их соответствие
	 * 3. checkMethods - имеет метод(ы), проверяются возвращаемые ими значения
	 * 4. instance - соответствие инстансу (отличие от 1 - может быть наследником инстанса)
	 * */
	public function ancestor($info) {
		$p = $this->parent;
		while ($p) {
			if (isset($info['is'])) {
				$instances = (array)$info['is'];
				foreach ($instances as $instance) {
					if ($p->type == $instance)
						return $p;
				}
			}

			if (isset($info['hasProperties'])) {
				$prop = $info['hasProperties'];
				if (is_array($prop)) {
					$match = true;
					foreach ($prop as $name => $val) {
						if (
							(is_numeric($name) && !property_exists($p, $val))
							||
							(!property_exists($p, $name) || $p->$name != $val)
						) {
							$match = false;
							break;
						}
					}
					if ($match) return $p;
				} else if (property_exists($p, $prop)) return $p;
			}

			if (isset($info['checkMethods'])) {
				$match = true;
				foreach ($info['checkMethods'] as $name => $value) {
					if (!method_exists($p, $name) || $p->$name() != $value) {
						$match = false;
						break;
					}
				}
				if ($match) return $p;
			}

			if (isset($info['instance']) && $p instanceof $info['instance']) return $p;
			$p = $p->parent;
		}
		return null;
	}

	public function parentBlock() {
		if (!$this->parent) return $this;
		return Renderer::active()->getCurrentBlock();
	}

	public function neighbor($key) {
		$parent = $this->parent;
		while ($parent) {
			$el = $parent->get($key);
			if ($el) return $el;
			$parent = $parent->parent;
		}
		return null;
	}
	/* 6. Environment navigation */
	//---------------------------------------------------------


	//---------------------------------------------------------
	/* 7. Events */
	/**
	 * Навешивает функцию на обработчик события
	 * $handler - строка - либо имя функции, либо непосредственно код, тогда строка должна начинаться с '()=> '
	 * */
	public function on($eventName, $handler) {
		if (!$this->handlers) $this->handlers = [];
		if (!array_key_exists($eventName, $this->handlers)) $this->handlers[$eventName] = [];

		/*
		todo
		надо бы сделать механизм, при котором можно инициировать кэширование кода функции, написанного здесь - в 
		лист функций уровня диалога
		*/

		$this->handlers[$eventName][] = JsCompiler::compileCodeInString($handler);
		return $this;
	}

	public function move($config = null) {
		$this->on('mousedown', 'lx.move');

		$config = DataObject::create($config);
		$this->moveParams = [
			'xMove'        => $config->getFirstDefined( 'xMove'       , true  ),
			'yMove'        => $config->getFirstDefined( 'xMove'       , true  ),
			'parentMove'   => $config->getFirstDefined( 'parentMove'  , false ),
			'parentResize' => $config->getFirstDefined( 'parentResize', false ),
			'xLimit'       => $config->getFirstDefined( 'xLimit'      , true  ),
			'yLimit'       => $config->getFirstDefined( 'yLimit'      , true  ),
			'moveStep'     => $config->getFirstDefined( 'moveStep'    , 1     ),
		];

		return $this;
	}

	public function click($handler) {
		$this->on('click', $handler);
		return $this;
	}

	public function blur($handler) {
		$this->on('blur', $handler);
		return $this;
	}

	public function display($handler) {
		$this->on('display', $handler);
		return $this;
	}

	public function displayIn($handler) {
		$this->on('displayin', $handler);
		return $this;
	}

	public function displayOut($handler) {
		$this->on('displayout', $handler);
		return $this;
	}

	public function displayOnce($handler) {
		$this->onload('.displayOnce', $handler);
		return $this;
	}

	public function copyEvents($el) {
		if (!$el->handlers) return $this;
		$this->handlers = $el->handlers;
		return $this;
	}

	/**
	 * Просто делегирование выполнения метода на JS
	 * сущности виджетов представляются путями '=path/to/elem/elemKey[index]'
	 * парсится lx.htmlLoader.unpackProperties()
	 * */
	public function onload($handler, $args=null) {
		$handler = JsCompiler::compileCodeInString($handler);
		if (is_array($args)) {
			foreach ($args as &$item) {
				if ($item instanceof Rect) $item = '=' . $item->path() . '/' . $item->fullKey();
			}
			unset($item);
		}
		if (!$this->js) $this->js = [];
		$this->js[] = $args ? [$handler, $args] : $handler;
		return $this;
	}

	public function onpostunpack($handler) {
		switch (\lx::getSetting('unpackType')) {
			case \lx::POSTUNPACK_TYPE_IMMEDIATLY:
				$this->onload($handler);
				break;
			case \lx::POSTUNPACK_TYPE_FIRST_DISPLAY:
				$this->displayOnce($handler);
				break;
			case \lx::POSTUNPACK_TYPE_ALL_DISPLAY:
				$this->displayIn($handler);
				break;
		}
	}
	/* 7. Events */
	//---------------------------------------------------------


	//---------------------------------------------------------
	/* 8. Debugging */
	/* 8. Debugging */
	//---------------------------------------------------------


	/* 9. Render */
	//---------------------------------------------------------
	public function getModule() {
		return Renderer::active()->getModule();
	}

	protected function addToPostBuild($config, $fields=null) {
		$config = DataObject::create($config);
		$info = [];

		if ($fields === null) $fields = array_keys($config->getProperties());

		foreach ($fields as $field) {
			if ($config->hasDynamicProperty($field)) {
				$info[$field] = $config->$field;
			}
		}

		if (!empty($info)) $this->__postBuild = $info;
	}

	protected function removeFromPostBuild($fields) {
		if (!$this->__postBuild) return;

		$fields = (array)$fields;
		foreach ($fields as $field) {
			$this->__postBuild->extract($field);
		}

		if (!$this->__postBuild->hasProperties())
			$this->extract('__postBuild');
	}

	public function getLx() {
		$result = [];

		foreach ($this->_prop as $key => $value) {
			if ($key == 'geom' && $this->located) {
				if ($value != []) {
					$temp = [];
					$temp[] = isset($value['bpg']) ? $value['bpg'][0] . ',' . $value['bpg'][1] : '';
					$temp[] = isset($value['bpv']) ? $value['bpv'][0] . ',' . $value['bpv'][1] : '';
					$result['geom'] = implode('|', $temp);
				}
				continue;
			}

			$result[$key] = $value;
		}

		return $result;
	}

	public function beforeRender() {
	}

	public function whileRender($block) {
	}

	/**
	 * Рендеринг элемента в контексте конкретного блока
	 * */
	public function renderInBlock($block) {
		$this->beforeRender();

		$this->attrs['class'] = $this->classList->join(' ');
		$config = [
			'attrs' => $this->attrs,
			'style' => $this->style,
			'html' => $this->html
		];

		$block->renderWidgetBegin($this, $config);
		$this->whileRender($block);
		$block->renderWidgetEnd($this->tag);
	}
	/* 9. Render */
	//---------------------------------------------------------


	//---------------------------------------------------------
	/* 10. Ajax */
	/**
	 * Метод возвращает массив имен методов, к которым можно напрямую обращаться по ajax
	 * */
	protected static function ajaxMethods() {
		return [];
	}

	/**
	 * Метод возвращает имя метода, который нужно выполнить по URL. Можно переопределить у потомков, добавить проверки и т.п.
	 * */
	protected static function ajaxRoute($url) {
		if (in_array($url, static::ajaxMethods())) {
			return $url;
		}

		return false;
	}

	/**
	 * Метод формирования ajax-ответа для виджета. Управлять роутом ajax-запросов виджетов можно переопределяя метод [[ajaxRoute()]]
	 * */
	public static function ajax($url, $params = []) {
		$methodName = static::ajaxRoute($url);

		if ($methodName === false) {
			throw new \Exception("Error while widget responsing", 400);
		}

		return \call_user_func_array([static::class, $methodName], $params);
	}
	/* 10. Ajax */
	//---------------------------------------------------------
}
