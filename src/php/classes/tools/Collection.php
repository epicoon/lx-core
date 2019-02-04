<?php

namespace lx;

/**
 *	__construct()
 *	__get($prop)
 *	_len()
 *	_isEmpty()
 *	clear()
 *	at($num)
 *	to($k)
 *	set($i, $val)
 *	first()
 *	last()
 *	current()
 *	next()
 *	pre()
 *	toCopy()
 *	add()
 *	addCopy()
 *	addList($list, $func=null)
 *	flat($deep=0)
 *	construct()
 *	remove($el)
 *	sub($k, $amt=1)
 *	call()
 *	callRepeat($funcName, $args)
 *	each($func)
 *	eachRevert($func)
 *	getEach($attr, $args=null)
 *	mix($c, $func, $repeat)
 *	select()
 *	stop()
 * */
class Collection {
	public
		$actPart = null,
		$actI = null,
		$actPartI = null,

		$copy = false,
		$elements,
		$map,

		$stopFlag = false,
		$repeat = false;

	public function __construct() {
		$this->elements = new Vector();
		$this->map = new Vector();

		$num = func_num_args();
		if (!$num) return;

		call_user_func_array(array($this, 'add'), func_get_args());
	}

	public function __get($prop) {
		switch ($prop) {
			case 'len'    : return $this->_len();
			case 'isEmpty': return $this->_isEmpty();
		}
	}

	public function _len() {
		if ($this->copy) return $this->elements->len;
		$len = 0;
		$this->map->each(function($a) use (&$len) {
			$len += $a->len;
		});
		return $len;
	}

	public function _isEmpty() {
		if ($this->copy) return $this->elements->len == 0;
		$this->map->each(function($a) {
			if ($a->len) return false;
		});
		return true;
	}

	public function clear() {
		$this->actPart = null;
		$this->elements->reset();
		$this->map->reset();
		$this->copy = false;
	}

	public function at($num) {
		if (!$this->to($num)) return null;
		return $this->current();
	}

	/*
	 * k - может быть как индекс (если число), так и элемент (кроме числа)
	 * k - может быть null, тогда просто сбрасывается позиционирование итератора
	 * */
	public function to($k) {
		if ($k === null) {
			$this->actPart = null;
			return $this;
		}

		if (is_numeric($k)) {
			if ($k >= $this->len) return false;

			if ($this->copy) {
				$this->actPart = $this->elements;
				$this->actPartI = $k;
			} else {
				$t = $this;
				$this->map->each(function($a, $i) use (&$k, &$t) {
					if ($a->len > $k) {
						$t->actPart = $a;
						$t->actI = $i;
						$t->actPartI = $k;
					} else $k -= $a->len;
				});
			}
		} else {
			$match = false;
			$this->first();
			while (!$match && $this->current()) {
				if ($this->current() === $k) $match = true;
				else $this->next();
			}
			if (!$match) return false;
		}

		return $this;
	}

	public function set($i, $val) {
		$this->to($i);
		$this->current($val);
		return $this;
	}

	public function first(/*$value*/) {
		if ($this->copy) {
			if (!$this->elements->len) return null;
			$this->actPart = $this->elements;
			$this->actPartI = 0;
		} else {
			if (!$this->map->len || !$this->map->at(0)->len) return null;
			$this->actPart = $this->map->at(0);
			$this->actI = 0;
			$this->actPartI = 0;
		}
		if (!func_num_args())
			return $this->actPart->at($this->actPartI);
		$value = func_get_arg(0);
		$this->actPart->set($this->actPartI, $value);
		return $this;
	}

	public function last(/*$value*/) {
		if ($this->copy) {
			if (!$this->elements->len) return null;
			$this->actPart = $this->elements;
			$this->actPartI = $this->elements->len - 1;
		} else {
			if (!$this->map->len || !$this->map->at(0)->len) return null;
			$this->actI = $this->map->len - 1;
			$this->actPart = $this->map->at($this->actI);
			$this->actPartI = $this->actPart->len - 1;
		}
		if (!func_num_args())
			return $this->actPart->at($this->actPartI);
		$value = func_get_arg(0);
		$this->actPart->set($this->actPartI, $value);
		return $this;
	}

	public function current(/*$value*/) {
		if ($this->actPart === null)
			return null;
		if (!func_num_args())
			return $this->actPart->at($this->actPartI);
		$value = func_get_arg(0);
		$this->actPart->set($this->actPartI, $value);
		return $this;
	}

	public function next() {
		if ($this->actPart === null) return $this->first();

		$this->actPartI++;
		if ($this->actPart->len == $this->actPartI) {
			if ($this->copy) {
				$this->actPart = null;
				return null;
			} else {
				$this->actI++;
				if ($this->map->len == $this->actI) {
					$this->actPart = null;
					return null;
				} else {
					$this->actPartI = 0;
					$this->actPart = $this->map->at($this->actI);
				}
			}
		}
		return $this->actPart->at($this->actPartI);
	}

