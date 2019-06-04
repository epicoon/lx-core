<?php

namespace lx;

/**
 * @group {i18n:widgets}
 * */
class Box extends Rect {
	public
		$_children = null,  // линейный массив потомков, для воссоздания потока
		$children = [],     // аналог children в js
		$positioningStrategy;

	public function __construct($config = []) {
		$config = DataObject::create($config);
		parent::__construct($config);

		$this->_children = new Vector();

		$posSt = $config->positioning
			? $config->positioning
			: PositioningStrategy::class;
		$this->positioningStrategy = new $posSt($this, $config);

		if ($config->text) $this->text($config->text);

		if ($config->image) {
			new Image([
				'parent' => $this,
				'key' => 'image',
				'filename' => $config->image
			]);
		}

		if ($config->stream) $this->stream($config->stream === true ? [] : $config->stream);
		if ($config->grid) $this->grid($config->grid === true ? [] : $config->grid);
		if ($config->slot) $this->slot($config->slot === true ? [] : $config->slot);
	}

	public function begin() {
		if ($this->isAutoParent()) return false;
		Renderer::active()->autoParent = $this;
		return $this;
	}

	public function isAutoParent() {
		return (Renderer::active()->autoParent === $this);
	}

	public function end() {
		if (!$this->isAutoParent()) return false;
		Renderer::active()->popAutoParent();
		return $this;
	}

	//=========================================================================================================================
	/* 1. Content managment */
	/**
	 * Метод, используемый новым элементом для регистрации в родителе
	 * */
	public function addChild($elem, $config = []) {
		$config = DataObject::create($config);
		$config = $this->modifyNewChildConfig($config);

		$elem->parent = $this;

		$this->childrenPush($elem, $config->nextSibling);

		$this->positioningStrategy->allocate($elem, $config);
	}

	/**
	 * Предобработка конфига добавляемого элемента
	 * */
	protected function modifyNewChildConfig($config) {
		return $config;
	}

	/**
	 * Непосредственно регистрация нового эемента в структурах родителя
	 * */
	public function childrenPush($el, $next=null) {
		if (isset($this->children[$el->key])) {
			if (!($this->children[$el->key] instanceof Vector)) {
				$this->children[$el->key]->index = 0;
				$this->children[$el->key] = new Vector([$this->children[$el->key]]);
			}
			if ($next && $el->key == $next->key) {
				$el->index = $next->index;
				$this->children[$el->key]->insert($el->index, $el);
				for ($i=$el->index+1,$l=$this->children[$el->key]->len; $i<$l; $i++) {
					$this->children[$el->key][$i]->index = $i;
				}
			} else {
				$el->index = $this->children[$el->key]->len;
				$this->children[$el->key]->push($el);
			}
		} else $this->children[$el->key] = $el;

		$index = -1;
		if ($next) $index = $this->_children->indexOf($next);
		if ($index == -1) $this->_children->push($el);
		else $this->_children->insert($index, $el);
	}

	/**
	 * Поддержка массовой вставки
	 * */
	public function insert($c, $next) {
		//todo неоптимизированно, но вроде тут и не надо - это в основном для динамики
		$t = $this;
		$c->each(function($a) use ($t, $next) {
			$t->addChild($a, ['nextSibling' => $next]);
		});
		return $this;
	}

