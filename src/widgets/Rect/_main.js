/* * 1. Constructor
 * constructor(config = {})
 * static rise(DOMelem)
 * __construct()
 * preBuild(config)
 * build(config={})
 * postBuildClient(config={})
 * postBuild(config={})
 * postUnpack(config={})
 * postLoad()
 * destruct()
 * tagForDOM()
 * createDOMelement(config)
 * applyConfig(config={})
 * static construct(count, config, configurator={})
 *
 * * 2. Common
 * index
 * indexedKey()
 * path()
 * imagePath(name)
 * disabled(bool)
 *
 * * 3. Html and Css
 * tag()
 * attr(name, val)
 * style(name, val)
 * html(content)
 * addClass(...args)
 * removeClass(...args)
 * hasClass(name)
 * toggleClass(class1, class2='')
 * toggleClassOnCondition(condition, class1, class2='')
 * clearClasses()
 * setBaseCss(classes)
 * getEnabledClass()
 * getDisabledClass()
 * getBaseCss()
 * opacity(val)
 * fill(color)
 * overflow(val)
 * picture(pic)
 * border( info )
 * roundCorners(val)
 * rotate(angle)
 * scrollTo(adr)
 * scrollPos()
 * visibility(vis)
 * show()
 * hide()
 * toggleVisibility()
 * setDOMElement(DOMelement)
 *
 * * 4. Geometry
 * getInnerSize(param)
 * setGeomParam(param, val)
 * reportSizeChange(param)
 * getLeft(format)
 * left(val)
 * getRight(format)
 * right(val)
 * getTop(format)
 * top(val)
 * getBottom(format)
 * bottom(val)
 * getWidth(format)
 * width(val)
 * getHeight(format)
 * height(val)
 * coords(l, t)
 * size(w, h)
 * copyGeom(el)
 * geomPriority(param)
 * geomPriorityH(val, val2)
 * geomPriorityV(val, val2)
 * geomPart(val, unit, direction)
 * calcGeomParam(format, val, thisSize, parentSize)
 * rect(format='px')
 * globalRect()
 * containPoint(x, y)
 * isOutOfVisibility(elem=null)
 * parentScreenParams()
 * isOutOfParentScreen()
 * returnToParentScreen()
 * isDisplay()
 * locateBy(elem, align, step)
 * satelliteTo(elem)
 *
 * * 5. Environment managment
 * setParent(config = null)
 * dropParent()
 * after(el)
 * before(el)
 * del()
 * setField(name, func)
 *
 * * 6. Environment navigation
 * nextSibling()
 * prevSibling()
 * ancestor(info={})
 * hasAncestor(box)
 * parentBlock()
 * rootBlock()
 * neighbor(key)
 * getModule()
 *
 * * 7. Events
 * on(eventName, func, useCapture)
 * off(eventName, func, useCapture)
 * trigger(eventName, ...args)
 * hasTrigger(type, func)
 * move(config={})
 * click(func)
 * blur(func)
 * display(func)
 * displayIn(func)
 * displayOut(func)
 * displayOnce(func)
 * triggerDisplay(event)
 * copyEvents(el)
 * static actualizeScreenMode()
 *
 * * 8. Debugging
 * log(msg)
 *
 * * 9. Load
 * findFunction(handler)
 * unpackFunction(str)
 * unpackGeom()
 * unpackScreenDependencies()
 * unpackProperties(loaderContext)
 * static ajax(method, params = [], handlers = null)
 */

/**
 * @group {i18n:widgets}
 * */
