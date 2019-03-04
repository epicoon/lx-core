<?php

namespace lx;

class AlignPositioningStrategy extends PositioningStrategy {
	public function __construct($owner, $config=[]) {
		parent::__construct($owner, $config);

		$this->counter = 0;
		$this->defaultRule = null;  // Правило по умолчанию
		$this->rules = [];        // массив правил
		$this->keysToRules = [];  // массив ключей элементов, указывающих на ключи соответствующих им правил

		if ($config) $this->addRule($config);
	}

	public function pack() {
		$str = parent::pack() . ';c:' . $this->counter;

		if ($this->defaultRule) {
			$str .= ';dr:' . $this->defaultRule->pack();
		}

		$v = new Vector();
		foreach ($this->rules as $rule) $v->push( $rule->pack() );
		if (!$v->isEmpty) $str .= ';r:' . $v->join('|');

		if (!empty($this->keysToRules)) {
			$v = new Vector();
			foreach ($this->keysToRules as $key => $id) {
				$v->push( $key . '|' . $id );
			}
			$str .= ';kr:' . $v->join(',');
		}

		return $str;
	}

	public function addRule($config, $vert=null, $els=null) {
		if ($vert !== null) {
			$this->addRule([
				'horizontal' => $config,
				'vertical' => $vert,
				'subject' => $els
			]);
			return;
		}

		$config = DataObject::create($config);

		// Если субъекты не переданы, значит задается правило по умолчанию
		if (!$config->subject) {
			$this->defaultRule = new AlignRuleDefault($this, $config);
			return $this->defaultRule;
		}

		// Если пришедшие ключи или элементы уже фигурируют в правилах - почистим правила
		$subject = new Vector($config->subject);
		$t = $this;

		$subject->each(function($a) use ($t) {
			$rule = null;
			if (is_string($a)) {
				if (array_key_exists($a, $t->keysToRules)) $rule = $t->rules[$t->keysToRules[$a]];
			} else if ($a->ruleId)
				$rule = $t->rules[$a->ruleId];
			if ($rule) $rule->remove($a);
		});

		// Создаем правило
		$ruleId = $this->genId();
		$this->rules[$ruleId] = new AlignRule($this, $config, $ruleId);
		return $this->rules[$ruleId];
	}

	public function actualize() {
		$format = $this->getFormatText($this->innerFormat);
		$t = $this;
		$this->owner->getChildren()->each(function($a) use ($t, $format) {
			// Для элементов, которые не относятся ни к одному правилу - чтобы стандартная логика работала
			if (!$t->defaultRule && !$a->hasProperty('ruleId')) {
				foreach ($a->geomBuffer as $key => $value) {
					if (is_numeric($value)) $value .= $format;
					$a->setGeomParam($key, $value);
				}
			// Остальным применить их размеры
			} else {
				$w = $t->getGeomParam($a, \lx::WIDTH, $format);
				$h = $t->getGeomParam($a, \lx::HEIGHT, $format);
				$a->setGeomParam(\lx::WIDTH, $w);
				$a->setGeomParam(\lx::HEIGHT, $h);
			}
		});

		$this->needJsActualize = true;
		/*
		todo - актуализация сложная ппц, делегирую сразу на js, но для оптимизации (посчитать то, что можно на сервере) на досуге попробовать сделать актуализацию
		- учесть, что дефолтный формат процент в конструкторе ставится, но координаты по факту всегда считаются в пикселях (оно надо так вообще? учитывая js)
		*/
		// // Актуализация дефолтного правила, если есть
		// if ($this->defaultRule) {
		// 	if (!$this->defaultRule->actualize())
		// 		$this->needJsActualize = true;
		// }

		// // Актуализация остальных правил
		// if (!$this->needJsActualize) {
		// 	foreach ($this->rules as $rule) {
		// 		if (!$rule->actualize()) {
		// 			$this->needJsActualize = true;
		// 			break;
		// 		}
		// 	}
		// }
	}

