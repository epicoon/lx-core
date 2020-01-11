#lx:module lx.Box;

#lx:use lx.Rect;
#lx:use lx.TextBox;
#lx:use lx.Textarea;

#lx:require positioningStrategiesJs/;

#lx:private;

/*
lx.Collection
lx.TextBox
lx.Textarea
*/

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
 * contains(key)
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
 * injectPlugin(info, func)
 * dropPlugin()
 *
 * * 6. Js-features
 * bind(model)
 * matrix(config)
 * agregator(c, toWidget=true, fromWidget=true)
 */

/**
 * @group {i18n:widgets}
 * */
class Box extends lx.Rect #lx:namespace lx {

    //=========================================================================================================================
    /* 1. Constructor */
    #lx:client {
        __construct() {
            super.__construct();
            this.children = {};
        }

        postBuild(config) {
            super.postBuild(config);
            this.on('resize', self::onresize);
            this.on('scrollBarChange', self::onresize);
        }

        postUnpack(config) {
            super.postUnpack(config);
            if (this.lxExtract('__na'))
                this.positioningStrategy.actualize();
        }

        destructProcess() {
            this.setBuildMode(true);
            this.eachChild((child)=>{
                if (child.destruct) child.destruct();
            });
            this.setBuildMode(false);
            var container = __getContainer(this);
            container.dropPlugin();
            super.destructProcess();
        }
    }

    #lx:server {
        __construct() {
            super.__construct();
            this.__self.children = {};
            this.__self.allChildren = {
                data: [],
                count: function() {
                    return this.data.length;
                },
                push: function(el) {
                    this.data.push(el);
                },
                remove: function(el) {
                    this.data.remove(el);
                },
                get: function(num) {
                    return this.data[num];
                },
                insertBefore: function(el, next) {
                    var index = this.data.indexOf(next);
                    this.data.splice(index, 0, el);
                },
                prev: function(elem) {
                    var index = this.data.indexOf(elem);
                    if (index == 0) return null;
                    return this.data[index - 1];
                },
                next: function(elem) {
                    var index = this.data.indexOf(elem);
                    if (index + 1 == this.data.length) return;
                    return this.data[index + 1];
                },
                each: function(f) {
                    this.data.each(f);
                },
                reset: function () {
                    this.data = [];
                }
            };
            this.__self.positioningStrategy = null;
        }

        get children() { return this.__self.children; }
        set children(attr) { this.__self.children = attr; }

        get allChildren() { return this.__self.allChildren; }
        set allChildren(attr) { this.__self.allChildren = attr; }

        get positioningStrategy() { return this.__self.positioningStrategy; }
        set positioningStrategy(attr) { this.__self.positioningStrategy = attr; }

        beforePack() {
            if (this.positioningStrategy !== null) {
                this.__ps = this.positioningStrategy.pack();
            }
        }
    }

    /**
     * config = {
     *	// стандартные для Rect,
     *
     *	positioning: PositioningStrategy
     *	text: string
     * }
     * */
    build(config) {
        super.build(config);

        if ( config.text ) this.text( config.text );

        if (config.positioning) this.setPositioning(config.positioning, config);
        if (config.stream) this.stream(config.stream.isObject ? config.stream : {});
        if (config.grid) this.grid(config.grid.isObject ? config.grid : {});
        if (config.slot) this.slot(config.slot.isObject ? config.slot : {});
    }

    setPositioning(constructor, config) {
        var container = __getContainer(this);
        container.positioningStrategy = new constructor(container, config);
    }

    positioning() {
        var container = __getContainer(this);
        if (container.positioningStrategy) return container.positioningStrategy;
        return new lx.PositioningStrategy(container);
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

    overflow(val) {
        super.overflow.call(__getContainer(this), val);
    }
    /* 1. Constructor */
    //=========================================================================================================================


    //=========================================================================================================================
    /* 2. Content managment */

    #lx:server {
        useRenderCache() {}
        applyRenderCache() {}
        checkParentRenderCache() {}
        addToRenderCache(elem) {}

		setSnippet(config, renderParams = null) {
			if (config.isString) {
				config = {path: config};
				if (renderParams !== null) {
					config.renderParams = renderParams;
				}
			}

			if (!config.renderParams) {
				config.renderParams = [];
			}

			if (!config.clientParams) {
				config.clientParams = [];
			}
			
			if (!config.path) return;

			var container = __getContainer(this);

			// inner snippet
            container.snippetInfo = {
			    path: config.path,
                renderParams: config.renderParams,
                clientParams: config.clientParams
			};

			// флаг, который будет и на стороне клиента
            container.isSnippet = true;
		}

		setPlugin(name, renderParams, clientParams) {
            if (renderParams === undefined && name.isObject) {
                renderParams = name.renderParams;
                clientParams = name.clientParams;
                name = name.name;
            }
            
            if (!name.isString) return;
            var container = __getContainer(this);
            container.pluginAnchor = App.genId();
            var data = {name, anchor:container.pluginAnchor};
            if (renderParams) data.renderParams = renderParams;
            if (clientParams) data.clientParams = clientParams;
            this.getSnippet().addPlugin(data);
        }
    }

    #lx:client {
        useRenderCache() {
            if (this.renderCacheStatus === undefined) {
                this.renderCacheStatus = true;
                this.renderCache = [];

                var container = __getContainer(this);
                if (container !== this) {
                    container.renderCacheStatus = true;
                    container.renderCache = [];
                }
            }
        }

        applyRenderCache() {
            // Если элемент не существует - применять некуда. Скорее всего, этот элемент сам находится в кэше
            // и применять кэш нужно на уровень выше
            if (!this.getDomElem()) return;

            if (!this.renderCacheStatus) return;
            delete this.renderCacheStatus;

            if (!this.renderCache.len) {
                this.getChildren().each((c) => {
                    if (c.lxHasMethod('applyRenderCache')) c.applyRenderCache()
                });

                var container = __getContainer(this);
                if (container !== this) container.applyRenderCache();
                return;
            }

            var html = this.domElem.html();
            var text = __renderContent(this, html);
            this.domElem.html(text);
            __renderRise(this);
            __refreshAfterRender(this);

            this.positioning().actualize();
        }

        checkParentRenderCache() {
            if (!this.renderCacheStatus && this.domElem.parent && this.domElem.parent.renderCacheStatus) {
                this.useRenderCache();
            }
        }

        addToRenderCache(elem) {
            this.renderCache.push(elem);
            var lxid = lx.WidgetHelper.genId();
            elem.setAttribute('lxid', lxid);
            elem.lxid = lxid;
        }
    }

    /**
     * Включение режима сборки устанавливает в качестве основного контейнера элемент самого виджета
     */
    setBuildMode(bool) {
        if (bool) this.__buildMode = true;
        else delete this.__buildMode;
    }

    /**
     * Можно переопределить у потомков, чтобы определить дочерний элемент, который будет отвечать за взаимодействие
     * с потомками, добавляемыми уже после создания виджета
     */
    getContainer() {
        return this;
    }

    /**
     * Метод, используемый новым элементом для регистрации в родителе
     * */
    addChild(elem, config = {}) {
        elem.parent = this;
        config = this.modifyNewChildConfig(config);

        this.checkParentRenderCache();
        var container = __getContainer(this);
        elem.domElem.setParent(container, config.nextSibling);
        if (this.renderCacheStatus) {
            container.addToRenderCache(elem);
        } else {
            elem.createDomElement();
        }

        container.childrenPush(elem, config.nextSibling);
        this.positioning().allocate(elem, config);

        if (container.getDomElem() && elem.getDomElem()) {
            var	tElem = container.getDomElem(),
                clientHeight0 = tElem.clientHeight,
                clientWidth0 = tElem.clientWidth;

            var clientHeight1 = tElem.clientHeight;
            if (clientHeight0 > clientHeight1) {
                container.trigger('xScrollBarOn');
                container.trigger('xScrollBarChange');
                container.trigger('scrollBarChange');
            } else if (clientHeight0 < clientHeight1) {
                container.trigger('xScrollBarOff');
                container.trigger('xScrollBarChange');
                container.trigger('scrollBarChange');
            }

            var clientWidth1 = tElem.clientWidth;
            if (clientWidth0 > clientWidth1) {
                container.trigger('yScrollBarOn');
                container.trigger('yScrollBarChange');
                container.trigger('scrollBarChange');
            } else if (clientWidth0 < clientWidth1) {
                container.trigger('yScrollBarOff');
                container.trigger('yScrollBarChange');
                container.trigger('scrollBarChange');
            }
        }
    }

    /**
     * Предобработка конфига добавляемого элемента
     * */
    modifyNewChildConfig(config) {
        return config;
    }

    /**
     * Регистрация нового элемента в структурах элемента виджета
     * регистрация напрямую (!) - без посредника контейнера
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
        var next = config.nextSibling;
        var positioning = this.positioning();
        // if (this.positioningStrategy) this.positioningStrategy.autoActualize = false;

        var map = [];
        var container = __getContainer(this);
        c.each((a)=> {
            a.dropParent();
            a.parent = this;

            this.checkParentRenderCache();
            a.domElem.setParent(container, config.nextSibling);
            if (this.renderCacheStatus) {
                container.addToRenderCache(a);
            } else {
                a.createDomElement();
            }

            //if (!this.renderCacheStatus)
            positioning.allocate(a, config);
            if (a.key) {
                (a.key in map) ? map[a.key].push(a) : map[a.key] = [a];
            }
        });

        for (var key in map) {
            var item = map[key];
            if (key in container.children) {
                if (!container.children[key].isArray) {
                    container.children[key]._index = 0;
                    container.children[key] = [container.children[key]];
                }
                if (next && key == next.key) {
                    var index = next._index;
                    container.children[key].splice.apply(container.children[key], [index, 0].concat(item));
                    for (var i=index,l=container.children[key].length; i<l; i++) {
                        container.children[key][i]._index = i;
                    }
                } else {
                    var index = container.children[key].length;
                    for (var i=0, l=item.length; i<l; i++) {
                        item[i]._index = index + i;
                        container.children[key].push(item[i]);
                    }
                }
            } else {
                container.children[key] = item;
                for (var i=0,l=item.length; i<l; i++) item[i]._index = i;
            }
        }

        // if (this.positioningStrategy) {
        //     this.positioningStrategy.autoActualize = true;
        //     this.positioningStrategy.actualize({from: c.first()});
        // }

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
        if (count == 1) return new type(config);
        var result = type.construct(count, config, configurator);
        return result;
    }

    /**
     * Проход по всем потомкам
     * */
    eachChild(func) {
        function re(elem) {
            if (!elem.child) return;
            var num = 0,
                child = elem.child(num);
            while (child) {
                func(child);
                re(child);
                child = elem.child(++num);
            }
        }
        re(__getContainer(this));
    }

    /**
     * Удаляет всех потомков
     * */
    clear() {
        var container = __getContainer(this);
        #lx:client{ if (container.domElem.html() == '') return; }

        // Сначала все потомки должны освободить используемые ресурсы
        container.eachChild((child)=>{
            if (child.destruct) child.destruct();
        });

        // После чего можно разом обнулить содержимое
        #lx:client{
            container.domElem.html('');
            lx.WidgetHelper.checkFrontMap();
        }
        #lx:server{
            this.allChildren.reset();
        }

        container.children = {};
        container.positioning().reset();
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
            //TODO - проверить важность этой строчки. После обертки над DOM-элементом, он может не существовать, а обертка да
            // если элемент - пустышка, нечего удалять
            // if (!this.getDomElem()) return 0;

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

            var container = __getContainer(this);

            // Если у элемента есть ключ - будем удалять по ключу
            if (el.key && el.key in container.children) return this.del(el.key, el._index, 1);

            // Если ключа нет - удаляем проще
            var pre = el.prevSibling();
            container.domElem.removeChild(el.domElem);
            #lx:client{ lx.WidgetHelper.checkFrontMap(); }
            container.positioning().actualize({from: pre, deleted: [el]});
            container.positioning().onDel();
            el.destruct();
            return 1;
        }

        // el - ключ
        var container = __getContainer(this);
        if (!(el in container.children)) return 0;

        // children[el] - не массив, элемент просто удаляется
        if (!container.children[el].isArray) {
            var elem = container.children[el],
                pre = elem.prevSibling();
            container.domElem.removeChild(elem.domElem);
            #lx:client{ lx.WidgetHelper.checkFrontMap(); }
            delete container.children[el];
            container.positioning().actualize({from: pre, deleted: [elem]});
            container.positioning().onDel();
            return 1;
        }

        // children[el] - массив
        if (count === undefined) count = 1;
        if (index === undefined) {
            index = 0;
            count = container.children[el].length;
        } else if (index >= container.children[el].length) return 0;
        if (index + count > container.children[el].length)
            count = container.children[el].length - index;

        var deleted = [],
            pre = container.children[el][index].prevSibling();
        for (var i=index,l=index+count; i<l; i++) {
            var elem = container.children[el][i];

            deleted.push(elem);
            container.domElem.removeChild(elem.domElem);
        }
        #lx:client{ lx.WidgetHelper.checkFrontMap(); }

        container.children[el].splice(index, count);
        for (var i=index,l=container.children[el].length; i<l; i++)
            container.children[el][i]._index = i;
        if (!container.children[el].length) {
            delete container.children[el];
        } else if (container.children[el].length == 1) {
            container.children[el] = container.children[el][0];
            delete container.children[el]._index;
        }
        container.positioning().actualize({from: pre, deleted});
        container.positioning().onDel();
        deleted.each((a)=>a.destruct());

        return count;
    }

    text(text) {
        if (text === undefined) {
            if ( !this.contains('text') ) return '';
            return this.children.text.value();
        }

        if (!this.contains('text')) new lx.TextBox({parent: this});

        this.children.text.value(text);
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
        if ( this.contains('input') ) return;

        var _t = this,
            boof = this.text(),
            input = new lx.Textarea({
                parent: this,
                key: 'input',
                geom: [0, 0, this.width('px')+'px', this.height('px')+'px']
            });

        var elem = input.getDomElem();
        elem.value = boof;
        input.focus();
        elem.select();
        input.style('visibility', 'visible');
        input.on('blur', function() {
            var boof = this.domElem.param('value').replace(/<.+?>/g, 'tag');
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
    
    /*TODO
    Только для сервера, не переделывал php-код пока
	public function renderHtmlFile($fileName) {
		$path = $this->getPlugin()->getFilePath($fileName);
		$file = new HtmpFile($path);
		if (!$file->exists()) return;

		$text = $file->get();
		$this->renderHtml($text);
	}

	public function renderHtml($text) {
		//TODO экранирование кавычек. Возможно, уже не обязательно (это делается более централизованно)
		if (!$this->getApp()->dialog->isAjax()) {
			$text = preg_replace('/"/', '\"', $text);
		}

		$text = preg_replace_callback('/<pre>\s?([\w\W]*?)<\/pre>/', function($matches) {
			$string = preg_replace('/\n/', '<br>', $matches[1]);
			return "<pre>$string</pre>";
		}, $text);
		$text = preg_replace('/\n/', '', $text);
		$text = preg_replace('/\t/', '    ', $text);
		$this->text($text);
	}
    */
    /* 2. Content managment */
    //=========================================================================================================================


    //=========================================================================================================================
    /* 3. Content navigation */
    /**
     *
     * */
    get(path) {
        var result = __get(this, path);
        if (result) return result;

        var container = __getContainer(this);
        if (!container !== this) result = __get(container, path);
        return result;
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

    contains(key) {
        var container = __getContainer(this);

        if (key instanceof lx.Rect) {
            if (!(key.key in container.children)) return false;
            if (container.children[key.key].isArray) {
                if (key._index === undefined) return false;
                return container.children[key.key][key._index] === key;
            }
            return container.children[key.key] === key;
        }
        return (key in container.children);
    }

    childrenCount(key) {
        var container = __getContainer(this);

        if (key === undefined) {
            #lx:server {
                return container.allChildren.count();
            }

            #lx:client {
                var elem = container.getDomElem();
                if (elem) {
                    var result = elem.children.length
                    if (container.renderCacheStatus)
                        result += container.renderCache.len;
                    return result;
                }
                if (container.renderCacheStatus)
                    return container.renderCache.len;
                return 0;
            }
        }

        if (!container.children[key]) return 0;
        if (!container.children[key].isArray) return 1;
        return container.children[key].len;
    }

    child(num) {
        var container = __getContainer(this);

        #lx:server {
            if (num < container.allChildren.count())
                return container.allChildren.get(num);
        }

        #lx:client {
            var elem = container.getDomElem();
            if (!elem) {
                if (!container.renderCacheStatus) return null;
                if (num >= container.renderCache.len) return null;
                return container.renderCache[num];
            }

            if (num < elem.children.length)
                return lx.WidgetHelper.getByElem(elem.children[num]);

            if (container.renderCacheStatus) {
                num -= elem.children.length;
                if (num < container.renderCache.len)
                    return container.renderCache[num];
            }
        }

        return null;
    }

    lastChild() {
        var elem = __getContainer(this).getDomElem();
        if (!elem) return null;
        var lc = elem.lastChild;
        if (!lc) return null;
        return lx.WidgetHelper.getByElem(lc);
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
        rec(__getContainer(this));
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
     * 8. getChildren({callback:(a)=>{...}, all:true})  - см. 4.
     * */
    getChildren(info=false, all=false) {
        if (info === false) {
            var c = new lx.Collection();
            var container = __getContainer(this);
            for (var i=0; i<container.childrenCount(); i++) {
                var child = container.child(i);
                if (child instanceof lx.Rect) c.add(child);
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
        var container = __getContainer(this);
        if (container.positioningStrategy) container.positioningStrategy.autoActualize = false;
    }

    startPositioning() {
        var container = __getContainer(this);
        if (container.positioningStrategy) {
            container.positioningStrategy.autoActualize = true;
            container.positioningStrategy.actualize();
        }
    }

    preparePositioningStrategy(strategy) {
        var container = __getContainer(this);
        if (container.positioningStrategy) {
            if (container.positioningStrategy.lxFullClassName == strategy.lxFullName)
                return container.positioningStrategy;
            container.positioningStrategy.clear();
        }
        container.positioningStrategy = (strategy === lx.PositioningStrategy)
            ? null
            : new strategy(container);
        return container.positioningStrategy;
    }

    align(hor, vert) {
        var pos = this.preparePositioningStrategy(lx.AlignPositioningStrategy);
        if (pos) pos.init(hor, vert);
        return this;
    }

    stream(config) {
        var pos = this.preparePositioningStrategy(lx.StreamPositioningStrategy);
        if (pos) pos.init(config);
        return this;
    }

    streamProportional(config={}) {
        config.type = lx.StreamPositioningStrategy.TYPE_PROPORTIONAL;
        return this.stream(config);
    }

    streamDirection() {
        if (!this.positioningStrategy || this.positioningStrategy.lxClassName != 'StreamPositioningStrategy')
            return false;
        return this.positioningStrategy.direction;
    }

    grid(config) {
        var pos = this.preparePositioningStrategy(lx.GridPositioningStrategy);
        if (pos) pos.init(config);
        return this;
    }

    gridProportional(config={}) {
        config.type = lx.GridPositioningStrategy.TYPE_PROPORTIONAL;
        return this.grid(config);
    }

    slot(config) {
        var pos = this.preparePositioningStrategy(lx.SlotPositioningStrategy);
        if (pos) pos.init(config);
        return this;
    }

    setIndents(config) {
        var container = __getContainer(this);
        if (!container.positioningStrategy) return this;
        container.positioningStrategy.setIndents(config);
        container.positioningStrategy.actualize();
        return this;
    }

    //todo нужен или нет этот метод?
    // setGeomParam(param, val) {
    // 	if (this.positioningStrategy.checkOwnerReposition(param))
    // 		super.setGeomParam(param, val);
    // }
    /* 4. PositioningStrategies */
    //==================================================================================================================


    #lx:client { /*client BEGIN*/
    //==================================================================================================================
    /* 5. Load */
    /**
     * Загружает уже полученные данные о модуле в элемент
     * */
    injectPlugin(info, func) {
        this.dropPlugin();
        lx.Loader.run(info, this, this.getPlugin(), func);
    }

    /**
     * Удаляет модуль из элемента, из реестра модулей и всё, что связано с модулем
     * */
    dropPlugin() {
        if (this.plugin) {
            this.plugin.del();
            delete this.plugin;
        }
    }

    /* 5. Load */
    //==================================================================================================================


    //==================================================================================================================
    /* 6. Client-features */
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
            lx.getFirstDefined(config.type, lx.Binder.BIND_TYPE_FULL)
        );
    }

    agregator(c, type=lx.Binder.BIND_TYPE_FULL) {
        lx.Binder.bindAgregation(c, this, type);
    }
    /* 6. Client-features */
    //==================================================================================================================
    } /*client END*/
}

