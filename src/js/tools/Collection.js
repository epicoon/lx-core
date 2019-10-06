/* Collection *//**********************************************
	len
	isEmpty
	clear()
	at(k)
	to(k)
	set(i, val)
	contains(obj)
	first()
	last()
	current(value)
	next()
	prev()
	toCopy()
	add(*arguments*)
	addCopy(*arguments*)
	addList(list)
	flat(deep)
	construct(*arguments*)
	indexOf(el)
	remove(el)
	removeAt(k)
	sub()
	sum(method, ...args)
	call()
	callRepeat()
	each(func)
	eachToEach(func)
	eachRevert(func)
	getEach(funcName, args)
	mix(c, func, repeat)
	select(func)
	stop()

***************************************************************/

class Collection extends lx.Object #lx:namespace lx {
	constructor(...args) {
		super();

		this.actPart = null;
		this.actI = null;
		this.actPartI = null;

		this.isCopy = false;
		this.elements = [];
		this.map = [];

		this.reversIteration = false;
		this.stopFlag = false;
		this.repeat = true;

		if (args.length) this.add.apply(this, args);
	}

	static cast(obj) {
		if (obj === undefined || obj === null) return new this();
		if (obj.is(this)) return obj;
		return new this(obj);
	}


	// var object = {};
	// object.constructor = arguments.callee;
	// object.actPart = null;
	// object.actI = null;
	// object.actPartI = null;

	// object.isCopy = false;
	// object.elements = [];
	// object.map = [];

	// object.stopFlag = false;
	// object.repeat = true;

	clear() {
		this.actPart = null;
		this.actI = null;
		this.actPartI = null;

		this.isCopy = false;
		this.elements = [];
		this.map = [];
	}

	at(k) {
		if (this.reversIteration) k = this.len - 1 - k;
		if (!this.to(k)) return null;
		return this.current();
	}

	/*
	 * k - может быть как индекс (если число), так и элемент (кроме числа)
	 * k - может быть null, тогда просто сбрасывается позиционирование итератора
	 * */
	to(k) {
		if (k === null) {
			this.actPart = null;
			return this;
		}

		if (k.isNumber) {
			if (k >= this.len) return false;

			if (this.isCopy) {
				this.actPart = this.elements;
				this.actPartI = k;
			} else {
				for (var i=0, l=this.map.length; i<l; i++) {
					if (this.map[i].length > k) {
						this.actPart = this.map[i];
						this.actI = i;
						this.actPartI = k;
						break;
					} else k -= this.map[i].length;
				}
			}
		} else {
			var match = false;
			this.first();
			while (!match && this.current()) {
				if (this.current() === k) match = true;
				else this.next();
			}
			if (!match) return false;
		}

		return this;
	}

	set(i, val) {
		this.to(i);
		this.current(val);
		return this;
	}

	contains(obj) {
		this.cachePosition();
		var match = false;
		var curr = this.first();
		while (curr && !match)
			if (curr === obj) match = true;
			else curr = this.next();
		this.loadPosition();
		return match;
	}

	current(value) {
		if (this.actPart === null)
			return null;
		if (value === undefined)
			return this.actPart[this.actPartI];
		this.actPart[this.actPartI] = value;
		return this;
	}

	__first(value) {
		if (this.isCopy) {
			if (!this.elements.len) return null;
			this.actPart = this.elements;
			this.actPartI = 0;
		} else {
			if (!this.map.len || !this.map[0].len) return null;
			this.actPart = this.map[0];
			this.actI = 0;
			this.actPartI = 0;
		}
		if (value === undefined)
			return this.actPart[this.actPartI];
		this.actPart[this.actPartI] = value;
		return this;
	}

	__last(value) {
		if (this.isCopy) {
			if (!this.elements.len) return null;
			this.actPart = this.elements;
			this.actPartI = this.elements.len - 1;
		} else {
			if (!this.map.len || !this.map[0].len) return null;
			this.actI = this.map.len - 1;
			this.actPart = this.map[this.actI];
			this.actPartI = this.actPart.len - 1;
		}
		if (value === undefined)
			return this.actPart[this.actPartI];
		this.actPart[this.actPartI] = value;
		return this;
	}

	__next() {
		if (this.actPart === null) return this.first();

		this.actPartI++;
		if (this.actPart.len == this.actPartI) {
			if (this.isCopy) {
				this.actPart = null;
				return null;
			} else {
				this.actI++;
				if (this.map.len == this.actI) {
					this.actPart = null;
					return null;
				} else {
					this.actPartI = 0;
					this.actPart = this.map[this.actI];
				}
			}
		}
		return this.actPart[this.actPartI];
	}

	__prev() {
		if (this.actPart === null) return this.last();

		this.actPartI--;
		if (this.actPartI == -1) {
			if (this.isCopy) {
				this.actPart = null;
				return null;
			} else {
				this.actI--;
				if (this.actI == -1) {
					this.actPart = null;
					return null;
				} else {
					this.actPart = this.map[this.actI];
					this.actPartI = this.actPart.len - 1;
				}
			}
		}
		return this.actPart[this.actPartI];
	}

	first() {
		if (this.reversIteration) return this.__last();
		return this.__first();
	}

	last() {
		if (this.reversIteration) return this.__first();
		return this.__last();
	}

	next() {
		if (this.reversIteration) return this.__prev();
		return this.__next();
	}

	prev() {
		if (this.reversIteration) return this.__next();
		return this.__prev();
	}

	toCopy() {
		if (this.isCopy) return this;
		var iter = 0;
		for (var i=0, l=this.map.len; i<l; i++) {
			if (this.actPart && i < this.actI) iter += this.map[i].len;
			else if (this.actPart && i == this.actI) iter += this.actPartI;
			for (var j=0, ll=this.map[i].len; j<ll; j++) {
				this.elements.push(this.map[i][j]);
			}
		}
		this.map = [];
		this.isCopy = true;
		if (this.actPart) {
			this.actPart = this.elements;
			this.actPartI = iter;
		}
		return this;
	}

	add() {
		if ( arguments == undefined ) return this;
		if (this.isCopy) return this.addCopy.apply(this, arguments);

		for (var i=0, l=arguments.length; i<l; i++) {
			var arg = arguments[i];

			if (arg === null) continue;

			if (arg.isArray) {
				this.map.push(arg);
			} else if ( arg.lxClassName == 'Collection' ) {
				if (arg.isCopy) this.add(arg.elements);
				else for (var j=0, ll=arg.map.length; j<ll; j++)
					this.add( arg.map[j] );
			} else {
				if ( this.map.len && this.map.last().singles ) {
					this.map.last().push(arg);
				} else {
					var arr = [arg];
					Object.defineProperty(arr, "singles", { get: function() { return true; } });
					this.map.push(arr);
				}
			}
		}

		return this;
	}

	addCopy() {
		this.toCopy();
		if ( arguments == undefined ) return this;

		for (var i=0, l=arguments.length; i<l; i++) {
			var arg = arguments[i];
			if (arg === null) continue;

			if (arg.isArray) {
				for (var j=0, ll=arg.length; j<ll; j++)
					this.elements.push( arg[j] );
			} else if ( arg.lxClassName == 'Collection' ) {
				arg.first();
				while (arg.current()) {
					this.elements.push( arg.current() );
					arg.next();
				}
			} else this.elements.push( arg );
		}

		return this;
	}

	addList(list, func) {
		for (var i in list) {
			if (func) func(list[i], i);
			this.add(list[i]);
		}
		return this;
	}

	flat(deep) {
		// изменять внутреннюю структуру содержащихся массивов можно только с копией
		this.toCopy();
		var arr = [];

		function rec(tempArr, counter) {
			for (var i=0,l=tempArr.length; i<l; i++) {
				if ((deep && (counter+1 > deep)) || !tempArr[i].isArray) {
					arr.push( tempArr[i] );
				}
				else rec(tempArr[i], counter + 1);
			}
		}
		rec(this.elements, 0);

		this.elements = arr;
		return this;
	}

	construct(/*arguments*/) {
		this.add(lx.Collection.construct.apply(null, arguments));
		return this;
	}

	indexOf(el) {
		//todo - возможно неоптимально
		this.toCopy();
		var index = this.elements.indexOf(el);
		if (index == -1) return false;
		return index;
	}

	remove(el) {
		// изменять внутреннюю структуру содержащихся массивов можно только с копией
		this.toCopy();
		var index = this.elements.indexOf(el);
		if (index == -1) return false;
		return this.removeAt(index);
	}

	removeAt(k) {
		// изменять внутреннюю структуру содержащихся массивов можно только с копией
		this.toCopy();
		this.to(k);
		this.elements.splice(k, 1);
		if (this.actPartI >= this.elements.length)
			this.actPartI = this.elements.length - 1;
		return true;
	}

	sub(k, amt) {
		var c = new lx.Collection();
		if (k === undefined) return c;
		amt = amt || 1;

		this.to(k);
		for (var i=0; i<amt; i++) {
			if (!this.current()) return c;
			c.add(this.current()); 
			this.next();
		}

		return c;
	}

	sum(method, ...args) {
		var result = 0;
		this.each((a)=> {
			var val = 0;
			if (a[method]) {
				if (a[method].isNumber) val = a[method];
				else if (a[method].isFunction) val = a[method].apply(a, args);
			} else if (a.toNumber) val = a.toNumber();
			if (!val.isNumber) val = 0;
			result += val;
		});
		return result;
	}
 	
	call(funcName, ...args) {
		this.each((a)=> {
			if ( a === null || !a.lxHasMethod(funcName) ) return;
			a[funcName].apply(a, args);
		});

		return this;
	}

	callRepeat(funcName, args) {
		var current = 0;
		this.each((a)=> {
			if (a === null || !a.lxHasMethod(funcName) ) return;

			if (args[current].isArray) a[funcName].apply(a, args[current]);
			else a[funcName].call(a, args[current]);
			current++;
			if (current == args.len) current = 0;
		});

		return this;
	}

	cachePosition() {
		if (!this.cachepos) this.cachepos = [];
		this.cachepos.push([this.actPart, this.actI, this.actPartI]);
	}

	loadPosition() {
		if (!this.cachepos) return false;
		var cache = this.cachepos.pop();
		this.actPart = cache[0];
		this.actI = cache[1];
		this.actPartI = cache[2];
		if (!this.cachepos.len) delete this.cachepos;
		return true;
	}

	each(func) {
		this.stopFlag = false;
		this.cachePosition();
		var i = 0,
			el = this.first();
		while (el && !this.stopFlag) {
			func.call( this, el, i++ );
			el = this.next();
		}
		this.loadPosition();
		return this;
	}

	/**
	 * Метод для взаимодействия каждого элемента коллекции с каждым элементом коллекции
	 * */
	eachToEach(func) {
		this.stopFlag = false;
		this.cachePosition();

		var i = 0,
			el0 = this.first();
		while (el0 && !this.stopFlag) {
			this.cachePosition();
			var j = i+1,
				el1 = this.next();

			while (el1 && !this.stopFlag) {
				func.call( this, el0, el1, i, j++ );
				el1 = this.next();
			}

			this.loadPosition();
			el0 = this.next();
			i++;
		}

		this.loadPosition();
		return this;
	}

	eachRevert(func) {
		this.stopFlag = false;
		this.cachePosition();
		var i = this.len - 1,
			el = this.last();
		while (el && !this.stopFlag) {
			func.call( this, el, i-- );
			el = this.prev();
		}
		this.loadPosition();
		return this;
	}

	getEach(attr, args) {
		var c = new lx.Collection();
		this.each(function(a) {
			if (!(attr in a)) return;
			if (a[attr] instanceof Function) {
				if (args === undefined) {
					c.add(a[attr]());
					return;
				}
				c.add(args.isArray ? a[attr].apply(a, args) : a[attr].call(a, args));
			}
			else c.add(a[attr]);
		});
		return c;
	}

	mix(c, func, repeat) {
		if (c.isArray) c = self::cast(c);
		this.to(null); c.to(null);
		for (var i=0, len=repeat ? Math.max(this.len, c.len) : Math.min(this.len, c.len); i<len; i++) {
			this.next();
			if (!this.current()) this.next();
			c.next();
			if (!c.current()) c.next();
			func.call(this, this.current(), c.current(), i);
		}
		return this;
	}

	/*
	 * Варианты аргументов:
	 * 1. (func(a)) - функция, возвращающая bool для каждого элемента коллекции
	 * 2. (prop) - выберет все объекты, которые имеют свойство с именем prop
	 * 3. (prop, val) - выберет все объекты, у которых есть свойство prop и оно равно val
	 * 4. (prop, op, val) - выберет все объекты, у которых есть свойство prop и сравнит с val, варианты оператора op:
	 *    a. >, <, >=, <=
	 *    b. contains - для массива prop на наличие элемента val
	 *    c. like - для строки prop проверяет совпадение регулярным выражением
	 * */
	select() {
		var amt = arguments.length;
		if (!amt) return null;

		if (amt == 1) {
			var c = new lx.Collection(),
				arg = arguments[0];

			// функция
			if (arg instanceof Function) {
				this.each(function(a) { if (arg(a)) c.add(a); });
				return c;
			}

			// набор правил
			if (arg.isArray) {
				this.each(function(a) {
					var match = true;
					// анализ правил
					for (var i=0,l=arg.len; i<l; i++) {
						var rule = arg[i];
						if (!(rule[0] in a)) {
							match = false;
							break;
						}
						// правила из двух элементов - на сравнение
						if (rule.length == 2) {
							if (a[rule[0]] != rule[1]) match = false;
						// правила из трех элементов - с оператором
						} else if (rule.length == 3) {
							var prop = rule[0],
								val = rule[2];
							switch (rule[1]) {
								case '>': if (a[prop] <= val) match = false; break;
								case '<': if (a[prop] >= val) match = false; break;
								case '>=': if (a[prop] < val) match = false; break;
								case '<=': if (a[prop] > val) match = false; break;
								case 'contains':
									if (!a[prop].isArray || a[prop].indexOf(val) == -1) match = false;
								break;
								case 'like':
									if (!a[prop].isString || !a[prop].match(new RegExp(val))) match = false;
								break;
							}
						}
						if (!match) break;
					}
					if (match) c.add(a);
				});
				return c;
			}

			// просто проверка на наличие свойства
			this.each(function(a) {
				if (arg in a) c.add(a);
			});
			return c;
		}

		return this.select([arguments]);
	}

	stop() {
		this.stopFlag = true;
	}
}