	public function allocate($elem, $config=[]) {
		$ruleId = null;
		if (array_key_exists($elem->key, $this->keysToRules)) $ruleId = $this->keysToRules[$elem->key];

		if (!$ruleId && !$this->defaultRule) {
			parent::allocate($elem, $config);
			return;
		}

		$geom = $this->geomFromConfig($config);
		$elem->setGeomBuffer(\lx::WIDTH, isset($geom['w']) ? $geom['w'] : 0);
		$elem->setGeomBuffer(\lx::HEIGHT, isset($geom['h']) ? $geom['h'] : 0);

		$elem->ruleId = $ruleId;
	}

	public function rule($id=null) {
		if (!$id) return $this->defaultRule;
		return $this->rules[$id];
	}

	public function reset() {
		$this->owner->getChildren()->each(function($a) { $a->extract('ruleId'); });
		$this->counter = 0;
		$this->rules = [];
		$this->keysToRules = [];
	}

	public function tryReposition($elem, $param, $val) {
		// можно менять размеры, но нельзя менять положение
		if ($param == \lx::LEFT || $param == \lx::RIGHT || $param == \lx::TOP || $param == \lx::BOTTOM) return false;

		$elem->setGeomBuffer($param, $val);
		return true;
	}

	private function genId() {
		return 'r' . Math::decChangeNotation($this->counter++, 62);
	}
}


abstract class AlignRuleAbstract {
	protected
		$owner,
		$h,
		$v,
		$dir,
		$indents;

	public function __construct($owner, $config) {
		$config = DataObject::create($config);
		$this->owner = $owner;

		$this->h = $config->horizontal;
		$this->v = $config->vertical;
		$this->dir = $config->direction ? $config->direction : \lx::HORIZONTAL;

		$this->indents = IndentData::createOrNull($config);
	}

	abstract public function getElements();

	public function pack() {
		$str = "{$this->dir}+{$this->h}+{$this->v}";
		return $str;
	}

	public function getIndents() {
		if ($this->indents) return $this->indents->get();
		return $this->owner->getIndents();
	}
}

class AlignRuleDefault extends AlignRuleAbstract {
	public function getElements() {
		// Все потомки, у которых нет динамического свойства ruleId
		return $this->owner->owner->getChildren(function($a) {
			return !$a->hasProperty('ruleId');
		});
	}

	public function pack() {
		$str = parent::pack();
		if ($this->indents) $str .= '+' . $this->indents->pack('=');
		return $str;
	}
}

class AlignRule extends AlignRuleAbstract {
	public function __construct($owner, $config, $id) {
		parent::__construct($owner, $config);
		$config = DataObject::create($config);

		$this->id = $id;
		$this->list = new Vector();

		$t = $this;
		$subject = new Vector($config->subject);
		$subject->each(function($a) use ($t) {
			$t->add($a);
		});
	}

	public function pack() {
		$str = parent::pack() . "+{$this->id}";

		$v = new Vector();
		$this->list->each(function($a) use ($v) {
			if (is_string($a)) $v->push($a);
			else $v->push('='.$a->fullKey());
		});
		$str .= '+' . $v->join(',');

		if ($this->indents) $str .= '+' . $this->indents->pack('=');

		return $str;
	}

	public function add($elem) {
		$this->list->push($elem);
		if (is_string($elem)) {
			$id = $this->id;
			$this->owner->keysToRules[$elem] = $id;
			$elems = $this->owner->owner->get($elem);
			(new Collection($elems))->each(function($b) use ($id) {
				$b->ruleId = $id;
			});
		} else $elem->ruleId = $this->id;
	}

	public function remove($el) {
		if (is_array($el)) {
			foreach ($el as $a) $this->remove($a);
			return;
		}

		if (is_string($el)) {
			unset($this->owner->keysToRules[$el]);
			$this->owner->owner->get($el)->each(function($a) {
				$a->extract('ruleId');
			});
		} else $el->extract('ruleId');

		$this->list->remove($el);
		if ($this->list->isEmpty) unset($this->owner->rules[$this->id]);
	}

	public function getElements() {
		$result = new Collection();
		$this->list->each(function($elem) use (&$result) {
			if (is_string($elem))
				$this->owner->owner->get($elem)->each(function($a) use (&$result) {
					if ($a->ruleId == $this->id) $result->add($a);
				});
			else $result->add($elem);
		});
		return $result;
	}
}
