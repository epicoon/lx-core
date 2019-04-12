#lx:private;

//todo судя по описанию это должен быть тот же commonContext
// Инкапсулированный контекст зарузки, остающийся единым над рекурсивным созданием экземпляров Loader
let loaderContext = {
	screenDependend: []
};

let loadData = {
	isAjax: null,
	modules: {},
	blocksInfo: {},
	blockTrees: {},
	rootKey: '',
	widgetsCode: null,

	necessaryWidgets: null,
	necessaryScripts: null,
	necessaryCss: null,

	reset: function() {
		this.isAjax = null;
		this.modules = {};
		this.blocksInfo = {};
		this.blockTrees = {};
		this.rootKey = '';
		this.widgetsCode = null;
		this.necessaryWidgets = null;
		this.necessaryScripts = null;
		this.necessaryCss = null;
		BlockJsNode.list = {};
		BlockJsNode.counter = 0;
	},

	hasAssets: function() {
		return this.necessaryWidgets
			|| this.necessaryScripts
			|| this.necessaryCss;
	}
};



function parseInfo(info) {
	var modulesInfo;

	// Статика приходит строкой, ajax массивом

	// Это статика - ресурсы собраны на стороне сервера
	if (info.isString) {
		loadData.isAjax = false;
		modulesInfo = info;

		// Достаем код пришедших виджетов
		var matches = modulesInfo.match(/<widgets>([\w\W]*?)<\/widgets>/);
		if (matches) loadData.widgetsCode = matches[1];

	// Это ajax - если ресурсы пришли, их надо собирать здесь
	} else {
		loadData.isAjax = false;
		modulesInfo = info.moduleInfo;

		// Определяем какие ресурсы потребуют отдельной дозагрузки
		if (info.scripts) loadData.necessaryScripts = info.scripts;
		if (info.css) loadData.necessaryCss = info.css;
		if (info.widgets) loadData.necessaryWidgets = lx.widgets.defineNecessary(info.widgets);
	}

	// Парсим инфу по модулям
	var reg = /<module (.+?)>/g,
		match;


	while (match = reg.exec(modulesInfo)) {
		var key = match[1],
			moduleString = modulesInfo.match(new RegExp('<module '+key+'>([\\w\\W]*?)</module '+key+'>'))[1];
		loadData.modules[key] = {
			key,
			info: lx.Json.parse(moduleString.match(new RegExp('<mi '+key+'>([\\w\\W]*?)</mi '+key+'>'))[1]),
			bootstrapJs: moduleString.match(new RegExp('<bs '+key+'>([\\w\\W]*?)</bs '+key+'>'))[1],
			blocks: lx.Json.parse(moduleString.match(new RegExp('<bl '+key+'>([\\w\\W]*?)</bl '+key+'>'))[1]),
			mainJs: moduleString.match(new RegExp('<mj '+key+'>([\\w\\W]*?)</mj '+key+'>'))[1]
		};

		/* Корневой модуль обволакивает вложенные - начинает собираться первым, а заканчивает поледним,
		 * т.о. его ключ идет последним
		 */
		loadData.rootKey = key;
	}
}





function createCssTag(href, key) {
	var link  = document.createElement('link');
	link.rel  = 'stylesheet';
	link.type = 'text/css';
	link.href = href;
	link.setAttribute('name', key);
	return link;
}

/**
 * //todo - Была возможность в контексте модуля запускать
 *   теперь модуль создается после загрузки ресурсов
 *   как было - закоменчен одноименный метод в lx.Module
 *   может быть стоит переделать
 * */
function createScriptTag(src, key) {
	var script = document.createElement('script');
	script.setAttribute('name', key);
	var onSuccess, onError;
	if (src.isArray) {
		onSuccess = src[1];
		onError = src[2];
		src = src[0];
	}
	script.src = src;
	if (onSuccess) script.onload = onSuccess.bind(script);
	if (onError) script.error = onError.bind(script);
	return script;
}



