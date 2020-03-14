/**
 * Собирает дерево элементов, запускает события элементов
 * Создается рекурсивно для вложенных сниппетов
 */
class SnippetLoader {
	constructor(loadContext, plugin, elem, infoIndex, parentLoader = null) {
		this.loadContext = loadContext;
		this.plugin = plugin;
		this.elem = elem;
		this.info = this.loadContext.getSnippetInfo(this.plugin, infoIndex);

		this.elems = [];
		this.currentElement = 0;
		this.node = new SnippetJsNode(
			this.loadContext,
			this.plugin,
			new lx.Snippet(this.elem, this.info),
			parentLoader ? parentLoader.node : null);
	}

	/**
	 * Основной алгоритм распаковки
	 */
	unpack() {
		if (!this.info) return;

		if (!this.elem.isSnippet)
			this.elem.isSnippet = true;

		if (this.info.html) {
			// Бухнули верстку в div блока
			this.elem.domElem.html(this.info.html);
			// Формирование дерева lx-сущностей
			this.riseTree(this.elem, this.info.lx);
		}

		// Блок принес всякие настройки для элемента, в который он выгружается
		if (this.info.self) this.applySelf(this.info.self);

		// Допилить содержимое
		if (this.elems.length) this.unpackContent();

		// Вызовы js-кода сниппетов
		this.unpackJs(this.info);
	}

	/**
	 * Применить полученные настройки для корневого блока
	 */
	applySelf(info) {
		if (info.attrs)
			for (var i in info.attrs)
				this.elem.domElem.setAttribute(i, info.attrs[i]);

		if (info.classes)
			for (var i=0, l=info.classes.len; i<l; i++)
				this.elem.domElem.addClass(info.classes[i]);

		if (info.style)
			for (var st in info.style) {
				var val = info.style[st],
					stName = st.replace(/-(\w)/g, function(str, p0) {
						return p0.toUpperCase();
					});
				this.elem.domElem.style(stName, val);
			}

		if (info.props)
			for (var prop in info.props)
				if (!(prop in this.elem))
					this.elem[prop] = info.props[prop];

		if (info.funcs)
			for (var name in info.funcs)
				if (!(name in this.elem))
					this.elem[name] = this.elem.unpackFunction(info.funcs[name]);

		this.elem.inLoad = true;
		this.elem.unpackProperties();
		delete this.elem.inLoad;
		this.elem.postUnpack();
	}

	/**
	 * Генерация lx-сущностей и воссоздание между ними родительских связей
	 */
	riseTree(elem, infoLx) {
		for (var i=0,l=elem.getDomElem().children.length; i<l; i++) {
			var node = elem.getDomElem().children[i];

			if (node.getAttribute('lx') === null) continue;

			var info = infoLx[this.currentElement++],
				namespace = info._namespace ? info._namespace : 'lx';

			var namespaceObj = lx.getNamespace(namespace);
			if (!(namespaceObj)) {
				console.error('Widget not found:', namespace + '.' + info.type);
				continue;
			}

			var type = null;

			if (info.type in namespaceObj) type = namespaceObj[info.type];
			else if (namespace == 'lx') type = lx.Box;

			if (type === null) {
				console.error('Widget not found:', namespace + '.' + info.type);
				continue;
			}

			var el = type.rise(node);

			for (var prop in info) {
				if (prop == 'type' || prop == '_namespace' || prop in el) continue;
				el[prop] = info[prop];
			}

			if (info.key) el.key = info.key;

			el.inLoad = true;

			this.elems.push(el);

			el.parent = elem;
			el.domElem.parent = elem;
			elem.registerChild(el);

			this.riseTree(el, infoLx);
		}
	}

	unpackContent() {
		// Некоторые свойства требуют распаковки, н-р стратегии позиционирования, функции на обработчиках событий и т.п.
		for (var i=0, l=this.elems.length; i<l; i++) {
			var el = this.elems[i];
			el.unpackProperties();

			// Сборка вложенных блоков
			if (el.ib) {
				(new SnippetLoader(this.loadContext, this.plugin, el, el.ib, this)).unpack();
				delete el.ib;
			}

			// Пришел плагин, собранный в этот элемент
			if (el.pluginAnchor) {
				var anchor = el.lxExtract('pluginAnchor');
				this.loadContext.createPluginByAnchor(anchor, el, this.plugin);
			}

			delete el.inLoad;
		}

		// Некоторые методы нужно вызвать сразу после распаковки
		for (var i=0, l=this.elems.length; i<l; i++) {
			var el = this.elems[i];

			// постсерверная доработка виджета
			if (lx.unpackType == lx.POSTUNPACK_TYPE_IMMEDIATLY) el.postLoad();
			else if (lx.unpackType == lx.POSTUNPACK_TYPE_FIRST_DISPLAY) el.displayOnce(el.postLoad);
			else if (lx.unpackType == lx.POSTUNPACK_TYPE_ALL_DISPLAY) el.on('displayin', el.postLoad);

			// функции, навешанные на момент загрузки
			this.callOnload(el);
			// если виджет виден - вызов функции на обработчике displayin, в т.ч. displayOnce
			if (el.isDisplay()) el.trigger('displayin');
		}
	}

	unpackJs(info) {
		// Если есть js-код для запуска после загрузки блока
		if (info.js) {
			this.node.js = info.js;
		}
	}

	/**
	 * Вызов функций, навешанных на момент загрузки
	 */
	callOnload(el) {
		var js = el.lxExtract('forOnload');
		if (!js) return;
		for (var i=0; i<js.len; i++) {
			var item = js[i],
				 func,
				 args=null;
			if (item.isArray) {
				func = item[0];
				args = item[1];
			} else func = item;
			func = el.unpackFunction(func);
			if (args === null) func.call(el);
			else {
				if (!args.isArray) args = [args];
				func.apply(el, args);
			}
		}
	}
}