	public function pre() {
		if ($this->actPart === null) return $this->last();
		
		$this->actPartI--;
		if ($this->actPartI == -1) {
			if ($this->copy) {
				$this->actPart = null;
				return null;
			} else {
				$this->actI--;
				if ($this->actI == -1) {
					$this->actPart = null;
					return null;
				} else {
					$this->actPart = $this->map->at($this->actI);
					$this->actPartI = $this->actPart->len - 1;
				}
			}
		}
		return $this->actPart->at($this->actPartI);
	}

	public function toCopy() {
		if ($this->copy) return $this;
		$iter = 0;
		$t = $this;
		$this->map->each(function($a, $i) use (&$iter, $t) {
			if ($t->actPart && $i < $t->actI) $iter += $a->len;
			else if ($t->actPart && $i == $t->actI) $iter += $t->actPartI;
			$a->each(function($b) {
				$this->elements->push($b);
			});
		});
		$this->map->reset();
		$this->copy = true;
		if ($this->actPart) {
			$this->actPart = $this->elements;
			$this->actPartI = $iter;
		}
		return $this;
	}

	public function add() {
		$count = func_num_args();
		if (!$count) return;

		$args = func_get_args();
		if ($this->copy) {
			call_user_func_array(array($this, 'addCopy'), $args);
			return $this;
		}

		for ($i=0; $i<$count; $i++) {
			$arg = $args[$i];
			if ($arg === null) continue;

			if ($arg instanceof Vector) {
				$this->map->push($arg);
			} else if ($arg instanceof Collection) {
				if ($arg->copy) $this->add($arg->elements);
				else for ($j=0, $ll=$arg->map->len; $j<$ll; $j++)
					$this->add( $arg->map->at($j) );
			} else {
				if ( $this->map->len && $this->map->last()->singles ) {
					$this->map->last()->push($arg);
				} else {
					$vector = new Vector([$arg]);
					$vector->singles = true;
					$this->map->push($vector);
				}
			}
		}
		return $this;
	}

	public function addCopy() {
		$this->toCopy();
		$count = func_num_args();
		if (!$count) return $this;
		$args = func_get_args();

		for ($i=0; $i<$count; $i++) {
			$arg = $args[$i];
			if ($arg === null) continue;

			if ($arg instanceof Vector) {
				for ($j=0, $ll=$arg->len; $j<$ll; $j++)
					$this->elements->push( $arg->at($j) );
			} else if ($arg instanceof Collection) {
				$arg->first();
				while ($arg->current()) {
					$this->elements->push( $arg->current() );
					$arg->next();
				}
			} else $this->elements->push($arg);
		}

		return $this;
	}

	public function addList($list, $func=null) {
		foreach ($list as $i => $value) {
			if ($func) $func($value, $i);
			$this->add($value);
		}
		return $this;
	}

	public function flat($deep=0) {
		// изменять внутреннюю структуру содержащихся массивов можно только с копией
		$this->toCopy();
		$arr = [];
		$this->flatRec($this->elements, $arr, 0, $deep);
		$this->elements = new Vector($arr);
		return $this;
	} private function flatRec($tempArr, &$arr, $counter, $deep) {
		for ($i=0,$l=$tempArr->len; $i<$l; $i++) {
			if (($deep && ($counter+1 > $deep)) || !is_array($tempArr->at($i))) {
				$arr[] = $tempArr->at($i);
			}
			else $this->flatRec($tempArr->at($i), $arr, $counter + 1, $deep);
		}
	}

	//todo - Много на себя берет, проверить js - там тоже такого не надо
	// public static function construct() {
	// 	$arguments = func_get_args();

	// 	$constructor = $arguments[0];
	// 	$count = $arguments[1];
	// 	$configurator = [];
	// 	$pos = 2;
	// 	$args = [];

	// 	if (isset($arguments[2]['preBuild']) || isset($arguments[2]['postBuild'])) {
	// 		$configurator = $arguments[2];
	// 		$pos++;
	// 	}

	// 	for ($i=$pos, $l=count($arguments); $i<$l; $i++)
	// 		$args[] = $arguments[$i];

	// 	$result = new self();
	// 	for ($i=0; $i<$count; $i++) {
	// 		$modifArgs = $args;
	// 		if (isset($configurator['preBuild']))
	// 			$modifArgs = $configurator['preBuild']($args, $i);

	// 		$obj = new $constructor;
	// 		call_user_func_array(array($obj, '__construct'), $modifArgs);
			
	// 		if (isset($configurator['postBuild']))
	// 			$configurator['postBuild']($obj, $i);

	// 		$result->add($obj);
	// 	}

	// 	return $result;
	// }

	public function remove($el) {
		// изменять внутреннюю структуру содержащихся массивов можно только с копией
		if (!$this->copy) return false;
		$index = $this->elements->indexOf($el);
		if ($index == -1) return false;
		$this->elements->splice($index, 1);
		if ($this->actPartI >= $this->elements->len)
			$this->actPartI = $this->elements->len - 1;
		return true;
	}

	public function sub($k, $amt=1) {
		$c = new Collection();
		$this->to($k);
		for ($i=0; $i<$amt; $i++) {
			if (!$this->current()) return $c;
			$c->add($this->current()); 
			$this->next();
		}
		return $c;
	}