function loadAssets(callback) {
	// Если нет необходимости в загрузке ресурсов
	if (!loadData.hasAssets()) {
		if (loadData.widgetsCode)
			lx.createAndCallFunction('', loadData.widgetsCode);
		callback();
		return;
	}

	// Синхронизируем загрузку ресурсов и старт выполнения модуля
	var synchronizer = new lx.Synchronizer(),
		headScriptTags = [],
		cssTags = [];

	// Запрос на догрузку виджетов регистрируется в синхронайзере
	var widgetsRequest = null;
	if (loadData.necessaryWidgets && !loadData.necessaryWidgets.lxEmpty) {
		widgetsRequest = new lx.Request('get-widgets', loadData.necessaryWidgets);
		widgetsRequest.setHeader('lx-type', 'service');
		widgetsRequest.success = function(result) {
			if (result) lx.createAndCallFunction('', result);
		};
		synchronizer.register(widgetsRequest);
	}

	// script-ресурсы регистрируются в синхронайзере
	if (loadData.necessaryScripts) {
		for (var key in loadData.necessaryScripts) {
			var scripts = loadData.necessaryScripts[key];
			if (scripts.head) {
				for (var i=0; i<scripts.head.len; i++) {
					var tag = createScriptTag(scripts.head[i], key);
					if (!tag) continue;
					synchronizer.register(tag, 'onload', 'onerror');
					headScriptTags.push(tag);
				}
			}
		}
	}

	// css-ресурсы регистрируются в синхронайзере
	if (loadData.necessaryCss) {
		for (var key in loadData.necessaryCss) {
			var css = loadData.necessaryCss[key];
			for (var i=0; i<css.len; i++) {
				var tag = createCssTag(css[i], key);
				if (!tag) continue;
				synchronizer.register(tag, 'onload', 'onerror');
				cssTags.push(tag);
			}
		}
	}

	// Модуль стартанёт после подключения ресурсов
	synchronizer.start(callback);

	// Подтягиваем виджеты
	if (widgetsRequest) widgetsRequest.send();

	// Подключение скриптов
	//todo
	// document.body.insertBefore(tag, bodyDiv);
	// document.body.appendChild(tag);
	if (headScriptTags.len) {
		var head = document.getElementsByTagName('head')[0];
		for (var i=0; i<headScriptTags.len; i++)
			head.appendChild(headScriptTags[i]);
	}
	
	// Подключение стилей
	if (cssTags.len) {
		var head = document.getElementsByTagName('head')[0];
		for (var i=0; i<cssTags.len; i++)
			head.appendChild(cssTags[i]);
	}
}


function createModule(moduleInfo, el, parent, clientCallback) {
	// Создадим экземпляр модуля
	if (!el) {
		lx.body = lx.Box.rise(document.getElementById('lx'));
		lx.body.key = 'body';
		lx.body.on('scroll', lx.checkDisplay);
		el = lx.body;
	}
	var info = moduleInfo.info;
	if (parent) info.parent = parent;
	info.key = moduleInfo.key;
	var m = lx.Module(info, el);

	var bootstrapJs = moduleInfo.bootstrapJs,
		blocks = moduleInfo.blocks,
		mainJs = moduleInfo.mainJs;

	// js-код загрузки модуля
	if (bootstrapJs != '')
		lx.createAndCallFunction('', 'const Module=lx.modules["'+m.key+'"];' + bootstrapJs);

	// Сборка блоков
	loadData.blocksInfo[m.key] = blocks;
	(new BlockBuilder(m, null, el, 0)).unpack();
	
	//todo - с ним явно что-то не то
	// Явно обнулим замыкание после использования
	loaderContext = null;
	// Если учитывается режим отображения
	if (m.screenMode !== undefined) {
		// Текущий режим отображения
		m.screenMode = m.idenfifyScreenMode();
		// Организация реакции на смену режима отображения
		for (var i=0, l=loaderContext.screenDependend.length; i<l; i++) {
			let el = loaderContext.screenDependend[i];
			el.constructor.actualizeScreenMode.call(el);
		}
	}

	// Собираем js-код, выполняемый после сборки модуля
	var argsStr = [];
	var args = [];
	var code = 'const Module=lx.modules["'+m.key+'"];lx.WidgetHelper.autoParent=Module.root;'
	// основной js-код модуля
	if (mainJs) code += mainJs;
	// Добавляем код блоков
	var node = loadData.blockTrees[m.key];
	var blocksJs = node.compileCode();
	code += blocksJs[0];
	// Выполняем получившийся код
	lx.createAndCallFunction(blocksJs[1], code, null, blocksJs[2]);
}

lx.Loader = {
	run: function(info, el, parent, clientCallback) {
		parseInfo(info);

		loadAssets(function() {
			createModule(loadData.modules[loadData.rootKey], el, parent, clientCallback);

			// Вернули контекст в исходное состояние
			loadData.reset();

			if (clientCallback) clientCallback();
		});
	}
};


/**
 * Дерево блоков для сборки пространств имен согласно иерархии
 * */
class BlockJsNode {
	constructor(builder, parentNode = null) {
		this.block = builder.block;
		this.js = null;
		this.params = null;
		this.children = [];
		this.key = 'k' + self::keyCounter++;
		self::list[this.key] = this;
		if (parentNode) parentNode.addChild(this);
		else loadData.blockTrees[builder.module.key] = this;
	}

	empty() {
		return !this.children.len;
	}

	addChild(node) {
		this.children.push(node.key);
	}

	child(i) {
		return self::list[this.children[i]];
	}