class Rect #lx:namespace lx {

	//=========================================================================================================================
	/* 1. Constructor */
	constructor(config = {}) {
		this.__construct();

		// false явно передается при восстановлении при загрузке, чтобы не строить сам элемент - он уже построен на сервере
		if (config === false) return;

		// строительство всего элемента на клиенте
		config = this.preBuild(config);
		this.createDOMelement(config);
		this.applyConfig(config);
		this.build(config);
		if (!config.postBuildOff) {
			this.postBuildClient(config);
			this.postBuild(config);
		}
	}

	/**
	 * Восстановление при загрузке
	 * */
	static rise(DOMelem) {
		var el = new this(false);
		el.DOMelem = DOMelem;
		DOMelem.lx = el;
		var data = DOMelem.getAttribute('lx-data');
		if (data) {
			var arr = data.split(/\s*,\s*/);
			arr.each((pare)=> {
				pare = pare.split(/\s*:\s*/);
				if (!(pare[0] in el)) el[pare[0]] = pare[1];
			});
		}
		return el;
	}

	/**
	 * Конструирование необходимых полей сущности
	 * */
	__construct() {
		this.DOMelem = null;
		this.parent = null;
		this._disabled = false;
	}

	/**
	 * Метод, вызываемый в конструкторе и модифицирующий конфиг конструирования до начала непосредственного конструирования
	 * Логика для создания элемнта на клиенте
	 * */
	preBuild(config) {
		return config;
	}

	/**
	 * Строительство элемента на клиенте
	 * */
	build(config={}) {
	}

	/**
	 * Метод, вызываемый в конструкторе для выполенния действий, когда сущность действительно построена, связи (с родителем) выстроены
	 * Логика только для клиента
	 * */
	postBuildClient(config={}) {
	}

	/**
	 * Метод, вызываемый в конструкторе для выполенния действий, когда сущность действительно построена, связи (с родителем) выстроены
	 * Логика общая для создания элемента и на клиенте и для допиливания при получении от сервера
	 * */
	postBuild(config={}) {
		this.on('scroll', lx.checkDisplay);
		if (!config) return;
		if (config.disabled !== undefined) this.disabled(config.disabled);
	}

	/**
	 * Метод чисто для допиливания виджета при получении данных от сервера
	 * */
	postUnpack(config={}) {
	}

	/**
	 * Метод, вызываемый загрузчиком для допиливания элемента
	 * */
	postLoad() {
		var config = this.lxExtract('__postBuild') || {};
		this.postBuild(config);
		this.postUnpack(config);
	}

	/**
	 * Метод для освобождения ресурсов
	 * */
	destruct() {
	}

	/**
	 * Тэг класса
	 * */
	tagForDOM() {
		return 'div';
	}

	/**
	 * Непосредственно создание DOM-элемента, выстраивание связи с родителем
	 * */
	createDOMelement(config) {
		if (this.DOMelem) {
			this.log('already exists');
			return false;
		}

		var tag = config.tag || this.tagForDOM();
		this.DOMelem = document.createElement(tag);
		this.DOMelem.lx = this;

		this.addClass('lx');

		if (config.key) this.key = config.key;
		else if (config.field) this.key = config.field;

		// this.key = config.key || config.field || 'e' + lx.Math.randomInteger(1, 1000); //lx.getKey();

		// Если родителя нет - геометрические параметры из конфига надо обработать
		// if (!this.setParent(config)) (new PositioningStrategy()).allocate(this, config);
		//todo решено что если родитель явно указан как null - это массовая вставка и раньше времени делать allocate нет смысла
		this.setParent(config);
	}

	/**
	 *	field
	 *	html
	 *	baseCss
	 *	cssClass
	 *	style
	 *	click
	 *	move | parentResize | parentMove
	 * */
 	applyConfig(config={}) {
		if (this.DOMelem === null) return this;

		if (config.field) this._field = config.field;
		if (config.html) this.html(config.html);

		if (config.baseCss !== undefined) this.setBaseCss(config.baseCss);
		else this.addClass( this.getBaseCss() );

		if (config.css !== undefined) this.addClass(config.css);

		if (config.style) {
			for (var i in config.style) {
				if (i in this) {
					if ( config.style[i].isArray )
						this[i].apply( this, config.style[i] );
					else 
						this[i].call( this, config.style[i] );
				} else {
					if ( config.style[i].isArray )
						this.style.apply( this, i, config.style[i] );
					else 
						this.style.call( this, i, config.style[i] );
				}
			}
		}

		if (config.picture) this.picture(config.picture);

		if (config.click) this.click( config.click );
		if (config.blur) this.blur( config.blur );

		if (config.move) this.move(config.move);
		else if (config.parentResize) this.move({parentResize: true});
		else if (config.parentMove) this.move({parentMove: true});

		return this;
	}

	/**
	 * Статический метод для массового создания сущностей
	 * */
	static construct(count, config, configurator={}) {
		var c = new lx.Collection();

		// Оптимизация массовой вставки в родителя
		var parent = null,
			next = null;
		if (config.before) {
			next = config.before;
			parent = next.parent;
			delete config.before;
		} else if (config.after) {
			parent = config.after.parent;
			next = config.after.nextSibling();
			delete config.after;
		} else if (config.parent) parent = config.parent;
		if (parent === null && config.parent !== null)
			parent = lx.WidgetHelper.autoParent;

		// Костыль, чтобы вызвать постконстракт позже - когда родитель уже появится
		config.postBuildOff = true;
		config.parent = null;
		for (var i=0; i<count; i++) {
			var modifArgs = config;
			if (configurator.preBuild) modifArgs = configurator.preBuild.call(null, modifArgs, i);
			var obj = new this(modifArgs);
			c.add(obj);
		};

		// Массовая вставка в родителя
		if (next) config.before = next;
		if (parent) parent.insert(c, config);

		// Постконструкторы
		c.each((a, i)=> {
			a.postBuildClient(config);
			a.postBuild(config);
			if (configurator.postBuild) configurator.postBuild.call(null, a, i);
		});

		return c;
	}
	/* 1. Constructor */
	//=========================================================================================================================


	//=========================================================================================================================
	/* 2. Common */
	get index() {
		if (this._index === undefined) return 0;
		return this._index;
	}

	/**
	 * Ключ с учетом индексации (если она есть) - уникальное значение в пределах родителя
	 * */
	indexedKey() {
		if (this._index === undefined) return this.key;
		return this.key + '[' + this._index + ']';
	}

	/**
	 * Строка, показывающая расположение в иерархии элементов - перечисление ключей всех родительских элементов через /
	 * */
	path() {
		var arr = [],
			temp = this,
			path = '';

		while (temp) {
			arr.push( temp.indexedKey() );
			temp = temp.parent;
		}

		path = arr[arr.length-1];
		for (var i=arr.length-2; i>=0; i--)
			path += '/' + arr[i];

		return path;
	}

	/**
	 * Путь для запроса изображений (идея единого хранилища изображений в рамках модуля)
	 * */
	imagePath(name) {
		var module = this.getModule();
		return module.images + '/' + name;
	}

	/**
	 * Управление активностью элемента
	 * */
	disabled(bool) {
		if (bool === undefined) return !!this._disabled;

		if (bool) this.DOMelem.setAttribute('disabled', true);
		else this.DOMelem.removeAttribute('disabled');

		this.removeClass( this.getBaseCss() );
		this._disabled = bool;
		this.addClass( this.getBaseCss() );

		return this;
	}

	/* 2. Common */
	//=========================================================================================================================


	//=========================================================================================================================
	/* 3. Html and Css */
	tag() {
		return this.DOMelem.tagName;
	}

	attr(name, val) {
		if (val === undefined) return this.DOMelem[name];

		if (val === null) {
			this.DOMelem.removeAttribute(name);
			return this;
		}

		this.DOMelem[name] = val;
		return this;
	}

	style(name, val) {
		if (name == undefined) return this.DOMelem.style;

		if (name.isObject) {
			for (var i in name) this.style(i, name[i]);
			return this;
		}

		if (val == undefined) return this.DOMelem.style[name];

		this.DOMelem.style[name] = val;
		return this;
	}

	html(content) {
		if (content == undefined)
			return this.DOMelem.innerHTML;

		this.DOMelem.innerHTML = content;
		return this;
	}

	/**
	 * Можно передавать аргументы двумя путями:
	 * 1. elem.addClass(class1, class2);
	 * 2. elem.addClass([class1, class2]);
	 * */
	addClass(...args) {
		if (args[0].isArray) args = args[0];

		// Fuck IE
		// if (lx.browserInfo.browser == 'ie') {
		// 	var newClass = args[0];
		// 	for (var i=1, l=args.length; i<l; i++) {
		// 		newClass += ' ' + args[i];
		// 	}
		// 	var arr = this.DOMelem.className.split(' '),
		// 		index = arr.indexOf(newClass);
		// 	if (index != -1) return this;

		// 	if (this.DOMelem.className == '') this.DOMelem.className = newClass;
		// 	else this.DOMelem.className += ' ' + newClass;
		// 	return this;
		// }
		args.each((name)=> {
			if (name == '') return;
			this.DOMelem.classList.add(name);
		});
		return this;
	}

	/**
	 * Можно передавать аргументы двумя путями:
	 * 1. elem.removeClass(class1, class2);
	 * 2. elem.removeClass([class1, class2]);
	 * */
	removeClass(...args) {
		if (args[0].isArray) args = args[0];

		// Fuck IE
		// if (lx.browserInfo.browser == 'ie') {

		// 	var arr = this.DOMelem.className.split(' '),
		// 		index = arr.indexOf(className);
		// 	if (index != -1) {
		// 		arr.splice(index, 1);
		// 		this.DOMelem.className = arr.join(' ');
		// 	}
		// 	return this;
		// }
		args.each((name)=> {
			if (name == '') return;
			this.DOMelem.classList.remove(name);
		});
		return this;
	}

	/**
	 * Проверить имеет ли элемент css-класс
	 * */
	hasClass(name) {
		this.DOMelem.classList.contains(name);
	}

	/**
	 * Если элемент имеет один из классов, то он будет заменен на второй
	 * Если передан только один класс - он будет установлен, если его не было, либо убран, если он был у элемента
	 * */
	toggleClass(class1, class2='') {
		if (this.hasClass(class1)) {
			this.removeClass(class1);
			this.addClass(class2);
		} else {
			this.addClass(class1);
			this.removeClass(class2);
		}
	}

	/**
	 * Если condition==true - первый класс применяется, второй убирается
	 * Если condition==false - второй класс применяется, первый убирается
	 * */
	toggleClassOnCondition(condition, class1, class2='') {
		if (condition) {
			this.addClass(class1);
			this.removeClass(class2);
		} else {
			this.removeClass(class1);
			this.addClass(class2);
		}
	}

	/**
	 * Убрать все классы (кроме .lx)
	 * */
	clearClasses() {
		this.DOMelem.className = 'lx';
		return this;
	}

	/**
	 * classes = String | {
	 *	enabled: String,
	 *	disabled: String
	 * }
	 * */
	setBaseCss(classes) {
		if (this.css === undefined) this.css = {};

		var enabled = (classes.enabled) ? classes.enabled : (classes.isString ? classes : null),
			disabled = (classes.disabled) ? classes.disabled : null;

		if (enabled !== null) {
			this.removeClass( this.getEnabledClass() );
			this.removeClass( this.getDisabledClass() );
			this.css.enabled = enabled;
		}

		if (disabled !== null) {
			this.removeClass( this.getDisabledClass() );
			this.css.disabled = disabled;
		}

		this.addClass( this.getBaseCss() );

		return this;
	}

	/**
	 * Если .css присутствует, но пустой, классы будут пустыми строками
	 * */
	getEnabledClass() {
		if (this.css !== undefined) return this.css.enabled || '';
		return 'lx-' + this.lxClassName;
	}

	getDisabledClass() {
		if (this.css && this.css.disabled) return this.css.disabled;
		return 'lx-' + this.lxClassName + '-disabled';
	}

	getBaseCss() {
		return this.disabled()
			? this.getDisabledClass()
			: this.getEnabledClass();
	}

	opacity(val) {
		if (val != undefined) {
			this.style('opacity', val);
			return this;
		}
		return this.style('opacity');
	}

	fill(color) {
		this.style('backgroundColor', color);
		return this;
	}

	overflow(val) {
		this.style('overflow', val);
		// if (val == 'auto') this.on('scroll', lx.checkDisplay);
		return this;
	}

	picture(pic) {
		if (pic === '' || pic === null) {
			this.DOMelem.style.backgroundImage = 'url("")';
		} else if (pic) {
			if (pic.isString) pic = this.getModule().images + '/' + pic;
			else if (pic.isObject && pic.src) {
				pic = pic.src;
			} else return false;
			this.DOMelem.style.backgroundImage = 'url("' + pic + '")';
			this.DOMelem.style.backgroundRepeat = 'no-repeat';
			this.DOMelem.style.backgroundSize = '100% 100%';
			return this;
		}
		return this.DOMelem.style.backgroundImage.split('"')[1];
	}

	border( info ) {  // info = {width, color, style, side}
		if (info == undefined) info = {};
		var width = ( (info.width != undefined) ? info.width: 1 ) + 'px',
			color = (info.color != undefined) ? info.color: '#000000',
			style = (info.style != undefined) ? info.style: 'solid',
			sides = (info.side != undefined) ? info.side: 'ltrb',
			side = [false, false, false, false],
			sideName = ['Left', 'Top', 'Right', 'Bottom'];
		side[0] = (sides.search('l') != -1);
		side[1] = (sides.search('t') != -1);
		side[2] = (sides.search('r') != -1);
		side[3] = (sides.search('b') != -1);

		if (side[0] && side[1] && side[2] && side[3]) {
			this.DOMelem.style.borderWidth = width;
			this.DOMelem.style.borderColor = color;
			this.DOMelem.style.borderStyle = style;
		} else {
			for (var i=0; i<4; i++) if (side[i]) {
				this.DOMelem.style[ 'border' + sideName[i] + 'Width' ] = width;
				this.DOMelem.style[ 'border' + sideName[i] + 'Color' ] = color;
				this.DOMelem.style[ 'border' + sideName[i] + 'Style' ] = style;
			}
		}
		this.trigger('resize');
		return this;
	}

	/**
	 * Варианты аргумента val:
	 * 1. Число - скругление по всем углам в пикселях
	 * 2. Объект: {
	 *     side: string    // указание углов для скругления в виде 'tlbr'
	 *     value: integer  // скругление по всем углам в пикселях
	 * }
	 * */
	roundCorners(val) {
		var arr = [];
		if (val.isObject) {
			var t = false, b = false, l = false, r = false;
			if ( val.side.indexOf('tl') != -1 ) { t = true; l = true; arr.push('TopLeft'); }
			if ( val.side.indexOf('tr') != -1 ) { t = true; r = true; arr.push('TopRight'); }
			if ( val.side.indexOf('bl') != -1 ) { b = true; l = true; arr.push('BottomLeft'); }
			if ( val.side.indexOf('br') != -1 ) { b = true; r = true; arr.push('BottomRight'); }
			if ( !t && val.side.indexOf('t') != -1 ) { arr.push('TopLeft'); arr.push('TopRight'); }
			if ( !b && val.side.indexOf('b') != -1 ) { arr.push('BottomLeft'); arr.push('BottomRight'); }
			if ( !l && val.side.indexOf('l') != -1 ) { arr.push('TopLeft'); arr.push('BottomLeft'); }
			if ( !r && val.side.indexOf('r') != -1 ) { arr.push('TopRight'); arr.push('BottomRight'); }
			val = val.value;
		}
		if (val.isNumber) val += 'px';

		if (!arr.length) this.DOMelem.style.borderRadius = val;

		for (var i=0; i<arr.length; i++)
			this.DOMelem.style['border' + arr[i] + 'Radius'] = val;

		return this;
	}

	rotate(angle) {
		this.DOMelem.style.mozTransform = 'rotate(' + angle + 'deg)';    // Для Firefox
		this.DOMelem.style.msTransform = 'rotate(' + angle + 'deg)';     // Для IE
		this.DOMelem.style.webkitTransform = 'rotate(' + angle + 'deg)'; // Для Safari, Chrome, iOS
		this.DOMelem.style.oTransform = 'rotate(' + angle + 'deg)';      // Для Opera
		this.DOMelem.style.transform = 'rotate(' + angle + 'deg)';
		return this;
	}

	scrollTo(adr) {
		if (adr.isObject) {
			if (adr.x !== undefined) this.DOMelem.scrollLeft = +adr.x;
			if (adr.y !== undefined) this.DOMelem.scrollTop = +adr.y;
		} else this.DOMelem.scrollTop = adr;
		this.trigger('scroll');
		return this;
	}

	scrollPos() {
		return {
			x: this.DOMelem.scrollLeft,
			y: this.DOMelem.scrollTop
		};
	}

	visibility(vis) {
		if (vis != undefined) { vis ? this.show(): this.hide(); return this; }
		if ( !this.DOMelem.style.visibility || this.DOMelem.style.visibility == 'inherit' ) {
			var p = this.parent;
			while (p) { if (p.DOMelem.style.visibility == 'hidden') return false; p = p.parent; }
			return true;
		} else return (this.DOMelem.style.visibility != 'hidden')
	}

	show() {
		this.DOMelem.style.visibility = 'inherit';
		lx.checkDisplay.call(this);
		return this;
	}

	hide() {
		this.DOMelem.style.visibility = 'hidden';
		lx.checkDisplay.call(this);
		return this;
	}

	toggleVisibility() {
		if (this.visibility()) this.hide();
		else this.show();
	}

	setDOMElement(DOMelement) {
		this.del();
		this.DOMelem = DOMelement;
		return this;
	}
	/* 3. Html and Css */
	//=========================================================================================================================


	//=========================================================================================================================
	/* 4. Geometry */
	/**
	 * Размер без рамок, полос прокрутки и т.п.
	 * */
	getInnerSize(param) {
		if (param === undefined) return [
			this.DOMelem.clientWidth,
			this.DOMelem.clientHeight
		];
		if (param == lx.HEIGHT) return this.DOMelem.clientHeight;
		if (param == lx.WIDTH) return this.DOMelem.clientWidth;
	}

	/**
	 * Установка значения геометрическому параметру с учетом проверки родительской стратегией позиционирования
	 * */
	setGeomParam(param, val) {
		/*
		todo
		тут не актуализируется стратегия позиционирования для потомков - отсюда она и не может, потому что Rect не имеет потомков
		надо сделать этот метод шалонным - оператор this.parent.tryChildReposition(this, param, val); вынести в отдельный метод,
		и переопределить его в Box, чтобы там актуализировать стратегию
		+ добавить метод Box.positioningAutoActualizeState(boolean) - чтобы включать/выключать/проверять режим авто-актуализации
		+ добавить в метод Rect.coords поддержку передачи параметров конфигом, передачу 4-х методов (l, t, r, b)
		+ методы Rect.coords и Rect.size переопределить в Box с выключением автоактуализации
		+ добавить метод Rect.geom(l, t, w, h, r, b) с возможностью передачи параметров конфигом + переопределить его в Box с выключением автоактуализации
		Для всего этого надо перепроверить как работают все стратегии во всех вариантах, продумать их взаимодействия
		и, возможно, прижется сделать метод аналогичный этому, но без актуализации - для использования родительской стратегией
		*/
		if (this.parent) this.parent.tryChildReposition(this, param, val);
		else {
			this.geomPriority(param);
			this.DOMelem.style[lx.Geom.geomName(param)] = val;
		}
		return this;
	}

	/**
	 * Если в силу внутренних процессов изменился размер - о таком надо сообщить "наверх" по иерархии
	 * */
	reportSizeChange(param) {
		if (this.parent) this.parent.childHasAutoresized(this);
	}

	/**
	 * format = '%' | 'px'  - вернет значение float, в соответствии с переданным форматом
	 * если format не задан - вернет значение как оно записано в style
	 * */
	getLeft(format) {
		if (!this.DOMelem) return null;
		if (!format) return this.DOMelem.style.left ? this.DOMelem.style.left : null;

		var pw = (this.parent) ? this.parent.DOMelem.offsetWidth : this.DOMelem.offsetWidth;
		return this.calcGeomParam(format, this.DOMelem.style.left, this.DOMelem.offsetLeft, pw);
	}

	left(val) {
		if (val === undefined || val == '%' || val == 'px')
			return this.getLeft(val);
		return this.setGeomParam(lx.LEFT, val);
	}

	getRight(format) {
		if (!this.DOMelem) return null;
		if (!format) return this.DOMelem.style.right ? this.DOMelem.style.right : null;

		if (this.DOMelem.style.right != '') {
			var b = lx.Geom.splitGeomValue(this.DOMelem.style.right);
			if (format == '%') {
				if ( b[1] != '%' ) b[0] = (b[0] / this.parent.DOMelem.offsetWidth) * 100;
				return b[0];
			} else {
				if ( b[1] != 'px' ) b[0] = b[0] * this.parent.DOMelem.offsetWidth * 0.01;
				return b[0];
			}
		} else {
			var p = this.parent;
			if (!p) return null;
			var t = lx.Geom.splitGeomValue(this.DOMelem.style.left),
				h = lx.Geom.splitGeomValue(this.DOMelem.style.width),
				pw = p.DOMelem.offsetWidth;
			if (format == '%') {
				if ( t[1] != '%' ) t[0] = (t[0] / pw) * 100;
				if ( h[1] != '%' ) h[0] = (h[0] / pw) * 100;
				return 100 - t[0] - h[0];
			} else {
				if ( t[1] != 'px' ) t[0] = this.DOMelem.offsetLeft;
				if ( h[1] != 'px' ) h[0] = this.DOMelem.offsetWidth;
				return pw - t[0] - h[0];
			}
		}
	}

	right(val) {
		if (val === undefined || val == '%' || val == 'px')
			return this.getRight(val);
		return this.setGeomParam(lx.RIGHT, val);
	}

	getTop(format) {
		if (!this.DOMelem) return null;
		if (!format) return this.DOMelem.style.top ? this.DOMelem.style.top : null;

		var p = this.parent,
			ph = (p) ? p.DOMelem.offsetHeight : this.DOMelem.offsetHeight;
		return this.calcGeomParam(format, this.DOMelem.style.top,
			this.DOMelem.offsetTop, ph);
	}

	top(val) {
		if (val === undefined || val == '%' || val == 'px')
			return this.getTop(val);
		return this.setGeomParam(lx.TOP, val);
	}

	getBottom(format) {
		if (!this.DOMelem) return null;
		if (!format) return this.DOMelem.style.bottom ? this.DOMelem.style.bottom : null;

		if ( this.DOMelem == null ) return null;
		if (this.DOMelem.style.bottom != '') {
			var b = lx.Geom.splitGeomValue(this.DOMelem.style.bottom);
			if (format == '%') {
				if ( b[1] != '%' ) b[0] = (b[0] / this.parent.DOMelem.clientHeight) * 100;
				return b[0];
			} else {
				if ( b[1] != 'px' ) b[0] = b[0] * this.parent.DOMelem.clientHeight * 0.01;
				return b[0];
			}
		} else {
			var p = this.parent;
			if (!p) return null;
			var t = lx.Geom.splitGeomValue(this.DOMelem.style.top),
				h = lx.Geom.splitGeomValue(this.DOMelem.style.height),
				ph = p.DOMelem.clientHeight;
			if (format == '%') {
				if ( t[1] != '%' ) t[0] = (t[0] / ph) * 100;
				if ( h[1] != '%' ) h[0] = (h[0] / ph) * 100;
				return 100 - t[0] - h[0];
			} else {
				if ( t[1] != 'px' ) t[0] = this.DOMelem.offsetTop;
				if ( h[1] != 'px' ) h[0] = this.DOMelem.offsetHeight;
				return ph - t[0] - h[0];
			}
		}
	}

	bottom(val) {
		if (val === undefined || val == '%' || val == 'px')
			return this.getBottom(val);
		return this.setGeomParam(lx.BOTTOM, val);
	}

	getWidth(format) {
		if (!this.DOMelem) return null;
		if (!format) return this.DOMelem.style.width ? this.DOMelem.style.width : null;

		if (!this.parent) {
			if (format == '%') return 100;
			return this.DOMelem.offsetWidth;
		}
		return this.calcGeomParam(format, this.DOMelem.style.width,
			this.DOMelem.offsetWidth, this.parent.DOMelem.offsetWidth);
	}

	width(val) {
		if (val === undefined || val == '%' || val == 'px')
			return this.getWidth(val);
		return this.setGeomParam(lx.WIDTH, val);
	}

	getHeight(format) {
		if (!this.DOMelem) return null;
		if (!format) return this.DOMelem.style.height ? this.DOMelem.style.height : null;

		if (!this.parent) {
			if (format == '%') return 100;
			return this.DOMelem.offsetHeight;
		}
		return this.calcGeomParam(format, this.DOMelem.style.height,
			this.DOMelem.offsetHeight, this.parent.DOMelem.offsetHeight);
	}

	height(val) {
		if (val === undefined || val == '%' || val == 'px')
			return this.getHeight(val);
		return this.setGeomParam(lx.HEIGHT, val);
	}

	coords(l, t) {
		if (l === undefined) return [ this.left(), this.top() ];
		if (t === undefined) return [ this.left(l), this.top(l) ];
		this.left(l);
		this.top(t);
		return this;
	}

	size(w, h) {
		if (w === undefined) return [ this.width(), this.height() ];
		if (h === undefined) return [ this.width(w), this.height(w) ];
		this.width(w);
		this.height(h);
		return this;
	}

	/**
	 * Копия "как есть" - с приоритетами, без адаптаций под старые соответствующие значения
	 * Стратегия позиционирования может не позволить некоторые изменения
	 * */
	copyGeom(el) {
		var pH = el.geomPriorityH(),
			pV = el.geomPriorityV();
		this.setGeomParam(pH[1], el[lx.Geom.geomName(pH[1])]());
		this.setGeomParam(pH[0], el[lx.Geom.geomName(pH[0])]());
		this.setGeomParam(pV[1], el[lx.Geom.geomName(pV[1])]());
		this.setGeomParam(pV[0], el[lx.Geom.geomName(pV[0])]());
		if (el.geom) this.geom = el.geom.lxCopy();
		return this;
	}

	geomPriority(param) {
		if (lx.Geom.directionByGeom(param) == lx.HORIZONTAL)
			this.geomPriorityH(param);
		else this.geomPriorityV(param);
	}

	geomPriorityH(val, val2) {
		if (val === undefined) return ((this.geom) ? this.geom.bpg : 0) || [lx.LEFT, lx.CENTER];

		if (val2 !== undefined) {
			if (!this.geom) this.geom = {};
			var dropGeom = this.geomPriorityH().diff([val, val2])[0];
			if (dropGeom === undefined) return this;
			this.DOMelem.style[lx.Geom.geomName(dropGeom)] = '';
			this.geom.bpg = [val, val2];
			return this;
		}

		if (!this.geom) this.geom = {};

		if (!this.geom.bpg) this.geom.bpg = [lx.LEFT, lx.CENTER];

		if (this.geom.bpg[0] == val) return this;

		if (this.geom.bpg[1] != val) switch (this.geom.bpg[1]) {
			case lx.LEFT: this.DOMelem.style.left = ''; break;
			case lx.CENTER: this.DOMelem.style.width = ''; break;
			case lx.RIGHT: this.DOMelem.style.right = ''; break;
		}

		this.geom.bpg[1] = this.geom.bpg[0];
		this.geom.bpg[0] = val;

		return this;
	}

	geomPriorityV(val, val2) {
		if (val === undefined) return ((this.geom) ? this.geom.bpv : 0) || [lx.TOP, lx.MIDDLE];

		if (val2 !== undefined) {
			if (!this.geom) this.geom = {};
			var dropGeom = this.geomPriorityV().diff([val, val2])[0];
			if (dropGeom === undefined) return this;
			this.DOMelem.style[lx.Geom.geomName(dropGeom)] = '';
			this.geom.bpv = [val, val2];
			return this;
		}

		if (!this.geom) this.geom = {};
		if (!this.geom.bpv) this.geom.bpv = [lx.TOP, lx.MIDDLE];

		if (this.geom.bpv[0] == val) return this;

		if (this.geom.bpv[1] != val) switch (this.geom.bpv[1]) {
			case lx.TOP: this.DOMelem.style.top = ''; break;
			case lx.MIDDLE: this.DOMelem.style.height = ''; break;
			case lx.BOTTOM: this.DOMelem.style.bottom = ''; break;
		}

		this.geom.bpv[1] = this.geom.bpv[0];
		this.geom.bpv[0] = val;

		return this;
	}

	/**
	 * Вычисляет процентное или пиксельное представление размера, переданного в любом формате, с указанием направления - длина или высота
	 * Пример:
	 * elem.geomPart('50%', 'px', VERTICAL)  - вернет половину высоты элемента в пикселах
	 * */
	geomPart(val, unit, direction) {
		if (val.isNumber) return +val;
		if (!val.isString) return NaN;

		var num = parseFloat(val),
			baseUnit = val.split(num)[1];

		if (baseUnit == unit) return num;

		if (unit == '%') {
			return (direction == lx.HORIZONTAL)
				? (num * 100) / this.width('px')
				: (num * 100) / this.height('px');
		}

		if (unit == 'px') {
			return (direction == lx.HORIZONTAL)
				? num * this.width('px') * 0.01
				: num * this.height('px') * 0.01;
		}

		return NaN;
	}

	/**
	 * Расчет для возврата значения в нужном формате
	 * val - как значение записано в стиле
	 * thisSize - размер самого элемента в пикселях
	 * parentSize - размер родительского элемента в пикселях
	 * */
	calcGeomParam(format, val, thisSize, parentSize) {
		if (format == 'px') return thisSize;

		if (val == null) return null;

		if ( val.charAt( val.length - 1 ) == '%' ) {
			if (format == '%') return parseFloat(val);
			return thisSize;
		}

		return ( thisSize * 100 ) / parentSize;
	}

	rect(format='px') {
		var l = this.left(format),
			t = this.top(format),
			w = this.width(format),
			h = this.height(format);
		return {
			left: l,
			top: t,
			width: w,
			height: h,
			right: l + w,
			bottom: t + h
		}
	}

	globalRect() {
		var rect = this.DOMelem.getBoundingClientRect();
		return {
			top: rect.top,
			left: rect.left,
			width: rect.width,
			height: rect.height,
			bottom: window.screen.availHeight - rect.bottom,
			right: window.screen.availWidth - rect.right
		}
	}

	containPoint(x, y) {
		var rect = this.globalRect();
		return ( x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom );
	}

	/**
	 * Провека не выходит ли элемент за пределы видимости вверх по иерархии родителей
	 * Родитель, с которого надо начать проверять может быть передан явно (н-р если непосредственный родитель заведомо содержит данный элемент вне своей геометрии)
	 * */
	isOutOfVisibility(elem=null) {
		if (elem === null) elem = this.parent;

		var result = {},
			rect = this.globalRect(),
			l = rect.left,
			r = rect.left + rect.width,
			t = rect.top,
			b = rect.top + rect.height,
			p = elem;


		while (p) {
			var pRect = p.globalRect(),
				pL = pRect.left,
				pR = pRect.left + pRect.width + p.DOMelem.clientWidth - p.DOMelem.offsetWidth,
				pT = pRect.top,
				pB = pRect.top + pRect.height + p.DOMelem.clientHeight - p.DOMelem.offsetHeight;

			if (l < pL) result.left   = pL - l;
			if (r > pR) result.right  = pR - r;
			if (t < pT) result.top    = pT - t;
			if (b > pB) result.bottom = pB - b;

			if (!result.lxEmpty) {
				result.element = p;
				return result;
			}

			p = p.parent;
		}

		return result;
	}

	parentScreenParams() {
		if (!this.parent) {
			var left = window.pageXOffset || document.documentElement.scrollLeft,
				width = window.screen.availWidth,
				right = left + width,
				top = window.pageYOffset || document.documentElement.scrollTop,
				height = window.screen.availHeight,
				bottom = top + height;
			return { left, right, width, height, top, bottom };
		}

		var elem = this.parent,
			left = elem.DOMelem.scrollLeft,
			width = elem.DOMelem.offsetWidth,
			right = left + width,
			top = elem.DOMelem.scrollTop,
			height = elem.DOMelem.offsetHeight,
			bottom = top + height;

		return { left, right, width, height, top, bottom };
	}

	/**
	 * Проверка не выходит ли элемент за пределы своего родителя
	 * */
	isOutOfParentScreen() {
		var p = this.parent,
			rect = this.rect('px'),
			geom = this.parentScreenParams(),
			result = {};

		if (rect.left < geom.left) result.left = geom.left - rect.left;
		if (rect.right > geom.right)  result.right = geom.right - rect.right;
		if (rect.top < geom.top)  result.top = geom.top - rect.top;
		if (rect.bottom > geom.bottom) result.bottom = geom.bottom - rect.bottom;

		if (result.lxEmpty) return false;
		return result;
	}

	returnToParentScreen() {
		var out = this.isOutOfParentScreen();
		if (out.lxEmpty) return this;

		if (out.left && out.right) {
		} else {
			if (out.left) this.left( this.left('px') + out.left + 'px' );
			if (out.right) this.left( this.left('px') + out.right + 'px' );
		}
		if (out.top && out.bottom) {
		} else {
			if (out.top) this.top( this.top('px') + out.top + 'px' );
			if (out.bottom) this.top( this.top('px') + out.bottom + 'px' );
		}
		return this;
	}

	/**
	 * Отображается ли элемент в данный момент
	 * */
	isDisplay() {
		if (!this.visibility()) return false;

		var r = this.globalRect(),
			w = window.screen.availWidth,
			h = window.screen.availHeight;

		if (r.top > h) return false;
		if (r.bottom < 0) return false;
		if (r.left > w) return false;
		if (r.right < 0) return false;
		return true;
	}

	/**
	 * Примеры использования:
	 * 1. small.locateBy(big, lx.RIGHT);
	 * 2. small.locateBy(big, lx.RIGHT, 5);
	 * 3. small.locateBy(big, [lx.RIGHT, lx.BOTTOM]);
	 * 4. small.locateBy(big, {bottom: '20%', right: 20});
	 * */
	locateBy(elem, align, step) {
		if (align.isArray) {
			for (var i=0,l=align.len; i<l; i++) this.locateBy(elem, align[i]);
			return this;
		} else if (align.isObject) {
			for (var i in align) this.locateBy(elem, lx.Geom.alignConst(i), align[i]);
			return this;
		}

		if (!step) step = 0;
		step = elem.geomPart(step, 'px', lx.Geom.directionByGeom(align));
		var rect = elem.globalRect();
		switch (align) {
			case lx.TOP:
				this.geomPriorityV(lx.TOP, lx.MIDDLE);
				this.top( rect.top+step+'px' );
			break;
			case lx.BOTTOM:
				this.geomPriorityV(lx.BOTTOM, lx.MIDDLE);
				this.bottom( rect.bottom+step+'px' );
			break;
			case lx.MIDDLE:
				this.geomPriorityV(lx.TOP, lx.MIDDLE);
				this.top( rect.top + (elem.height('px') - this.height('px')) * 0.5 + step + 'px' );
			break;

			case lx.LEFT:
				this.geomPriorityH(lx.LEFT, lx.CENTER);
				this.left( rect.left+step+'px' );
			break;
			case lx.RIGHT:
				this.geomPriorityH(lx.RIGHT, lx.CENTER);
				this.right( rect.right+step+'px' );
			break;
			case lx.CENTER:
				this.geomPriorityH(lx.LEFT, lx.CENTER);
				this.left( rect.left + (elem.width('px') - this.width('px')) * 0.5 + step + 'px' );
			break;
		};
		return this;
	}

	satelliteTo(elem) {
		this.locateBy(elem, {top: elem.height('px'), center:0});
		if (this.isOutOfParentScreen().bottom)
			this.locateBy(elem, {bottom: elem.height('px'), center:0});
		this.returnToParentScreen();
	}
	/* 4. Geometry */
	//=========================================================================================================================


	//=========================================================================================================================
	/* 5. Environment managment */
	/**
	 * config == Box | {
	 *     parent: Box    // непосредственно родитель, если null - родитель не выставляется
	 *     index: int     // если в родителе по ключу элемента есть группа, можно задать конкретную позицию
	 *     before: Rect   // если в родителе по ключу элемента есть группа, можно его расположить перед указанным элементом из группы
	 *     after: Rect    // если в родителе по ключу элемента есть группа, можно его расположить после указанного элемента из группы
	 * }
	 * */
	setParent(config = null) {
		this.dropParent();

		if (config === null) return null;
		
		var parent = null,
			next = null;
		if (config instanceof lx.Box) parent = config;
		else {
			if (config.parent === null) return null;

			if (config.before && config.before.parent) {
				parent = config.before.parent;
				next = config.before;
			} else if (config.after && config.after.parent) {
				parent = config.after.parent;
				next = config.after.nextSibling();
			} else {
				parent = config.parent || lx.WidgetHelper.autoParent;
				if (!parent) return null;
				if (config.index !== undefined) next = (this.key && this.key in parent.children)
					? parent.children[this.key][config.index]
					: parent.DOMelem.children[config.index].lx;
			}
		}

		config.nextSibling = next;
		parent.addChild(this, config);
		return parent;
	}

	dropParent() {
		if (this.parent) this.parent.del(this);
		return this;
	}

	after(el) {
		return this.setParent({ after: el });
	}

	before(el) {
		return this.setParent({ before: el });
	}

	del() {
		if (this.DOMelem === null) return;
		var p = this.parent;
		if (p) p.del(this);
	}

	/**
	 * Назначить элементу поле, за которым он сможет следить
	 * */
	setField(name, func, type = null) {
		this._field = name;
		this._bindType = type || lx.Binder.BIND_TYPE_FULL;

		if (func) {
			var valFunc = this.lxHasMethod('value')
				? this.value
				: function(val) { if (val===undefined) return this._val; this._val = val; };
			this.innerValue = valFunc;

			// Определяем - может ли переданная функция возвращать значение (кроме как устанавливать)
			var str = func.toString(),
				argName = str[0] == '('
					? str.match(/\((.*?)(?:,|\))/)[1]
					: str.match(/function\s*\((.*?)(?:,|\))/)[1],
				reg = new RegExp('if\\s*\\(\\s*' + argName + '\\s*===\\s*undefined'),
				isCallable = (str.match(reg) !== null);

			/* Метод, через который осуществляется связь с моделью:
			 * - модель отсюда читает значение, когда на виджете триггерится событие 'change'
			 * - модель сюда записывает значение поля, когда оно меняется через сеттер
			 */
			this.value = function(val) {
				if (val === undefined) {
					if (isCallable) return func.call(this);
					return this.innerValue();
				}
				var oldVal = isCallable ? func.call(this) : this.innerValue();
				/* Важно:
				 * - цепочка алгоритма может быть такая:
				 * 1. В пользовательском коде вызван метод .value(val), передано какое-то значение
				 * 2. Значение новое - оно присваивается значению виджета, триггерится событие 'change'
				 * 3. На 'change' срабатывает актуализация модели - в ней через сеттер записывается новое значение
				 * 4. В сеттере модели тоже есть логика актуализации - на этот раз виджета, через метод value(val)
				 * 5. Этот метод будет вызван повторно, чтобы не попасть в рекурсию - при попытке присвоить значние, идентичное текущему,
				 *    ничего не делаем - логично, если значение x "поменялось" на значение x, изменений не произошло - событие 'change' не должно триггериться
				 */
				if (lx.CompareHelper.deepCompare(val, oldVal)) return;
				this.innerValue(val);
				func.call(this, val, oldVal);
				this.trigger('change');
			};
		}

		return this;
	}
	/* 5. Environment managment */
	//=========================================================================================================================


	//=========================================================================================================================
	/* 6. Environment navigation */
	nextSibling() {
		var ns = this.DOMelem.nextSibling;
		if (!ns) return null;
		return ns.lx;
	}

	prevSibling() {
		var ns = this.DOMelem.previousSibling;
		if (!ns) return null;
		return ns.lx;
	}

	/**
	 * Поиск первого ближайшего предка, удовлетворяющего условию из переданной конфигурации:
	 * 1. is - точное соответствие переданному конструктору
	 * 2. hasProperties - имеет свойство(ва), при передаче значений проверяется их соответствие
	 * 3. checkMethods - имеет метод(ы), проверяются возвращаемые ими значения
	 * 4. instance - соответствие инстансу (отличие от 1 - может быть наследником инстанса)
	 * */
	ancestor(info={}) {
		var p = this.parent;
		while (p) {
			if (info.isFunction) {
				if (info(p)) return p;
			} else {
				if (info.is) {
					var instances = info.is.isArray ? info.is : [info.is];
					for (var i=0, l=instances.len; i<l; i++) {
						if (p.constructor === instances[i])
							return p;
					}
				}

				if (info.hasProperties) {
					var prop = info.hasProperties;
					if (prop.isObject) {
						var match = true;
						for (var name in prop)
							if (!(name in p) || prop[name] != p[name]) {
								match = false;
								break;
							}
						if (match) return p;
					} else if (prop in p) return p;
				}

				if (info.checkMethods) {
					var match = true;
					for (var name in info.checkMethods) {
						if (!(name in p) || !p[name].isFunction || p[name]() != info.checkMethods[name]) {
							match = false;
							break;
						}
					}
					if (match) return p;
				}

				if (info.instance && p instanceof info.instance) return p;
			}

			p = p.parent;
		}
		return null;
	}

	/**
	 * Определяет имеет ли элемент данного предка
	 * */
	hasAncestor(box) {
		var temp = this.parent
		while (temp) {
			if (temp === box) return true;
			temp = temp.parent;
		}
		return false;
	}

	/*
	 * Родительский блок для данного виджета
	 * */
	parentBlock() {
		return this.ancestor({hasProperties: 'isBlock'});
	}

	/*
	 * Блок с выходом на модуль, он же корневой блок в данном модуле
	 * */
	rootBlock() {
		if (this.module) return this;
		return this.ancestor({hasProperties: 'module'});
	}

	/**
	 * Поиск подымается по иерархии родителей, вернется первый ближайший найденный элемент
	 * */
	neighbor(key) {
		var parent = this.parent;
		while (parent) {
			var el = parent.get(key);
			if (el) return el;
			parent = parent.parent;
		}
		return null;
	}

	getModule() {
		var root = this.rootBlock();
		if (!root) return null;
		return root.module;
	}
	/* 6. Environment navigation */
	//=========================================================================================================================


	//=========================================================================================================================
	/* 7. Events */
	on(eventName, func, useCapture) {
		// useCapture = useCapture || false;
		// useCapture в ie не обрабатывается, поэтому не реализован

		if (!func) return this;

		//todo
		if (eventName == 'mousedown') this.on('touchstart', func, useCapture);
		else if (eventName == 'mousemove') this.on('touchmove', func, useCapture);
		else if (eventName == 'mouseup' /*|| eventName == 'click'*/) this.on('touchend', func, useCapture);

		if (func.isString)
			func = this.unpackFunction(func);

		if (func) lx.Event.add( this.DOMelem, eventName, func );
		return this;
	}

	off(eventName, func, useCapture) {
		// useCapture = useCapture || false;
		// useCapture в ie не обрабатывается, поэтому не реализован

		lx.Event.remove( this.DOMelem, eventName, func );
		return this;
	}

	trigger(eventName, ...args) {
		if ( this.disabled() || !this.DOMelem.events || !(eventName in this.DOMelem.events) ) return;

		var res;
		if (eventName == 'blur') {
			this.DOMelem.blur();
			res = true;
		} else {
			res = [];
			for (var i in this.DOMelem.events[eventName]) {
				var func = this.DOMelem.events[eventName][i];
				res.push( func.apply(this, args) );
			}
		}

		if (res.isArray && res.length == 1) res = res[0];
		return res;
	}

	hasTrigger(type, func) {
		return lx.Event.has(this.DOMelem, type, func);
	}

	move(config={}) {
		if (config.parentMove && config.parentResize) delete config.parentMove;
		this.moveParams = {
			xMove        : (config.xMove        !== undefined) ? config.xMove : true,
			yMove        : (config.yMove        !== undefined) ? config.yMove : true,
			parentMove   : (config.parentMove   !== undefined) ? config.parentMove : false,
			parentResize : (config.parentResize !== undefined) ? config.parentResize : false,
			xLimit       : (config.xLimit       !== undefined) ? config.xLimit : true,
			yLimit       : (config.yLimit       !== undefined) ? config.yLimit : true,
			moveStep     : (config.moveStep     !== undefined) ? config.moveStep : 1
		};
		this.on('mousedown', lx.move);
		return this;
	}

	click(func) { this.on('click', func); return this; }

	display(func) { this.on('display', func); return this; }

	displayIn(func) { this.on('displayin', func); return this; }

	displayOut(func) { this.on('displayout', func); return this; }

	displayOnce(func) {
		if (func.isString) func = this.unpackFunction(func);
		if (!func) return this;
		var f;
		f = function() {
			func.call(this);
			this.off('displayin', f);
		};
		this.on('displayin', f);
		return this;
	}

	triggerDisplay(event) {
		if (!this.isDisplay()) {
			if (this.displayNow) {
				this.trigger('displayout', event);
				this.displayNow = false;
			}
		} else {
			if (!this.displayNow) {
				this.displayNow = true;
				this.trigger('displayin', event);
			} else this.trigger('display', event);
		}
	}

	copyEvents(el) {
		if (!el) return this;

		this.off();  // ???

		if ( el.DOMelem.events == undefined ) return this;

		for (var i in el.DOMelem.events ) {
			var events = el.DOMelem.events[i];
			for (var j in events) {
				this.on( i, events[j] );
			}
		}

		return this;
	}

	/**
	 * Актуализация при изменении режима отображения
	 * Навешивается на обработчик, так что this - это конкретный объект
	 * */
	static actualizeScreenMode() {
		var module = this.getModule();
		if (this.screenMode == module.screenMode || module.screenMode == '') return;

		this.screenMode = module.screenMode;
		this.screenDependencies[module.screenMode].call(this);
	}
	/* 7. Events */
	//=========================================================================================================================


	//=========================================================================================================================
	/* 8. Debugging */
	log(msg) {
		console.log( 'lx.' + this.path() + ': ' + msg );
	}
	/* 8. Debugging */
	//=========================================================================================================================


	//=========================================================================================================================
	/* 9. Load */
	/**
	 * Если при распаковке хэндлеры обработчиков событий перенаправляют на реальные функции
	 * Варианты аргументов:
	 * - ('.funcName')     // будет искать функцию 'funcName' среди своих методов
	 * - ('::funcName')    // будет искать функцию 'funcName' среди статических методов своего класса
	 * - ('->funcName')    // будет искать функцию 'funcName' в листе функций модуля
	 * - ('lx.funcName')	// будет искать функцию 'funcName' в листе функций lx
	 * */
	findFunction(handler) {
		// '.funcName'
		if (handler.match(/^\./)) {
			var func = this[handler.split('.')[1]];
			if (!func || !func.isFunction) return null;
			return func;
		}

		// '::funcName'
		if (handler.match(/^::/)) {
			var func = lx[this.lxClassName][handler.split('::')[1]];
			if (!func || !func.isFunction) return null;
			return func;
		}

		// '->funcName'
		if (handler.match(/^->/)) {
			return this.getModule().getHandler(handler.split('->')[1]);
		}

		// 'lx.funcName'
		if (handler.match(/^lx\./)) {
			return lx.getHandler(handler.split('.')[1]);
		}

		// Если нет явного префикса - плохо, но попробуем от частного к общему найти хэндлер
		var f = null;
		f = this.findFunction('.' + handler);
		if (f) return f;
		f = this.findFunction('::' + handler);
		if (f) return f;
		f = this.findFunction('->' + handler);
		if (f) return f;
		f = this.findFunction('lx.' + handler);
		if (f) return f;

		return null;
	}

	/**
	 * Формат функции, которую пишем строкой на php:
	 * '(arg1, arg2) => ...function code'
	 * */
	unpackFunction(str) {
		var f = null;
		if (str.match(/^\(.*?\)\s*=>/))
			f = lx.createFunctionByInlineString(str);
		else
			f = this.findFunction(str);
		if (!f) return null;
		return f;

		// return f.bind(this);
	}

	/**
	 * Распаковка приоритетов геометрических параметров
	 * */
	unpackGeom() {
		var val = this.geom,
			arr = val.split('|');
		this.geom = {};
		if (arr[0] != '') {
			var params = arr[0].split(',');
			this.geomPriorityH(+params[0], +params[1]);
		}
		if (arr[1] != '') {
			var params = arr[1].split(',');
			this.geomPriorityV(+params[0], +params[1]);
		}
	}

	/**
	 * Распаковка зависимостей от режимов отображения
	 * */
	unpackScreenDependencies() {
		for (var name in this.screenDependencies) {
			var f = this.unpackFunction(this.screenDependencies[name]);
			if (f) this.screenDependencies[name] = f;
		}

		this.on('resize', self::actualizeScreenMode);
	}

	/**
	 * Распаковка расширенных свойств
	 * */
	unpackProperties(loaderContext) {
		if (!this.inLoad) return;

		if (this.geom && this.geom.isString) this.unpackGeom();

		// Зависимости от режимов отображения
		if (this.screenDependencies) {
			this.unpackScreenDependencies();
			loaderContext.screenDependend.push(this);
		}

		// Стратегии позиционирования
		if (this.__ps) {
			var ps = this.lxExtract('__ps').split(';'),
				psName = ps.shift();
			this.positioningStrategy = new lx[psName](this);
			this.positioningStrategy.unpack(ps);
		}

		// Функции-обработчики событий
		if (this.handlers) {
			for (var i in this.handlers) for (var j in this.handlers[i]) {
				var f = this.unpackFunction(this.handlers[i][j]);
				if (f) this.on(i, f);
			}				

			delete this.handlers;
		}
	}

	/**
	 * @param key - ключ вызываемого метода
	 * @param params - параметры, с которыми нужно вызвать метод
	 * @param handlers - обработчики ответа
	 * */
	static ajax(key, params = [], handlers = null) {
		var config = lx.Dialog.handlersToConfig(handlers);
		config.data = {key, params};

		var headers = [];
		headers['lx-type'] = 'widget';
		headers['lx-widget'] = this.lxFullName;
		config.headers = headers;

		lx.Dialog.post(config);
	}
	/* 9. Load */
	//=========================================================================================================================
}
