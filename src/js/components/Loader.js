#private;

let commonContext = {
	modules: [],
	scripts: [],
	css: [],
	blockJs: []
};

Object.defineProperty(lx, 'Loader', {get: function() {
	//todo судя по описанию это должен быть тот же commonContext
	// Инкапсулированный контекст зарузки, остающийся единым над рекурсивным созданием экземпляров Loader
	let loaderContext = {
		screenDependend: []
	};

	// Открытый интерфейс
	let publicInterface = {
		/**
		 * info - параметры модуля
		 * el - элемент, в который будет загружен корневой блок представления модуля
		 * parent - в случае загрузки ajax-модуля это родительский модуль, который его вызвал
		 * func - после удачной загрузки можно что-то сделать хорошего
		 * */
		loadModule: function(info, el, parent, func) {
			// Статика и ajax приходят по-разному (Актуально для первого прогона! Дальше всё строкой пойдет, а ресурсы уже будут в общем контексте)
			var isAjax,
				modulesInfo,
				moduleFullInfo,
				necessaryWidgets = null;
			// Это статика - ресурсы собраны на стороне сервера
			if (info.isString) {
				isAjax = false;
				modulesInfo = info;

				// Раскручиваем пришедшие виджеты
				var matches = modulesInfo.match(/<widgets>([\w\W]*?)<\/widgets>/);
				if (matches) {
					var widgets = matches[1];
					lx.createAndCallFunction('', widgets);
				}
			// Это ajax - если ресурсы пришли, их надо собирать здесь
			} else {
				isAjax = true;
				modulesInfo = info.moduleInfo;
				if (info.scripts) commonContext.scripts = info.scripts;
				if (info.css) commonContext.css = info.css;
				// Для ajax-запроса надо определить какие виджеты потребуют отдельной дозагрузки
				if (info.widgets) necessaryWidgets = lx.widgets.defineNecessary(info.widgets);
			}

			// Если еще это корневой прогон - надо распарсить инфу по модулям
			if (commonContext.modules.lxEmpty) {
				var reg = /<module (.+?)>/g,
					match,
					modules = [],
					rootKey = '';
				while (match = reg.exec(modulesInfo)) {
					var key = match[1];
					modules[key] = modulesInfo.match(new RegExp('<module '+key+'>([\\w\\W]*?)</module '+key+'>'))[1];
					// Корневой модуль обволакивает вложенные - начинает собираться первым, а заканчивает поледним
					rootKey = key;
				}

				commonContext.modules = modules;
				moduleFullInfo = modules[rootKey];
			} else {
				moduleFullInfo = modulesInfo;
			}

			// Тут пошла работа с инфой чисто по одному модулю
			var uniqKey = moduleFullInfo.match(/^<mi (.+?)>/)[1],
				moduleInfo = moduleFullInfo.match(new RegExp('<mi '+uniqKey+'>([\\w\\W]*?)</mi '+uniqKey+'>'))[1],
				bootstrapJs= moduleFullInfo.match(new RegExp('<bs '+uniqKey+'>([\\w\\W]*?)</bs '+uniqKey+'>'))[1],
				blocks= moduleFullInfo.match(new RegExp('<bl '+uniqKey+'>([\\w\\W]*?)</bl '+uniqKey+'>'))[1],
				mainJs= moduleFullInfo.match(new RegExp('<mj '+uniqKey+'>([\\w\\W]*?)</mj '+uniqKey+'>'))[1];

			moduleInfo = lx.Json.parse(moduleInfo);
			blocks = lx.Json.parse(blocks);

			// Создадим экземпляр модуля
			if (!el) {
				lx.body = lx.Box.rise(document.getElementById('lx'));
				lx.body.key = 'body';
				lx.body.on('scroll', lx.checkDisplay);
				el = lx.body;
			}
			if (parent) moduleInfo.parent = parent;
			moduleInfo.key = uniqKey;
			var m = lx.Module(moduleInfo, el);
			
			// Синхронизируем загрузку ресурсов и старт выполнения модуля
			var synchronizer = new lx.Synchronizer(),
				headScriptTags = [],
				cssTags = [];

			// Запрос на догрузку виджетов регистрируется в синхронайзере
			var widgetsRequest = null;
			if (necessaryWidgets && !necessaryWidgets.lxEmpty) {
				widgetsRequest = new lx.Request('get-widgets', necessaryWidgets);
				widgetsRequest.setHeader('lx-type', 'service');
				widgetsRequest.success = function(result) {
					if (result) lx.createAndCallFunction('', result);
				};
				synchronizer.register(widgetsRequest);
			}

			// script-ресурсы регистрируются в синхронайзере
			if (uniqKey in commonContext.scripts) {
				var scripts = commonContext.scripts[uniqKey];
				if (scripts.head) {
					for (var i=0; i<scripts.head.len; i++) {
						var tag = m.createScriptTag(scripts.head[i]);
						if (!tag) continue;
						synchronizer.register(tag, 'onload', 'onerror');
						headScriptTags.push(tag);
					}
				}
			}

			// css-ресурсы регистрируются в синхронайзере
			if (uniqKey in commonContext.css) {
				var css = commonContext.css[uniqKey];
				for (var i=0; i<css.len; i++) {
					var tag = m.createCssTag(css[i]);
					if (!tag) continue;
					synchronizer.register(tag, 'onload', 'onerror');
					cssTags.push(tag);
				}
			}

			// Модуль стартанёт после подключения ресурсов
			synchronizer.start(()=> {
				// js-код загрузки модуля
				if (bootstrapJs != '')
					lx.createAndCallFunction('', 'const Module=lx.modules["'+m.key+'"];' + bootstrapJs);

				// Сборка блоков
				(new BlockBuilder(el, blocks, 0)).unpack();
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
				//todo возможно это как-то реально собрать на стороне сервера - подумать наж этим
				// Добавляем код блоков
				for (let i=0, l=commonContext.blockJs.length; i<l; i++) {
					let pare = commonContext.blockJs[i];
					args.push(pare[1]);
					args.push(pare[2]);
					let block = '__b_' + i;
					let params = '__p_' + i;
					argsStr.push(block);
					argsStr.push(params);
					code += '(function(Block, clientParams){'+pare[0]+'})('+block+','+params+');'
				}
				// Выполняем получившийся код
				lx.createAndCallFunction(argsStr.join(','), code, null, args);
			
				// Вернули контекст в исходное состояние
				commonContext = {
					modules: [],
					scripts: [],
					css: [],
					blockJs: []
				};
				// Функция, которую можно передать коллбэком - для выполнения после загрузки модуля
				if (func) func(m);
			});

			// Подтягиваем виджеты
			if (widgetsRequest) widgetsRequest.send();

			// Подключение ресурсов
			if (headScriptTags.len) {
				var head = document.getElementsByTagName('head')[0];
				for (var i=0; i<headScriptTags.len; i++)
					head.appendChild(headScriptTags[i]);
			}
			//todo
			// document.body.insertBefore(tag, bodyDiv);
			// document.body.appendChild(tag);
			if (cssTags.len) {
				var head = document.getElementsByTagName('head')[0];
				for (var i=0; i<cssTags.len; i++)
					head.appendChild(cssTags[i]);				
			}
		
			return m;
		}
	};


	/**
	 * Собирает дерево элементов, запускает события элементов
	 * Создается рекурсивно для вложенных блоков
	 * */
	function BlockBuilder(block, info, infoIndex) {
		this.block = block;
		this.totalInfo = info;
		this.index = infoIndex;
		this.elems = [];
		this.onloadElems = [];

		/**
		 * Применить полученный настройки для корневого блока
		 * */
		this.applySelf = function(info) {
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
		};

		/**
		 * Генерация lx-сущностей и воссоздание между ними родительских связей
		 * */
		this.riseTree = function(elem, infoLx) {
			for (var i=0,l=elem.DOMelem.children.length; i<l; i++) {
				var node = elem.DOMelem.children[i];

				var index = node.getAttribute('lx') - 1;
				if (index == -1) continue;
				node.removeAttribute('lx');

				var info = infoLx[index],
					namespace = info._namespace ? info._namespace : 'lx';

				if (!(namespace in window)) {
					console.error('Widget not found:', namespace + '.' + info.type);
					continue;
				}

				var type = null;

				if (info.type in window[namespace]) type = window[namespace][info.type];
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
		};

		/**
		 * Вызов функций, навешанных на момент загрузки
		 * */
		this.callOnload = function(el) {
			var js = el.extract('js');
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
		};

		this.unpackContent = function() {
			// Некоторые свойства требуют распаковки, н-р стратегии позиционирования, функции на обработчиках событий и т.п.
			for (var i=0, l=this.elems.length; i<l; i++) {
				var el = this.elems[i];
				el.unpackProperties(loaderContext);

				// Сборка вложенных блоков
				if (el.ib) {
					(new BlockBuilder(el, this.totalInfo, el.ib)).unpack();
					delete el.ib;
				}

				// Пришел модуль, собранный в этот элемент
				if (el.moduleData)
					el.injectModule(commonContext.modules[el.extract('moduleData')]);

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
		};

		this.unpackJs = function(info) {
			let clientParams = this.block.extract('ibp');

			// Если есть js-код для немедленного запуска
			if (info.bs) {
				let f = new Function('Block, clientParams', info.bs);
				f(this.block, clientParams);
			}

			// Если есть js-код для запуска после загрузки всего модуля
			if (info.js) {
				// let f = new Function('Module, Block, clientParams', info.js);
				commonContext.blockJs.push([info.js, this.block, clientParams]);
			}
		};

		/**
		 * Основной алгоритм распаковки
		 * */
		this.unpack = function() {
			//todo подумать насколько эта идея была нормальной
			// // убедимся, что блок чистый
			// var temp = new lx[this.block.className]();
			// for (var i in this.block)
			// 	//todo проверки не нравятся
			// 	if (i != 'module' && i != 'key' && !(i in temp)) delete this.block[i];
			var info = this.totalInfo[this.index];
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
		};
	};

	/**
	 * Открытый интерфейс
	 * */
	return publicInterface;
}});