	public function call() {
		$count = func_num_args();
		if (!$count) return $this;
		$arguments = func_get_args();
		$funcName = $arguments[0];


		$args = [];
		for ($i=1; $i<$count; $i++)
			$args[] = $arguments[$i];

		$this->each(function($a) use ($funcName, $args) {
			if ( $a === null || !method_exists($a, $funcName) ) return;
			call_user_func_array(array(&$a, $funcName), $args);
		});

		return $this;
	}

	public function callRepeat($funcName, $args) {
		$current = 0;
		$this->each(function($a, $i) use (&$current, $funcName, $args) {
			if ($a == null || !method_exists($a, $funcName)) return;

			if (is_array($args[$current])) call_user_func_array(array(&$a, $funcName), $args[$current]);
			else call_user_func(array(&$a, $funcName), $args[$current]);
			$current++;
			if ($current == count($args)) $current = 0;
		});
		return $this;
	}

	public function each($func) {
		$this->stopFlag = false;
		$i = 0;
		$el = $this->first();
		while ($el && !$this->stopFlag) {
			$func($el, $i++, $this);
			$el = $this->next();
		}
		return $this;
	}

	public function eachRevert($func) {
		$this->stopFlag = false;
		$i = $this->len - 1;
		$el = $this->last();
		while ($el && !$this->stopFlag) {
			$func($el, $i--, $this);
			$el = $this->pre();
		}
		return $this;
	}

	public function getEach($attr, $args=null) {
		$c = new Collection();
		$this->each(function($a) use ($attr, $args, &$c) {
			if (property_exists($a, $attr)) {
				$c->add($a->$attr);
				return;
			}
			if (!method_exists($a, $attr)) return;
			if ($args === null) {
				$c->add($a->$attr());
				return;
			}
			if (is_array($args))
				$c->add(call_user_func_array([&$a, $attr], $args));
			else
				$c->add(call_user_func([&$a, $attr], $args));
		});
		return $c;
	}

	public function mix($c, $func, $repeat) {
		if ($c instanceof Vector) $c = new Collection($c);
		$this->to(null);
		$c->to(null);
		for ($i=0, $len=$repeat ? max($this->len, $c->len) : min($this->len, $c->len); $i<$len; $i++) {
			$this->next();
			if (!$this->current()) $this->next();
			$c->next();
			if (!$c->current()) $c->next();
			$func($this->current(), $c->current(), $i, $this);
		}
		return $this;
	}

	/*
	 * Варианты аргументов:
	 * 1. ($prop) - выберет все объекты, которые имеют свойство с именем $prop
	 * 2. ($prop, $val) - выберет все объекты, у которых есть свойство $prop и оно равно $val
	 * 3. ($prop, $op, $val) - выберет все объекты, у которых есть свойство $prop и сравнит с $val, варианты оператора $op:
	 *    a. >, <, >=, <=
	 *    b. contain - для массива $prop на наличие элемента $val
	 *    c. like - для строки $prop проверяет совпадение регулярным выражением
	 * */
	public function select() {
		$amt = func_num_args();
		if (!$amt) return null;

		if ($amt == 1) {
			$c = new Collection();
			$arg = func_get_arg(0);

			// функция
			if (is_callable($arg)) {
				$this->each(function($a) use ($c, $arg) { if ($arg($a)) $c->add($a); });
				return $c;
			}

			// набор правил
			if (is_array($arg)) {
				$this->each(function($a) use ($c, $arg) {
					if (!is_object($a)) return;
					$match = true;
					// анализ правил
					foreach ($arg as $rule) {
						if (!property_exists($a, $rule[0])) {
							$match = false;
							break;
						}

						// правила из двух элементов - на сравнение
						if (count($rule) == 2) {
							if ($a->$rule[0] != $rule[1]) $match = false;
						// правила из трех элементов - с оператором
						} else if (count($rule) == 3) {
							$prop = $rule[0];
							$val = $rule[2];
							switch ($rule[1]) {
								case '>': if ($a->$prop <= $val) $match = false; break;
								case '<': if ($a->$prop >= $val) $match = false; break;
								case '>=': if ($a->$prop < $val) $match = false; break;
								case '<=': if ($a->$prop > $val) $match = false; break;
								case 'contain':
									if (!is_array($a->$prop) || array_search($val, $a->$prop) === false) $match = false;
								break;
								case 'like':
									if (!is_string($a->$prop)) $match = false;
									else {
										if ($val[0] != '/') $val = '/'.$val.'/';
										if (!preg_match($val, $a->$prop)) $match = false;
									}
								break;
							}
						}

						if (!$match) break;
					}

					if ($match) $c->add($a);
				});

				return $c;
			}

			// просто проверка на наличие свойства
			$this->each(function($a) use ($c, $arg) {
				if (is_object($a) && property_exists($a, $arg)) $c->add($a);
			});
			return $c;
		}

		return $this->select([func_get_args()]);
	}

	public function stop() {
		$this->stopFlag = true;
	}
}