	compileCode() {
		var argsStr = [];
		var args = [];

		var counter = 0,
			pre = '(function(Block, clientParams){lx.WidgetHelper.autoParent=Block;',
			post = 'lx.WidgetHelper.popAutoParent();',
			begin = [],
			end = [];

		function rec(node) {
			if (node.js) {
				args.push(node.block);
				args.push(node.params);
				let block = '__lxb_' + counter;
				let params = '__lxp_' + counter;
				counter++;
				argsStr.push(block);
				argsStr.push(params);
				var js = node.js.replace(/([^;])$/, '$1;');
				let head = pre + js + post;
				let tail = '})('+block+','+params+');';
				
				if (node.empty()) begin.push(head + tail);
				else {
					begin.push(head);
					end.push(tail);
				}
			}
			for (var i=0, l=node.children.len; i<l; i++) rec(node.child(i));
		}
		rec(this);

		var code = '';
		for (var i=0, l=begin.len; i<l; i++) code += begin[i];
		for (var i=end.len-1; i>=0; i--) code += end[i];
		return [code, argsStr.join(','), args];
	}
}
BlockJsNode.keyCounter = 0;
BlockJsNode.list = {};


/**
 * Собирает дерево элементов, запускает события элементов
 * Создается рекурсивно для вложенных блоков
 * */
class BlockBuilder {
	constructor(module, parentBuilder, block, infoIndex) {
		this.module = module;
		this.block = block;
		this.parentBuilder = parentBuilder;
		this.index = infoIndex;
		this.elems = [];
		this.onloadElems = [];
		this.node = new BlockJsNode(this, parentBuilder ? parentBuilder.node : null);
	}

	/**
	 * Применить полученный настройки для корневого блока
	 * */
	applySelf(info) {
		if (info.attrs)
			for (var i in info.attrs)
				this.block.DOMelem.setAttribute(i, info.attrs[i]);

		if (info.style)
			for (var st in info.style) {
				var val = info.style[st],
					stName = st.replace(/-(\w)/g, function(str, p0) {
						return p0.toUpperCase();
					});
				this.block.DOMelem.style[stName] = val;
			}

		if (info.props)
			for (var prop in info.props)
				if (!(prop in this.block))
					this.block[prop] = info.props[prop];

		if (info.funcs)
			for (var name in info.funcs)
				if (!(name in this.block))
					this.block[name] = this.block.unpackFunction(info.funcs[name]);

		this.block.inLoad = true;
		this.block.unpackProperties(loaderContext);
		delete this.block.inLoad;
		this.block.postUnpack();
	}

	/**
	 * Генерация lx-сущностей и воссоздание между ними родительских связей
	 * */
	riseTree(elem, infoLx) {
		for (var i=0,l=elem.DOMelem.children.length; i<l; i++) {
			var node = elem.DOMelem.children[i];

			var index = node.getAttribute('lx') - 1;
			if (index == -1) continue;
			node.removeAttribute('lx');

			var info = infoLx[index],
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

			el.key = info.key;

			el.inLoad = true;

			this.elems.push(el);

			el.parent = elem;
			elem.childrenPush(el);

			this.riseTree(el, infoLx);
		}
	}

	/**
	 * Вызов функций, навешанных на момент загрузки
	 * */
	callOnload(el) {
		var js = el.lxExtract('js');
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

				//todo на php генерится такой путь '=' $item->path . '/' . $item->fullKey();
				for (var j=0, l=args.length; j<l; j++) {
					if (args[j] === null) continue;
					// преобразование путей виджетов в их сущности
					if (args[j].isString && args[j].match(/^!!!todo$/))
						args[j] = null;/*!!!todo*/
				}
				func.apply(el, args);
			}
		}
	}

	unpackContent() {
		// Некоторые свойства требуют распаковки, н-р стратегии позиционирования, функции на обработчиках событий и т.п.
		for (var i=0, l=this.elems.length; i<l; i++) {
			var el = this.elems[i];
			el.unpackProperties(loaderContext);

			// Сборка вложенных блоков
			if (el.ib) {
				(new BlockBuilder(this.module, this, el, el.ib)).unpack();
				delete el.ib;
			}

			// Пришел модуль, собранный в этот элемент
			if (el.moduleData)
				createModule(loadData.modules[el.lxExtract('moduleData')], el, this.module);

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
		let clientParams = this.block.lxExtract('ibp');

		// Если есть js-код для запуска после загрузки блока
		if (info.js) {
			this.node.js = info.js;
			this.node.params = clientParams;
		}
	}

	/**
	 * Основной алгоритм распаковки
	 * */
	unpack() {
		//todo подумать насколько эта идея была нормальной
		// // убедимся, что блок чистый
		// var temp = new lx[this.block.lxClassName]();
		// for (var i in this.block)
		// 	//todo проверки не нравятся
		// 	if (i != 'module' && i != 'key' && !(i in temp)) delete this.block[i];
		var info = loadData.blocksInfo[this.module.key][this.index];
		if (!info) return;

		if (!this.block.isBlock)
			this.block.isBlock = true;

		if (info.html) {
			// Бухнули верстку в div блока
			this.block.DOMelem.innerHTML = info.html;
			// Формирование дерева lx-сущностей
			this.riseTree(this.block, info.lx);
		}

		// Блок принес всякие настройки для элемента, в который он выгружается
		if (info.self) this.applySelf(info.self);

		// Допилить содержимое
		if (this.elems.length) this.unpackContent();

		// Вызовы js-кода блоков
		this.unpackJs(info);
	}
}