	/*
	 * Удаление элементов в вариантах:
	 * 1. Без аргументов - удаление элемента, на котором метод вызван
	 * 2. Аргумент el - элемент - если такой есть в элементе, на котом вызван метод, он будет удален
	 * 3. Аргумент el - ключ (единственный аргумент) - удаляется элемент по ключу, если по ключу - массив,
	 *    то удаляются все элементы из этого массива
	 * 4. Аргументы el (ключ) + index - имеет смысл, если по ключу - массив, удаляется из массива 
	 * элемент с индексом index в массиве
	 * 5. Аргументы el (ключ) + index + amount - как 4, но удаляется amount элементов начиная с index
	 * */
	public function del($el=null, $index=null, $amount=1) {
		//todo - не надо вроде на этой стороне, в любом случае не горит

		// // ситуация 1 - элемент не передан, надо удалить тот, на котором вызван метод

		// if ($el === null) {
		// 	// если элемент - пустышка, нечего удалять
		// 	if ($el->key === null) return 0;

		// 	$p = $this->parent;
		// 	// если нет родителя - это модуль
		// 	if (!$p) return 0;
		// 	return $p->del($this);
		// }

		// // ситуация 2 - el - объект
		// if (!is_string($el)) return $this->del($el->key, $el->index, 1);

		// // $el - ключ
		// if (!isset($this->children[$el])) return 0;

		// // children[$el] - не массив, элемент просто удаляется
		// if (!($this->children[$el] instanceof Vector)) {
		// 	$pre = $this->children[$el]->prevSibling();

		// 	$elem = $this->children[$el];
		// 	if ($elem->alignId && $this->alignInfo) $this->alignInfo->remove($elem);
		// 	$this->_children->remove($elem);
		// 	unset($this->children[$el]);

		// //??? todo - для удаления реализовать метод, через GeomCalculator
		// // $this->actualizeStream($pre);
		// 	return 1;
		// }

		// // children[el] - массив
		// if ($index === null) {
		// 	$index = 0;
		// 	$amount = $this->children[$el]->len;
		// } else if ($index >= $this->children[$el]->len) {
		// 	return 0;
		// } else if ($index + $amount > $this->children[$el]->len) {
		// 	$amount = $this->children[$el]->len - $index;
		// }

		// $pre = $this->children[$el]->at($index)->prevSibling();
		// for ($i=$index,$l=$index+$amount; $i<$l; $i++) {
		// 	$elem = $this->children[$el]->at($i);
		// 	if ($elem->alignId && $this->alignInfo) $this->alignInfo->remove($elem);
		// 	$this->_children->remove($elem);
		// }
		// $this->children[$el]->splice($index, $amount);
		// for ($i=$index,$l=$this->children[$el]->len; $i<$l; $i++) {
		// 	$this->children[$el]->at($i)->index = $i;
		// }
		// if (!$this->children[$el]->len) {
		// 	unset($this->children[$el]);
		// } else if ($this->children[$el]->len == 1) {
		// 	$this->children[$el] = $this->children[$el]->at(0);
		// 	$this->children[$el]->index = null;
		// }

		// //??? todo - для удаления реализовать метод, через GeomCalculator
		// // $this->actualizeStream($pre);

		// return $amount;
	}

	public function add($type, $count=1, $config = []) {
		if (is_array($type)) {
			$result = [];
			foreach ($type as $args)
				$result[] =  call_user_method_array('add', $this, $args);
			return $result;
		}
		if (is_array($count)) {
			$config = $count;
			$count = 1;
		}
		$config['parent'] = $this;
		return $count == 1
			? new $type($config)
			: $type::construct($count, $config);
	}

	public function clear() {
		$this->_children = new Vector();
		$this->children = [];
		$this->positioningStrategy->reset();
	}

	public function text($text=null) {
		if ($text === null) {
			if ( !$this->contain('text') ) return '';
			return $this->children['text']->value();
		}

		if ( !$this->contain('text') ) {
			new TextBox(['parent' => $this]);
		}

		$this->children['text']->value($text);
		return $this;
	}

	public function image($filename) {
		new Image([
			'parent' => $this,
			'filename' => $filename
		]);
		return $this;
	}

	public function renderHtmlFile($fileName) {
		$path = $this->getModule()->getFilePath($fileName);
		$file = new HtmpFile($path);
		if (!$file->exists()) return;

		$text = $file->get();
		$this->renderHtml($text);
	}

	public function renderHtml($text) {
		if (!\lx::$dialog->isAjax())
			$text = preg_replace('/"/', '\"', $text);
		$text = preg_replace_callback('/<pre>\s?([\w\W]*?)<\/pre>/', function($matches) {
			$string = preg_replace('/\n/', '<br>', $matches[1]);
			return "<pre>$string</pre>";
		}, $text);
		$text = preg_replace('/\n/', '', $text);
		$text = preg_replace('/\t/', '    ', $text);
		$this->text($text);
	}

