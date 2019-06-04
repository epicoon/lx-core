#lx:require positioningStrategiesJs/;

#lx:use lx.Rect as Rect;

/* * 1. Constructor
 * build(config)
 * postBuild(config)
 * postUnpack(config)
 * positioning()
 * static onresize()
 * isAutoParent()
 * begin()
 * end()
 *
 * * 2. Content managment
 * addChild(elem, config = {})
 * modifyNewChildConfig(config)
 * childrenPush(el, next)
 * insert(c, next, config={})
 * add(type, count=1, config={}, configurator={})
 * clear()
 * del(el, index, count)
 * text(text)
 * image(filename)
 * tryChildReposition(elem, param, val)
 * childHasAutoresized(elem)
 * static entry()
 * showOnlyChild(key)
 *
 * * 3. Content navigation
 * get(path)
 * getAll(path)
 * find(key, all=true)
 * findAll(key, all=true)
 * findOne(key, all=true)
 * contain(key)
 * childrenCount(key)
 * child(num)
 * lastChild()
 * divideChildren(info)
 * getChildren(info=false, all=false)
 * each(func, info=false)
 *
 * * 4. PositioningStrategies
 * preparePositioningStrategy(strategy)
 * align(hor, vert, els)
 * stream(config)
 * streamProportional(config={})
 * streamAutoSize(config={})
 * streamDirection()
 * grid(config)
 * gridProportional(config={})
 * gridAutoSize(config={})
 * slot(config)
 * setIndents(config)
 *
 * * 5. Load
 * injectModule(info, func)
 * dropModule()
 *
 * * 6. Js-features
 * bind(model)
 * matrix(config)
 * agregator(c, toWidget=true, fromWidget=true)
 */

/**
 * @group {i18n:widgets}
 * */