lx.Box.defaultMatrixItemBox = lx.Box;


/***********************************************************************************************************************
 * PRIVATE
 **********************************************************************************************************************/
function __getContainer(self) {
    if (self.__buildMode) return self;
    return self.getContainer();
}

function __get(self, path) {
    if (path instanceof lx.Rect) return path;

    var arr = path.match(/[\w\d_\[\]]+/ig),
        children = self.children;
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

#lx:client {
    function __renderContent(self, baseHtml = '') {
        if (self.children === undefined) return self.domElem.content;
        
        var result = baseHtml;
        var map = {};
        if (self.renderCache) for (var i = 0, l = self.renderCache.len; i < l; i++) {
            var id = self.renderCache[i].domElem.next
                ? self.renderCache[i].domElem.next.domElem.getAttribute('lxid')
                : 0;
            if (map[id] === undefined) map[id] = '';
            map[id] += __render(self.renderCache[i]);
        }

        for (var id in map) {
            var text = map[id];
            if (id == 0) result += text;
            else {
                var regExp = new RegExp('lxid="' + id + '"');
                if (result.search(regExp) == -1) result += text;
                else {
                    var regExp = new RegExp('(<[^<]*?lxid="' + id + '")');
                    result = result.replace(regExp, text + '$1');
                }
            }
        }

        return result;
    }

    function __render(self) {
        return self.domElem.getHtmlStringBegin() + __renderContent(self) + self.domElem.getHtmlStringEnd();
    }

    function __renderRise(self) {
        if (!self.renderCache) return;

        for (var i = 0, l = self.renderCache.len; i < l; i++) {
            var child = self.renderCache[i];

            var elem = lx.WidgetHelper.getElementByLxid(child.lxid, self);
            child.domElem.setElem(elem);

            __renderRise(child);
        }

        delete self.renderCacheStatus;
        delete self.renderCache;
    }

    function __refreshAfterRender(self) {
        var re = function (t) {
            var children = t.getDomElem().children;
            for (var i = 0, l = children.length; i < l; i++) {
                var child = children[i],
                    elem = lx.WidgetHelper.getByElem(children[i]);
                elem.domElem.refreshElem(t);
                re(elem);
            }
        }
        re(self);
    }
};