	public function tryChildReposition($elem, $param, $val) {
		return $this->positioningStrategy->tryReposition($elem, $param, $val);
	}

	public function showOnlyChild($key) {
		$this->getChildren()->each(function($a) use ($key) {
			$a->visibility($a->key == $key);
		});
	}
	/* 1. Content managment */
	//=========================================================================================================================


	//=========================================================================================================================
	/* 2. Content navigation */
	public function get($path) {
		$arr = [];
		preg_match_all('/[\w\d_\[\]]+/', $path, $arr);
		$children = $this->children;

		$l = count($arr[0]);
		foreach ($arr[0] as $i => $item) {
			$key = explode('[', $item);
			$index = (count($key) > 1) ? (int)$key[1] : null;
			$key = $key[0];

			if (!isset($children[$key])) return null;
			if ($i + 1 == $l) {
				if ($index === null) return $children[$key];
				return $children[$key]->at($index);
			}
			$children = ($index === null)
				? $children[$key]->children
				: $children[$key]->at($index)->children;
		}
	}

	/**
	 * Возвращает коллекцию, если найдено более одного элемента, либо единственный найденный элемент
	 * */
	public function find($key, $all=true) {
		$c = $this->getChildren([
			'hasProperties' => ['key' => $key],
			'all' =>  $all
		]);
		if ($c->len == 1) return $c->at(0);
		return $c;
	}

	/**
	 * Всегда возвращает коллекцию, даже для одного элемента
	 * */
	public function findAll($key, $all=true) {
		$c = $this->getChildren([
			'hasProperties' => ['key' => $key],
			'all' =>  $all
		]);
		return $c;
	}

	/**
	 * Всегда возвращает один элемент (первый наденный)
	 * */
	public function findOne($key, $all=true) {
		$c = new Collection( $this->find($key, $all) );
		if ($c->isEmpty) return null;
		return $c->at(0);
	}

	public function contain($key) {
		if (is_string($key)) return (isset($this->children[$key]));

		if (!($key instanceof Rect)) return false;

		if (!isset($this->children[$key->key])) return false;
		if ($this->children[$key->key] instanceof Vector) {
			if ($key->index === null) return false;
			return $this->children[$key->key]->at($key->index) === $key;
		}
		return $this->children[$key->key] === $key;
	}

	public function childrenCount($key=null) {
		if ($key === null) return $this->_children->len;
		if (!isset($this->children[$key])) return 0;
		if ($this->children[$key] instanceof Vector)
			return $this->children[$key]->len;
		else return 1;
	}

	public function child($num) {
		return $this->_children->at($num);
	}

	public function lastChild() {
		return $this->_children->last();
	}

	public function divideChildren($info=[]) {
		$bool = isset($info['all']) ? $info['all'] : false;
		$match = new Collection();
		$notMatch = new Collection();
		$rec = function($el) use(&$rec, $info, $bool, &$match, &$notMatch) {
			if ($el === null || !($el instanceof Box)) return;
			for ($i=0, $l=$el->childrenCount(); $i<$l; $i++) {
				$child = $el->child($i);
				if (!$child) continue;
				$matched = true;

				if (isset($info['callback'])) {
					$matched = $info['callback']($child);
				}

				if (isset($info['hasProperties'])) {
					$prop = $info['hasProperties'];
					if (is_array($prop)) {
						foreach ($prop as $name => $val) {
							if (is_numeric($name)) {
								if (!$child->hasProperty($val)) { $matched = false; break; }
							} else {
								if (!$child->testProperty($name, $val)) { $matched = false; break; }
							}
						}
					} else if (!$child->hasProperty($prop)) $matched = false;
				}

				if ($matched) $match->add($child);
				else $notMatch->add($child);
				if ($bool) $rec($child);
			}
		};
		$rec($this);
		return [
			'match' => $match,
			'notMatch' => $notMatch
		];
	}