/*
 * Структура аргументов: (constructor, count, {configurator,} arguments...)
 * - consctructor - функция-конструктор объектов, которые мы создаем
 * - count - количество объектов, которые надо создать
 * - configurator - необязательный элемент, если объявлен - доджен содержать реализацию одной или обеих функций:
 *   {
	 * 		preBuild: function(i, args) {},  // передается итератор и массив с аргументами для передачи в конструктор
 *  	                                     // объекта, этот массив можно модифицировать и вернуть
 * 	                                         // можно вообще игнорировать args и вернуть полностью другой массив, например
 * 	                                         // не определять никаких arguments, а формировать их тут
	 * 		postBuild: function(i) {}  // передается итератор, выполняется в контексте уже созданного объекта
 *   }
 * - arguments - аргументы для передачи в функцию-конструктор объектов
 * Пример:
 * lx.Collection().construct(
 * 	lx.Widget, 3, {
 * 		preBuild: function(i, args) {
 * 			args[0].key = 'obj' + i;
 * 			return args;
 * 		},
 * 		postBuild : function(i) {
 * 			this.text(i);
 * 		}
 * 	},
 * 	{height: 10}
 * );
 * */
Object.defineProperty(lx.Collection, "construct", {
	value: function(/*arguments*/) {
		var result = this(), // lx.Collection(),
			constructor = arguments[0],
			count = arguments[1],
			configurator = {},
			pos = 2,
			args;

		if (arguments[2].preBuild || arguments[2].postBuild) {
			configurator = arguments[2];
			pos++;
		}

		if (arguments.length > pos) {
			args = new Array(arguments.length - pos);
			for (var i=0, l=args.length; i<l; i++)
				args[i] = arguments[i + pos];
		}

		for (var i=0; i<count; i++) {
			var modifArgs = args;
			if (configurator.preBuild) modifArgs = configurator.preBuild.call(null, args, i);
			var obj = constructor.apply(null, modifArgs);
			if (configurator.postBuild) configurator.postBuild.call(null, obj, i); 
			result.add(obj);
		}

		return result;
	}
});

Object.defineProperty(lx.Collection.prototype, 'len', {
	get: function() {
		if (this.isCopy) return this.elements.length;
		var len = 0;
		for (var i=0, l=this.map.length; i<l; i++)
			len += this.map[i].length;
		return len;
	}
});

Object.defineProperty(lx.Collection.prototype, 'isEmpty', {
	get: function() {
		if (this.isCopy) return this.elements.length == 0;
		for (var i=0, l=this.map.length; i<l; i++)
			if (this.map[i].length)
				return false;
		return true;
	}
});

Object.defineProperty(lx.Collection.prototype, 'currentIndex', {
	get: function() {
		if (this.actPart === null) return -1;

		if (this.isCopy) return this.actPartI;

		var res = 0;
		for (var i=0, l=this.actI; i<l; i++)
			res += this.map[i].length;
		return res + this.actPartI;
	}
});