class Box extends Rect #lx:namespace lx {

	//=========================================================================================================================
	/* 1. Constructor */
	__construct() {
		super.__construct();
		this.children = {};
	}

	/**
	 * config = {
	 *	// стандартные для Rect,
	 *	
	 *	positioning: PositioningStrategy
	 *	text: string
	 *	image: string  // filename - имя файла в изображениях текущего модуля
	 * }
	 * */
	build(config) {
		super.build(config);

		if (config.positioning) {
			this.positioningStrategy = new config.positioning(this, config);
		}

		if ( config.text ) this.text( config.text );

		if (config.image) {
			new lx.Image({
				parent: this,
				key: 'image',
				filename: config.image
			});
		}

		if (config.stream) this.stream(config.stream.isObject ? config.stream : {});
		if (config.grid) this.grid(config.grid.isObject ? config.grid : {});
		if (config.slot) this.slot(config.slot.isObject ? config.slot : {});
	}

	postBuild(config) {
		super.postBuild(config);
		this.on('resize', self::onresize);
		this.on('scrollBarChange', self::onresize);

		//TODO - это очень очень плохо. Неочевидно, если что-то и должно такое быть - оно должно инициироваться явно, сам код должен быть на уровне стратегии
		// //todo - единственное пока взаимодействие стратегий элемента и родителя. Выносить в отдельный метод, еще какие-то может нужны
		// //todo - еще момент - работает такая связь только если 'grid' передали в конфиге, если методом навесили - связи нет. Может это не баг, а фича?
		// // Если стратегия позиционирования grid и у элемента и у родителя - осуществляется некоторое взаимодействие - выставляется высота и заимстуются шаги
		// if (this.positioning().lxClassName == 'GridPositioningStrategy'
		// 	&& this.parent
		// 	&& this.parent.positioning().lxClassName == 'GridPositioningStrategy'
		// ) {
		// 	console.log('!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
		// 	console.log(this.inGrid);

		// 	this.height( this.positioning().map.len );

		// 	console.log('!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
		// 	console.log(this.inGrid);

		// 	var indents = this.parent.positioning().indents.get();
		// 	this.positioning().autoActualize = true;
		// 	this.positioning().setIndents({
		// 		stepX: indents.stepX,
		// 		stepY: indents.stepY
		// 	}).actualize();

		// 	console.log('2!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
		// 	console.log(this.inGrid);
		// }
	}

	postUnpack(config) {
		super.postUnpack(config);
		if (this.lxExtract('__na'))
			this.positioningStrategy.actualize();
	}

	positioning() {
		if (this.positioningStrategy) return this.positioningStrategy;
		return new lx.PositioningStrategy(this);
	}

	static onresize() {
		this.positioning().actualize();
	}

	begin() {
		lx.WidgetHelper.autoParent = this;
		return true;
	}

	isAutoParent() {
		return (lx.WidgetHelper.autoParent === this);
	}

	end() {
		if (!this.isAutoParent()) return false;
		lx.WidgetHelper.popAutoParent();
		return true;
	}
	/* 1. Constructor */
	//=========================================================================================================================


	//=========================================================================================================================
	/* 2. Content managment */
	/**
	 * Метод, используемый новым элементом для регистрации в родителе
	 * */
	addChild(elem, config = {}) {
		config = this.modifyNewChildConfig(config);

		if (config.nextSibling) this.DOMelem.insertBefore( elem.DOMelem, config.nextSibling.DOMelem );
		else this.DOMelem.appendChild( elem.DOMelem );
		elem.parent = this;

		var	clientHeight0 = this.DOMelem.clientHeight,
			clientWidth0 = this.DOMelem.clientWidth;

		this.childrenPush(elem, config.nextSibling);
		this.positioning().allocate(elem, config);

		var clientHeight1 = this.DOMelem.clientHeight;
		if (clientHeight0 > clientHeight1) {
			this.trigger('xScrollBarOn');
			this.trigger('xScrollBarChange');
			this.trigger('scrollBarChange');
		} else if (clientHeight0 < clientHeight1) {
			this.trigger('xScrollBarOff');
			this.trigger('xScrollBarChange');
			this.trigger('scrollBarChange');
		}

		var clientWidth1 = this.DOMelem.clientWidth;
		if (clientWidth0 > clientWidth1) {
			this.trigger('yScrollBarOn');
			this.trigger('yScrollBarChange');
			this.trigger('scrollBarChange');
		} else if (clientWidth0 < clientWidth1) {
			this.trigger('yScrollBarOff');
			this.trigger('yScrollBarChange');
			this.trigger('scrollBarChange');
		}
	}

	/**
	 * Предобработка конфига добавляемого элемента
	 * */
	modifyNewChildConfig(config) {
		return config;
	}

	/**
	 * Регистрация ключа нового элемента в структурах родителя
	 * */
	childrenPush(el, next) {
		if (!el.key) return;

		if (el.key in this.children) {
			if (!this.children[el.key].isArray) {
				this.children[el.key]._index = 0;
				this.children[el.key] = [this.children[el.key]];
			}
			if (next && el.key == next.key) {
				el._index = next._index;
				this.children[el.key].splice(el._index, 0, el);
				for (var i=el._index+1,l=this.children[el.key].length; i<l; i++) {
					this.children[el.key][i]._index = i;
				}
			} else {
				el._index = this.children[el.key].length;
				this.children[el.key].push(el);
			}
		} else this.children[el.key] = el;
	}

	/**
	 * Поддержка массовой вставки
	 * */
	insert(c, config={}) {
		c = lx.Collection.cast(c);
		var next = config.before;
		var positioning = this.positioning();
		if (this.positioningStrategy) this.positioningStrategy.autoActualize = false;

		var map = [];
		c.each((a)=> {
			a.dropParent();
			if (next) this.DOMelem.insertBefore( a.DOMelem, next.DOMelem );
			else this.DOMelem.appendChild( a.DOMelem );
			a.parent = this;
			positioning.allocate(a, config);
			if (a.key) {
				(a.key in map) ? map[a.key].push(a) : map[a.key] = [a];
			}
		});

		for (var key in map) {
			var item = map[key];
			if (key in this.children) {
				if (!this.children[key].isArray) {
					this.children[key]._index = 0;
					this.children[key] = [this.children[key]];
				}
				if (next && key == next.key) {
					var index = next._index;
					this.children[key].splice.apply(this.children[key], [index, 0].concat(item));
					for (var i=index,l=this.children[key].length; i<l; i++) {
						this.children[key][i]._index = i;
					}
				} else {
					var index = this.children[key].length;
					for (var i=0, l=item.length; i<l; i++) {
						item[i]._index = index + i;
						this.children[key].push(item[i]);
					}
				}
			} else {
				this.children[key] = item;
				for (var i=0,l=item.length; i<l; i++) item[i]._index = i;
			}
		}

		if (this.positioningStrategy) {
			this.positioningStrategy.autoActualize = true;
			this.positioningStrategy.actualize({from: c.first()});
		}

		return this;
	}

	/**
	 * Варианты использования:
	 * 1. el.add(lx.Box, config);
	 * 2. el.add(lx.Box, 5, config, configurator);
	 * 3. el.add([
	 *        [lx.Box, config1],
	 *        [lx.Box, 5, config2, configurator]
	 *    ]);
	 * */
	add(type, count=1, config={}, configurator={}) {
		if (type.isArray) {
			var result = [];
			for (var i=0, l=type.len; i<l; i++)
				result.push( this.add.apply(this, type[i]) );
			return result;
		}
		if (count.isObject) {
			config = count;
			count = 1;
		}
		config.parent = this;
		return count == 1
			? new type(config)
			: type.construct(count, config, configurator);
	}

	/**
	 * Проход по всем потомкам
	 * */
	eachChild(func) {
		function re(elem) {
			func(elem);
			// if (elem.destruct) elem.destruct();
			if (!elem.child) return;
			var num = 0,
				child = elem.child(num);
			while (child) {
				re(child);
				child = elem.child(++num);
			}
		}
		re(this);
	}

	/**
	 * Удаляет всех потомков
	 * */
	clear() {
		// Сначала все потомки должны освободить используемые ресурсы
		this.eachChild((child)=>{
			if (child.destruct) child.destruct();
		});

		// После чего можно разом обнулить содержимое
		this.DOMelem.innerHTML = '';
		lx.WidgetHelper.checkFrontMap();

		this.children = {};
		this.positioning().reset();
	}

	/*
	 * Удаление элементов в вариантах:
	 * 1. Без аргументов - удаление элемента, на котором метод вызван
	 * 2. Аргумент el - элемент - если такой есть в элементе, на котом вызван метод, он будет удален
	 * 3. Аргумент el - ключ (единственный аргумент) - удаляется элемент по ключу, если по ключу - массив,
	 *    то удаляются все элементы из этого массива
	 * 4. Аргументы el (ключ) + index - имеет смысл, если по ключу - массив, удаляется из массива 
	 * элемент с индексом index в массиве
	 * 5. Аргументы el (ключ) + index + count - как 4, но удаляется count элементов начиная с index
	 * */
	del(el, index, count) {
		// ситуация 1 - элемент не передан, надо удалить тот, на котором вызван метод
		if (el === undefined) {
			// если элемент - пустышка, нечего удалять
			if (this.DOMelem === null) return 0;

			var p = this.parent;


			//todo что-то с ними не так уже работает
			// если нет родителя - это корневой элемент
			if (!p) {
				// если это body - его мы не удаляем
				if (this.key == 'body') return 0;
				lx.delRootElement(this);
				return 1;
			} 


			return p.del(this);
		}

		// ситуация 2 - el - объект
		if (!el.isString) {
			// Проверка на дурака - не удаляем чужой элемент
			if (el.parent !== this) return;

			// Если у элемента есть ключ - будем удалять по ключу
			if (el.key && el.key in el.parent.children) return this.del(el.key, el._index, 1);

			// Если ключа нет - удаляем проще
			lx.WidgetHelper.removeFromFrontMap(el);
			var pre = el.prevSibling();
			this.DOMelem.removeChild(el.DOMelem);
			this.positioning().actualize({from: pre, deleted: [el]});
			return 1;
		}

		// el - ключ
		if (!(el in this.children)) return 0;

		// children[el] - не массив, элемент просто удаляется
		if (!this.children[el].isArray) {
			var elem = this.children[el],
				pre = elem.prevSibling();
			lx.WidgetHelper.removeFromFrontMap(elem);
			this.DOMelem.removeChild(elem.DOMelem);
			delete this.children[el];
			this.positioning().actualize({from: pre, deleted: [elem]});
			return 1;
		}

		// children[el] - массив
		if (count === undefined) count = 1;
		if (index === undefined) {
			index = 0;
			count = this.children[el].length;
		} else if (index >= this.children[el].length) return 0;
		if (index + count > this.children[el].length)
			count = this.children[el].length - index;

		var deleted = [],
			pre = this.children[el][index].prevSibling();
		for (var i=index,l=index+count; i<l; i++) {
			var elem = this.children[el][i];

			deleted.push(elem);
			lx.WidgetHelper.removeFromFrontMap(elem);
			this.DOMelem.removeChild(elem.DOMelem);
		}
		this.children[el].splice(index, count);
		for (var i=index,l=this.children[el].length; i<l; i++)
			this.children[el][i]._index = i;
		if (!this.children[el].length) {
			delete this.children[el];
		} else if (this.children[el].length == 1) {
			this.children[el] = this.children[el][0];
			delete this.children[el]._index;
		}
		this.positioning().actualize({from: pre, deleted});

		return count;
	}

	text(text) {
		if (text == undefined) {
			if ( !this.contain('text') ) return '';
			return this.children.text.value();
		}

		if (!this.contain('text')) new lx.TextBox({parent: this});

		this.children.text.value(text);
		return this;
	}

	image(filename) {
		new Image({ parent: this, filename });
		return this;
	}

	tryChildReposition(elem, param, val) {
		return this.positioning().tryReposition(elem, param, val);
	}

	childHasAutoresized(elem) {
		this.positioning().reactForAutoresize(elem);
	}

	/**
	 * Навешивается на обработчики - контекстом устанавливает активированный элемент
	 * */
	entry() {
		/*
		todo
		делать по механизму как редактор, а не через инпут
		*/
		if ( this.contain('input') ) return;

		var _t = this,
			boof = this.text(),
			input = new lx.Textarea({
				parent: this,
				key: 'input',
				geom: [0, 0, this.width('px')+'px', this.height('px')+'px']
			});

		input.DOMelem.value = boof;
		input.focus();
		input.DOMelem.select();
		input.style('visibility', 'visible');
		input.on('blur', function() {
			var boof = this.DOMelem.value.replace(/<.+?>/g, 'tag');
			_t.del('input');
			_t.text(boof);
			_t.show();
			_t.trigger('blur', event);
		});

		this.hide();
	}

	showOnlyChild(key) {
		this.getChildren().each((a)=> a.visibility(a.key == key));
	}
	/* 2. Content managment */
	//=========================================================================================================================


	//=========================================================================================================================
	/* 3. Content navigation */
	/**
	 *
	 * */
	get(path) {
		if (path instanceof Rect) return path;

		var arr = path.match(/[\w\d_\[\]]+/ig),
			children = this.children;
		for (var i=0,l=arr.length; i<l; i++) {
			var key = arr[i].split('['),
				index = (key.len > 1) ? parseInt(key[1]) : null;
			key = key[0];
			if (!(key in children)) return null;
			if (i+1 == l) {
				if (index === null) return children[key];
				return children[key][index];
			}
			children = (index === null)
				? children[key].children
				: children[key][index].children;
		}
	}

	/**
	 * Возвращает всегда коллекцию потомков
	 * */
	getAll(path) {
		return new lx.Collection(this.get(path));
	}

	find(key, all=true) {
		var c = this.getChildren({hasProperties:{key}, all});
		if (c.len == 1) return c.at(0);
		return c;
	}

	findAll(key, all=true) {
		var c = this.getChildren({hasProperties:{key}, all});
		return c;
	}

	findOne(key, all=true) {
		var c = lx.Collection.cast(this.find(key, all));
		if (c.isEmpty) return null;
		return c.at(0);
	}

	contain(key) {
		if (key instanceof Rect) {
			if (!(key.key in this.children)) return false;
			if (this.children[key.key].isArray) {
				if (key._index === undefined) return false;
				return this.children[key.key][key._index] === key;
			}
			return this.children[key.key] === key;
		}
		return (key in this.children);
	}

	childrenCount(key) {
		if (key === undefined) return this.DOMelem.children.length;
		if (!this.children[key]) return 0;
		if (!this.children[key].isArray) return 1;
		return this.children[key].len;
	}

	child(num) {
		if (num >= this.childrenCount()) return null;
		return this.DOMelem.children[num].lx;
	}

	lastChild() {
		var lc = this.DOMelem.lastChild;
		if (!lc) return null;
		return lc.lx;
	}

	divideChildren(info) {
		var all = info.all !== undefined ? info.all : false;
		var match = new lx.Collection(),
			notMatch = new lx.Collection();
		function rec(el) {
			if (el === null || !el.childrenCount) return;
			for (var i=0; i<el.childrenCount(); i++) {
				var child = el.child(i),
					matched = true;
				if (!child) continue;

				if (info.callback) matched = info.callback(child);

				//todo обыграть логику AND и OR
				if (info.hasProperties) {
					var prop = info.hasProperties;
					if (prop.isObject) {
						for (var j in prop)
							if (!(j in child) || child[j] != prop[j]) { matched = false; break; }
					} else if (prop.isArray) {
						for (var j=0, l=prop.len; j<l; j++)
							if (!(prop[j] in child)) { matched = false; break; }
					} else if (!(prop in child)) matched = false;
				}

				if (matched) match.add(child);
				else notMatch.add(child);
				if (all) rec(child);
			}
		}
		rec(this);
		return {match, notMatch};
	}

	/**
	 * Варианты:
	 * 1. getChildren()  - вернет своих непосредственных потомков
	 * 2. getChildren(true)  - вернет всех потомков, всех уровней вложенности
	 * 3. getChildren((a)=>{...})  - из своих непосредственных потомков вернет тех, для кого коллбэк вернет true
	 * 4. getChildren((a)=>{...}, true)  - из всех своих потомков вернет тех, для кого коллбэк вернет true
	 * 5. getChildren({hasProperties:''})
	 * 6. getChildren({hasProperties:'', all:true})
	 * 7. getChildren({callback:(a)=>{...}})  - см. 3.
	 * 8. getChildren({callbacl:(a)=>{...}, all:true})  - см. 4.
	 * */
	getChildren(info=false, all=false) {
		if (info === false) {
			var c = new lx.Collection();
			// for (var i in this.children) c.add(this.children[i]);

			for (var i=0; i<this.childrenCount(); i++) {
				var child = this.child(i);
				if (child instanceof Rect) c.add(child);
			}
			return c;
		} else if (info === true) {
			return this.divideChildren({all:true}).match;
		}

		//todo заменяет по сути hasProperties. Может менее читабельно, но не факт. Можно упростить
		if (info.isFunction) info = {callback: info, all};

		return this.divideChildren(info).match;
	}

	each(func, info=false) {
		this.getChildren(info).each(func);
	}
	/* 3. Content navigation */
	//=========================================================================================================================


	//=========================================================================================================================
	/* 4. PositioningStrategies */
	stopPositioning() {
		if (this.positioningStrategy) this.positioningStrategy.autoActualize = false;
	}

	startPositioning() {
		if (this.positioningStrategy) {
			this.positioningStrategy.autoActualize = true;
			this.positioningStrategy.actualize();
		}
	}

	preparePositioningStrategy(strategy) {
		if (this.positioningStrategy) {
			if (this.positioningStrategy.lxClassName == strategy.name) return;
			this.positioningStrategy.clear();
		}
		this.positioningStrategy = (strategy === lx.PositioningStrategy)
			? null
			: new strategy(this);
	}

	/**
	 * Устанавливает стратегию AlignPositioningStrategy, если еще не была установлена
	 * Добавляет в стратегию новове правило выравнивания
	 * Сбрасывает предыдущую стратегию, если была отличная от AlignPositioningStrategy
	 * */
	align(hor, vert, els) {
		this.preparePositioningStrategy(lx.AlignPositioningStrategy);
		this.positioningStrategy.addRule(hor, vert, els);
		return this;
	}

	stream(config) {
		this.preparePositioningStrategy(lx.StreamPositioningStrategy);
		this.positioningStrategy.init(config);
		return this;
	}

	streamProportional(config={}) {
		config.sizeBehavior = lx.StreamPositioningStrategy.SIZE_BEHAVIOR_PROPORTIONAL;
		return this.stream(config);
	}

	streamAutoSize(config={}) {
		config.sizeBehavior = lx.StreamPositioningStrategy.SIZE_BEHAVIOR_BY_CONTENT;
		return this.stream(config);
	}

	streamDirection() {
		if (!this.positioningStrategy || this.positioningStrategy.lxClassName != 'StreamPositioningStrategy')
			return false;
		return this.positioningStrategy.direction;
	}

	grid(config) {
		this.preparePositioningStrategy(lx.GridPositioningStrategy);
		this.positioningStrategy.init(config);
		return this;
	}

	gridProportional(config={}) {
		config.sizeBehavior = lx.GridPositioningStrategy.SIZE_BEHAVIOR_PROPORTIONAL;
		return this.grid(config);
	}

	gridAutoSize(config={}) {
		config.sizeBehavior = lx.GridPositioningStrategy.SIZE_BEHAVIOR_BY_CONTENT;
		return this.grid(config);
	}

	slot(config) {
		this.preparePositioningStrategy(lx.SlotPositioningStrategy);
		this.positioningStrategy.init(config);
		return this;
	}

	setIndents(config) {
		if (!this.positioningStrategy) return;
		this.positioningStrategy.setIndents(config);
		this.positioningStrategy.actualize();
		return this;
	}

	//todo нужен или нет этот метод?
	// setGeomParam(param, val) {
	// 	if (this.positioningStrategy.checkOwnerReposition(param))
	// 		super.setGeomParam(param, val);
	// }
	/* 4. PositioningStrategies */
	//=========================================================================================================================


	//=========================================================================================================================
	/* 5. Load */
	/**
	 * Загружает уже полученные данные о модуле в элемент
	 * */
	injectModule(info, func) {
		this.dropModule();
		lx.Loader.run(info, this, this.getModule(), func);
	}

	/**
	 * Удаляет модуль из элемента, из реестра модулей и всё, что связано с модулем
	 * */
	dropModule() {
		if (this.module) {
			this.module.del();
			delete this.module;
		}
	}

	/* 5. Load */
	//=========================================================================================================================


	//=========================================================================================================================
	/* 6. Js-features */
	bind(model, type=lx.Binder.BIND_TYPE_FULL) {
		model.bind(this, type);
	}

	/**
	 * Если один аргумент - полная передача конфига:
	 * {
	 * 	items: lx.Collection,
	 * 	itemBox: [Widget, Config],
	 * 	itemRender: function(itemBox, model) {}
	 *  afterBind: function(itemBox, model) {}
	 * 	type: boolean
	 * }
	 * Если три(два) аргумента - краткая передача коллекции и коллбэков:
	 * - lx.Collection
	 * - Function  - itemRender
	 * - Function  - afterBind
	 * */
	matrix(...args) {
		let config;
		if (args.len == 1 && args[0].isObject) config = args[0];
		else { config = {
			items: args[0],
			itemRender: args[1],
			afterBind: args[2]
		}; };
		if (!config.itemBox) config.itemBox = self::defaultMatrixItemBox;

		lx.Binder.makeWidgetMatrix(this, config);
		lx.Binder.bindMatrix(
			config.items,
			this,
			[config.type, lx.Binder.BIND_TYPE_FULL].lxGetFirstDefined()
		);
	}

	agregator(c, type=lx.Binder.BIND_TYPE_FULL) {
		lx.Binder.bindAgregation(c, this, type);
	}
	/* 6. Js-features */
	//=========================================================================================================================


	//=========================================================================================================================
	/* todo из старого блока Align, странные механизмы взаимного позиционирования. В контексте стратегий позиционирования, походу, не нужны? */
	satellites(info) {  //todo - move to Rect
		/* info = {
			elems : []  ||  lx.Collection,
			side: LEFT | TOP | RIGHT | BOTTOM
			align: TOP/BOTTOM/MIDDLE/JUSTIFY | LEFT/RIGHT/CENTER/JUSTIFY	
			direction: VERTICAL | HORIZONTAL
			margin | marginX, marginY: n
			step: n
		} */

		var elems = info.elems ? info.elems : info,
			side = info.side ? info.side : lx.RIGHT,
			align = info.align ? info.align : 
				((side == lx.LEFT || side == lx.RIGHT) ? lx.TOP : lx.LEFT),
			direction = info.direction ? info.direction :
				((side == lx.TOP || side == lx.BOTTOM) ? lx.HORIZONTAL : lx.VERTICAL),
			step = info.step || 0,
			marginX = info.marginX || info.margin || step,
			marginY = info.marginY || info.margin || step,
			xStart, yStart,
			alignBy,
			dir;  // 0 - по x размещаются, 1 - по y
		elems = lx.Collection.cast(elems);
		step = this.geomPart(step, 'px', direction);
		marginX = this.geomPart(marginX, 'px', lx.HORIZONTAL);
		marginY = this.geomPart(marginY, 'px', lx.VERTICAL);

		var rect = this.rect('px');
		if (side == lx.RIGHT || side == lx.LEFT) {
			xStart = (side == lx.RIGHT) ? rect.right + marginX : rect.left - marginX;

			if (direction == lx.HORIZONTAL) {
				alignBy = true;
				dir = 0;
				if (side == lx.LEFT) 
					xStart -= (elems.sum('width', 'px') + step * (elems.len - 1));
			} else {
				if (align == lx.TOP) {
					yStart = rect.top;
				} else if (align == lx.BOTTOM) {
					var h = elems.sum('height', 'px') + step * (elems.len - 1);
					yStart = rect.bottom - h;
				} else if (align == lx.MIDDLE) {
					var h = elems.sum('height', 'px') + step * (elems.len - 1);
					yStart = rect.top + ( rect.height - h ) * 0.5;					
				} else if (align == lx.JUSTIFY) {
					yStart = rect.top;
					step = (rect.height - elems.sum('height', 'px')) / (elems.len - 1);					
				}
				alignBy = false;
				dir = 1;
			}
		} else if (side == lx.TOP || side == lx.BOTTOM) {
			yStart = (side == lx.BOTTOM) ? rect.bottom + marginY : rect.top - marginY;
			if (direction == lx.VERTICAL) {
				alignBy = true;
				dir = 1;
				if (side == lx.TOP)
					yStart -= (elems.sum('height', 'px') + step * (elems.len - 1));
			} else {
				if (align == lx.LEFT) {
					xStart = rect.left;
				} else if (align == lx.LEFT) {
					var w = elems.sum('width', 'px') + step * (elems.len - 1);
					xStart = rect.right - w;
				} else if (align == lx.CENTER) {
					var w = elems.sum('width', 'px') + step * (elems.len - 1);
					xStart = rect.left + ( rect.width - w ) * 0.5;	
				} else if (align == lx.JUSTIFY) {
					xStart = rect.left;
					step = (rect.width - elems.sum('width', 'px')) / (elems.len - 1);					
				}
				alignBy = false;
				dir = 0;
			}
		}

		var _t = this,
			x = xStart,
			y = yStart;

		elems.each(function(a) {
			if (alignBy) a.locateBy(_t, align);
			if (y !== undefined) {
				if (side == lx.TOP && direction == lx.HORIZONTAL) a.top(y - a.height('px') + 'px');
				else a.top(y+'px');
			}
			if (x !== undefined) {
				if (side == lx.LEFT && direction == lx.VERTICAL) a.left(x - a.width('px') + 'px')
				else a.left(x+'px')
			}
			if (dir) y += a.height('px') + step;
			else x += a.width('px') + step;
		});
		return elems;
	}
	/* todo из старого блока Align, странные механизмы взаимного позиционирования. В контексте стратегий позиционирования, походу, не нужны? */
	//=========================================================================================================================
}

lx.Box.defaultMatrixItemBox = lx.Box;