	/**
	 * $info - может быть булевым - все дерево потомков искать (true), или только непосредственных (false)
	 * если массив - по ключу 'all' соответствующее булево значение + ключи для параметров поиска
	 * */
	public function getChildren($info=null) {
		if ($info === null) {
			$c = new Collection();
			foreach ($this->children as $child)
				$c->add($child);
			return $c;
		}

		if (is_callable($info)) {
			$info = ['callback' => $info];
		}

		if (is_bool($info)) $info = ['all' => $info];
		return $this->divideChildren($info)['match'];
	}

	public function each($func, $info=null) {
		$this->getChildren($info)->each($func);
	}
	/* 2. Content navigation */
	//=========================================================================================================================


	//=========================================================================================================================
	/* 3. PositioningStrategies */
	public function preparePositioningStrategy($strategy) {
		if ($this->positioningStrategy) {
			if ($this->positioningStrategy->className() == $strategy) return;
			$this->positioningStrategy->clear();
		}
		$this->positioningStrategy = new $strategy($this);
	}

	/**
	 * Устанавливает стратегию AlignPositioningStrategy, если еще не была установлена
	 * Добавляет в стратегию новове правило выравнивания
	 * Сбрасывает предыдущую стратегию, если была отличная от AlignPositioningStrategy
	 * */
	public function align($hor, $vert=null, $els=null) {
		$this->preparePositioningStrategy(AlignPositioningStrategy::class);
		$this->positioningStrategy->addRule($hor, $vert, $els);
		return $this;
	}

	public function stream($config=[]) {
		$this->preparePositioningStrategy(StreamPositioningStrategy::class);
		$this->positioningStrategy->init($config);
		return $this;
	}

	public function streamProportional($config=[]) {
		$config['sizeBehavior'] = StreamPositioningStrategy::SIZE_BEHAVIOR_PROPORTIONAL;
		return $this->stream($config);
	}

	public function streamDirection() {
		if (!($this->positioningStrategy instanceof StreamPositioningStrategy)) return false;
		return $this->positioningStrategy->direction;
	}

	public function grid($config=[]) {
		$this->preparePositioningStrategy(GridPositioningStrategy::class);
		$this->positioningStrategy->init($config);
		return $this;
	}

	public function gridProportional($config=[]) {
		$config['sizeBehavior'] = GridPositioningStrategy::SIZE_BEHAVIOR_PROPORTIONAL;
		return $this->grid($config);
	}

	public function slot($config=[]) {
		$this->preparePositioningStrategy(SlotPositioningStrategy::class);
		$this->positioningStrategy->init($config);
		return $this;
	}

	public function setIndents($config) {
		$this->positioningStrategy->setIndents($config);
		return $this;
	}
	/* 3. PositioningStrategies */
	//=========================================================================================================================


	/* 4. Render */
	//=========================================================================================================================
	public function setBlock($config) {
		if (is_string($config)) {
			$config = ['path' => $config];
		}
		if (!isset($config['renderParams']))
			$config['renderParams'] = [];
		if (!isset($config['clientParams']))
			$config['clientParams'] = [];

		// inner block
		$this->blockInfo = [$config['path'], $config['renderParams'], $config['clientParams']];

		// флаг, который будет и на js
		$this->isBlock = true;
		return $this;
	}

	/**
	 * @var $module lx\Module
	 * */
	public function setModule($module, $params = []) {
		$module->addParams($params);

		$builder = new ModuleBuilder($module);
		if ($builder->build()) {
			$this->moduleData = $builder->uniqKey();
		}
	}

	public function beforeRender() {
		$this->positioningStrategy->actualize();
		// Простую стратегию нет смысла паковать
		if ($this->positioningStrategy->className() == PositioningStrategy::class) return;
		$this->__ps = $this->positioningStrategy->pack();
	}

	public function whileRender($block) {
		$this->_children->each(function($a) use ($block) { $a->renderInBlock($block); });
	}
	/* 4. Render */
	//=========================================================================================================================
}
