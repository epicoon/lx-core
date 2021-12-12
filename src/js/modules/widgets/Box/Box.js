#lx:module lx.Box;

#lx:use lx.Rect;
#lx:use lx.TextBox;
#lx:use lx.Textarea;

#lx:require positioningStrategiesJs/;
#lx:require tools;

/* * 1. Constructor
 * build(config)
 * clientBuild(config)
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
 * eachChild(func, all=false)
 *
 * * 4. PositioningStrategies
 * preparePositioningStrategy(strategy)
 * align(hor, vert, els)
 * stream(config)
 * streamProportional(config={})
 * streamAutoSize(config={})
 * getStreamDirection()
 * grid(config)
 * gridProportional(config={})
 * gridStream(config={})
 * gridAdaptive(config={})
 * slot(config)
 * setIndents(config)
 *
 * * 5. Load
 * setPlugin(info, attributes, func)
 * dropPlugin()
 *
 * * 6. Js-features
 * bind(model)
 * matrix(config)
 * agregator(c, toWidget=true, fromWidget=true)
 * 
 * Special events:
 * - beforeAddChild(child)
 * - afterAddChild(child)
 */

/**
 * @group {i18n:widgets}
 * */
class Box extends lx.Rect #lx:namespace lx {

    //==================================================================================================================
    /* 1. Constructor */
    #lx:client {
        __construct() {
            super.__construct();
            this.children = new BoxChildren();
            this.childrenByKeys = {};
        }

        clientBuild(config) {
            super.clientBuild(config);
            this.on('resize', self::onresize);
            this.on('scrollBarChange', self::onresize);
        }

        postUnpack(config) {
            super.postUnpack(config);
            if (this.lxExtract('__na'))
                this.positioningStrategy.actualize();
        }

        destruct() {
            this.unbind();

            var container = __getContainer(this);
            container.dropPlugin();

            this.setBuildMode(true);
            this.eachChild((child)=>{
                if (child.destructProcess) child.destructProcess();
            });
            this.setBuildMode(false);

            super.destruct();
        }
    }

    #lx:server {
        __construct() {
            super.__construct();
            this.__self.children = new BoxChildren();
            this.__self.childrenByKeys = {};
            this.__self.positioningStrategy = null;
        }

        get children() { return this.__self.children; }
        set children(attr) { this.__self.children = attr; }

        get childrenByKeys() { return this.__self.childrenByKeys; }
        set childrenByKeys(attr) { this.__self.childrenByKeys = attr; }

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

        if (config.positioning)
            this.setPositioning(config.positioning, config);
        else if (config.stream)
            this.stream(lx.isObject(config.stream) ? config.stream : {});
        else if (config.streamProportional)
            this.streamProportional(lx.isObject(config.streamProportional) ? config.streamProportional : {});
        else if (config.grid)
            this.grid(lx.isObject(config.grid) ? config.grid : {});
        else if (config.gridProportional)
            this.gridProportional(lx.isObject(config.gridProportional) ? config.gridProportional : {});
        else if (config.gridStream)
            this.gridStream(lx.isObject(config.gridStream) ? config.gridStream : {});
        else if (config.gridAdaptive)
            this.gridAdaptive(lx.isObject(config.gridAdaptive) ? config.gridAdaptive : {});
        else if (config.slot)
            this.slot(lx.isObject(config.slot) ? config.slot : {});
    }

    getCommonEventNames() {
        return ['beforeAddChild', 'afterAddChild'];
    }

    static onresize() {
        this.positioning().actualize();
    }

    begin() {
        lx.Rect.setAutoParent(this);
        return true;
    }

    isAutoParent() {
        return (lx.Rect.getAutoParent() === this);
    }

    end() {
        lx.Rect.removeAutoParent(this);
        return true;
    }

    overflow(val) {
        super.overflow.call(__getContainer(this), val);
    }
    /* 1. Constructor */
    //==================================================================================================================


    //==================================================================================================================
    /* 2. Content managment */

    getContainerBox() {
        return __getContainer(this);
    }

    #lx:server {
		setSnippet(config, attributes = null) {
			if (lx.isString(config)) {
				config = {path: config};
				if (attributes !== null) config.attributes = attributes;
			}

            if (!config.path) return;
			if (!config.attributes) config.attributes = {};

			var container = __getContainer(this);

			// inner snippet
            container.snippetInfo = {
			    path: config.path,
                attributes: config.attributes
			};

			// флаг, который будет и на стороне клиента
            container.isSnippet = true;
		}

		setPlugin(name, attributes, onLoad) {
            if (attributes === undefined && lx.isObject(name)) {
                attributes = name.attributes;
                name = name.name;
            }

            if (!lx.isString(name)) return;

            var container = __getContainer(this);
            container.pluginAnchor = App.genId();

            var data = {name, anchor:container.pluginAnchor};
            if (attributes) data.attributes = attributes;
            if (onLoad) data.onLoad = onLoad;
            this.getSnippet().addPlugin(data);
        }

        useRenderCache() {}
        applyRenderCache() {}
    }

    #lx:client {
        getInnerPlugin() {
            var container = __getContainer(this);
            if (!container.plugin) return null;
            return container.plugin;
        }
        
        setSnippet(config, attributes = null) {
            if (lx.isString(config)) {
                config = {path: config};
                if (attributes !== null) config.attributes = attributes;
            }

            if (!config.path) return;
            if (!config.attributes) config.attributes = {};

            var container = __getContainer(this);
            container.isSnippet = true;

            var snippet = new lx.Snippet(this, config);
            var maker = lx.SnippetMap.getSnippetMaker(config.path);

            this.useRenderCache();
            this.begin();
            maker(this.getPlugin(), snippet);
            snippet.setLoaded();
            this.end();
            this.applyRenderCache();
        }

        useRenderCache() {
            if (this.renderCacheStatus === undefined) {
                this.stopPositioning();
                
                this.renderCacheStatus = true;
                this.renderCache = 0;

                var container = __getContainer(this);
                if (container !== this) {
                    container.renderCacheStatus = true;
                    container.renderCache = 0;
                }
            }
        }

        applyRenderCache() {
            // Если элемент не существует - применять некуда. Скорее всего, этот элемент сам находится в кэше
            // и применять кэш нужно на уровень выше
            if (!this.getDomElem()) return;

            if (!this.renderCacheStatus) return;
            delete this.renderCacheStatus;

            if (this.renderCache === 0) {
                this.eachChild((c)=>{
                    if (c.lxHasMethod('applyRenderCache')) c.applyRenderCache()
                });

                var container = __getContainer(this);
                if (container !== this) container.applyRenderCache()
                else this.startPositioning();
                return;
            }

            var text = __renderContent(this);
            this.domElem.html(text);
            __refreshAfterRender(this);

            this.startPositioning();
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
     * Метод, используемый новым виджетом для регистрации в родителе
     * */
    addChild(widget, config = {}) {
        this.trigger('beforeAddChild', widget);
        widget.parent = this;
        config = this.modifyNewChildConfig(config);
        var container = __getContainer(this);
        widget.domElem.setParent(container, config.nextSibling);

        #lx:client {
            __checkParentRenderCache(this);
            if (this.renderCacheStatus) {
                __addToRenderCache(container, widget);
            } else {
                widget.domElem.applyParent();
            }
        }

        var clientHeight0, clientWidth0;
        if (container.getDomElem() && widget.getDomElem()) {
            var tElem = container.getDomElem();
            clientHeight0 = tElem.clientHeight;
            clientWidth0 = tElem.clientWidth;
        }

        container.registerChild(widget, config.nextSibling);
        this.positioning().allocate(widget, config);

        if (container.getDomElem() && widget.getDomElem()) {
            var clientHeight1 = tElem.clientHeight;
            var trigged = false;
            if (clientHeight0 > clientHeight1) {
                container.trigger('xScrollBarOn');
                container.trigger('xScrollBarChange');
                container.trigger('scrollBarChange');
                trigged = true;
            } else if (clientHeight0 < clientHeight1) {
                container.trigger('xScrollBarOff');
                container.trigger('xScrollBarChange');
                container.trigger('scrollBarChange');
                trigged = true;
            }

            var clientWidth1 = tElem.clientWidth;
            if (clientWidth0 > clientWidth1) {
                container.trigger('yScrollBarOn');
                container.trigger('yScrollBarChange');
                if (!trigged) container.trigger('scrollBarChange');
            } else if (clientWidth0 < clientWidth1) {
                container.trigger('yScrollBarOff');
                container.trigger('yScrollBarChange');
                if (!trigged) container.trigger('scrollBarChange');
            }

            widget.trigger('displayin');
        }
        this.trigger('afterAddChild', widget);
    }

    /**
     * Регистрация нового виджета в структурах родителя (текущего виджета)
     * регистрация напрямую (!) - без посредника контейнера
     */
    registerChild(child, next) {
        if (next) this.children.insertBefore(child, next);
        else this.children.push(child);

        if (!child.key) return;

        if (child.key in this.childrenByKeys) {
            if (!lx.isArray(this.childrenByKeys[child.key])) {
                this.childrenByKeys[child.key]._index = 0;
                this.childrenByKeys[child.key] = [this.childrenByKeys[child.key]];
            }
            if (next && child.key == next.key) {
                child._index = next._index;
                this.childrenByKeys[child.key].splice(child._index, 0, child);
                for (var i=child._index+1,l=this.childrenByKeys[child.key].length; i<l; i++) {
                    this.childrenByKeys[child.key][i]._index = i;
                }
            } else {
                child._index = this.childrenByKeys[child.key].length;
                this.childrenByKeys[child.key].push(child);
            }
        } else this.childrenByKeys[child.key] = child;
    }

    /**
     * Предобработка конфига добавляемого элемента
     * */
    modifyNewChildConfig(config) {
        return config;
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
        if (lx.isArray(type)) {
            var result = [];
            for (var i=0, l=type.len; i<l; i++)
                result.push( this.add.apply(this, type[i]) );
            return result;
        }

        if (lx.isObject(count)) {
            config = count;
            count = 1;
        }
        config.parent = this;

        if (count == 1) return new type(config);

        var result = type.construct(count, config, configurator);
        return result;
    }

    /**
     * Удаляет всех потомков
     * */
    clear() {
        var container = __getContainer(this);
        #lx:client{ if (container.domElem.html() == '') return; }

        // Сначала все потомки должны освободить используемые ресурсы
        container.eachChild((child)=>{
            if (child.destructProcess) child.destructProcess();
        });

        // После чего можно разом обнулить содержимое
        #lx:client{
            container.domElem.html('');
            lx.DepthClusterMap.checkFrontMap();
        }

        container.children.reset();
        container.childrenByKeys = {};
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
        if (el === undefined) return super.del();

        var c = this.remove(el, index, count);
        c.forEach(a=>a.destructProcess());
    }

    /*
     * Удаление элементов в вариантах:
     * 1. Аргумент el - элемент - если такой есть в элементе, на котом вызван метод, он будет удален
     * 2. Аргумент el - ключ (единственный аргумент) - удаляется элемент по ключу, если по ключу - массив,
     *    то удаляются все элементы из этого массива
     * 3. Аргументы el (ключ) + index - имеет смысл, если по ключу - массив, удаляется из массива
     * элемент с индексом index в массиве
     * 4. Аргументы el (ключ) + index + count - как 4, но удаляется count элементов начиная с index
     * */
    remove(el, index, count) {
        // el - объект
        if (!lx.isString(el)) {
            // Проверка на дурака - не удаляем чужой элемент
            if (el.parent !== this) return false;

            var container = __getContainer(this);

            // Если у элемента есть ключ - будем удалять по ключу
            if (el.key && el.key in container.childrenByKeys) return this.remove(el.key, el._index, 1);

            // Если ключа нет - удаляем проще
            var result = new lx.Collection();
            var pre = el.prevSibling();
            container.domElem.removeChild(el.domElem);
            container.children.remove(el);
            #lx:client{ lx.DepthClusterMap.checkFrontMap(); }
            container.positioning().actualize({from: pre, deleted: [el]});
            container.positioning().onDel();
            result.add(el);
            return result;
        }

        // el - ключ
        var key = el;
        var container = __getContainer(this);
        var result = new lx.Collection();
        if (!(key in container.childrenByKeys)) return result;

        // childrenByKeys[key] - не массив, элемент просто удаляется
        if (!lx.isArray(container.childrenByKeys[key])) {
            var elem = container.childrenByKeys[key],
                pre = elem.prevSibling();
            container.domElem.removeChild(elem.domElem);
            container.children.remove(elem);
            #lx:client{ lx.DepthClusterMap.checkFrontMap(); }
            delete container.childrenByKeys[key];
            container.positioning().actualize({from: pre, deleted: [elem]});
            container.positioning().onDel();
            result.add(elem);
            return result;
        }

        // childrenByKeys[key] - массив
        if (count === undefined) count = 1;
        if (index === undefined) {
            index = 0;
            count = container.childrenByKeys[key].length;
        } else if (index >= container.childrenByKeys[key].length) return result;
        if (index + count > container.childrenByKeys[key].length)
            count = container.childrenByKeys[key].length - index;

        var deleted = [],
            pre = container.childrenByKeys[key][index].prevSibling();
        for (var i=index,l=index+count; i<l; i++) {
            var elem = container.childrenByKeys[key][i];

            deleted.push(elem);
            container.domElem.removeChild(elem.domElem);
            container.children.remove(elem);
        }
        #lx:client{ lx.DepthClusterMap.checkFrontMap(); }

        container.childrenByKeys[key].splice(index, count);
        for (var i=index,l=container.childrenByKeys[key].length; i<l; i++)
            container.childrenByKeys[key][i]._index = i;
        if (!container.childrenByKeys[key].length) {
            delete container.childrenByKeys[key];
        } else if (container.childrenByKeys[key].length == 1) {
            container.childrenByKeys[key] = container.childrenByKeys[key][0];
            delete container.childrenByKeys[key]._index;
        }
        container.positioning().actualize({from: pre, deleted});
        container.positioning().onDel();
        result.add(deleted);
        return result;
    }

    text(text) {
        var container = __getContainer(this);

        if (text === undefined) {
            if ( !container.contains('text') ) return '';
            return container->text.value();
        }

        if (!container.contains('text')) new lx.TextBox({parent: container});

        container->text.value(text);
        return container;
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
        this.eachChild(c=>c.visibility(c.key == key));
    }
    /* 2. Content managment */
    //==================================================================================================================


    //==================================================================================================================
    /* 3. Content navigation */

    isEmpty() {
        var container = __getContainer(this);
        return container.children.isEmpty();
    }

    /**
     *
     * */
    get(path) {
        var result = __get(this, path);
        if (result) return result;

        var container = __getContainer(this);
        if (container !== this) result = __get(container, path);
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

    getChildIndex(child) {
        var container = __getContainer(this);
        return container.children.indexOf(child);
    }

    contains(key) {
        var container = __getContainer(this);

        if (key instanceof lx.Rect) {
            if (key.key) {
                if (!(key.key in container.childrenByKeys)) return false;
                if (lx.isArray(container.childrenByKeys[key.key])) {
                    if (key._index === undefined) return false;
                    return container.childrenByKeys[key.key][key._index] === key;
                }
                return container.childrenByKeys[key.key] === key;
            } else {
                return container.children.contains(key);
            }
        }

        return (key in container.childrenByKeys);
    }

    childrenCount(key) {
        var container = __getContainer(this);

        if (key === undefined) return container.children.count();

        if (!container.childrenByKeys[key]) return 0;
        if (!lx.isArray(container.childrenByKeys[key])) return 1;
        return container.childrenByKeys[key].len;
    }

    child(num) {
        var container = __getContainer(this);
        return container.children.get(num);
    }

    lastChild() {
        var container = __getContainer(this);
        return container.children.last();
    }

    divideChildren(info) {
        var all = info.all !== undefined ? info.all : false;
        if (info.hasProperty) info.hasProperties = [info.hasProperty];
        var match = info.notMatch === true
            ? null
            : new lx.Collection(),
            notMatch = info.match === true
            ? null
            : new lx.Collection();
        function rec(el) {
            if (el === null || !el.childrenCount) return;
            for (var i=0; i<el.childrenCount(); i++) {
                var child = el.child(i),
                    matched = true;
                if (!child) continue;

                if (info.callback) matched = info.callback(child);

                if (matched && info.hasProperties) {
                    var prop = info.hasProperties;
                    if (lx.isObject(prop)) {
                        for (var j in prop)
                            if (!(j in child) || child[j] != prop[j]) { matched = false; break; }
                    } else if (lx.isArray(prop)) {
                        for (var j=0, l=prop.len; j<l; j++)
                            if (!(prop[j] in child)) { matched = false; break; }
                    }
                }

                if (matched) {
                    if (match) match.add(child);
                } else {
                    if (notMatch) notMatch.add(child);
                }
                if (all) rec(child);
            }
        }
        rec(__getContainer(this));
        return {match, notMatch};
    }

    /**
     * Получение коллекции потомков с учетом переданных условий
     * Варианты:
     * 1. getChildren()  - вернет своих непосредственных потомков
     * 2. getChildren(true)  - вернет всех потомков, всех уровней вложенности
     * 3. getChildren((a)=>{...})  - из своих непосредственных потомков вернет тех, для кого коллбэк вернет true
     * 4. getChildren((a)=>{...}, true)  - из всех своих потомков вернет тех, для кого коллбэк вернет true
     * 5. getChildren({hasProperty:''}) | getChildren({hasProperties:[]})
     * 6. getChildren({hasProperties:[], all:true})
     * 7. getChildren({callback:(a)=>{...}})  - см. 3.
     * 8. getChildren({callback:(a)=>{...}, all:true})  - см. 4.
     * */
    getChildren(info={}, all=false) {
        if (info === true) info = {all:true};
        if (lx.isFunction(info)) info = {callback: info, all};
        info.match = true;
        return this.divideChildren(info).match;
    }

    /**
     * Проход по всем потомкам без построения промежуточных структур - самый производительный метод для этой цели
     * */
    eachChild(func, all=false) {
        function re(elem) {
            if (!elem.child) return;
            var num = 0,
                child = elem.child(num);
            while (child) {
                func(child);
                if (all) re(child);
                child = elem.child(++num);
            }
        }
        re(__getContainer(this));
    }
    /* 3. Content navigation */
    //==================================================================================================================


    //==================================================================================================================
    /* 4. PositioningStrategies */
    setPositioning(constructor, config) {
        var container = __getContainer(this);
        container.positioningStrategy = new constructor(container, config);
    }

    positioning() {
        var container = __getContainer(this);
        if (container.positioningStrategy) return container.positioningStrategy;
        return new lx.PositioningStrategy(container);
    }

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
            if (container.positioningStrategy.lxFullClassName() == strategy.lxFullName())
                return container.positioningStrategy;
            container.positioningStrategy.clear();
        }
        container.positioningStrategy = (strategy === lx.PositioningStrategy)
            ? null
            : new strategy(container);
        return container.positioningStrategy;
    }

    align(horizontal, vertical) {
        var pos = this.preparePositioningStrategy(lx.AlignPositioningStrategy);
        if (!pos) return this;

        if (vertical === undefined && lx.isObject(horizontal)) pos.init(horizontal);
        else pos.init({horizontal, vertical});
        return this;
    }

    map(config) {
        var pos = this.preparePositioningStrategy(lx.MapPositioningStrategy);
        if (pos) pos.init(config);
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

    getStreamDirection() {
        if (!this.positioningStrategy || this.positioningStrategy.lxClassName() != 'StreamPositioningStrategy')
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

    gridStream(config={}) {
        config.type = lx.GridPositioningStrategy.TYPE_STREAM;
        return this.grid(config);
    }

    gridAdaptive(config={}) {
        config.type = lx.GridPositioningStrategy.TYPE_ADAPTIVE;
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

    tryChildReposition(elem, param, val) {
        return this.positioning().tryReposition(elem, param, val);
    }

    childHasAutoresized(elem) {
        this.positioning().reactForAutoresize(elem);
    }
    /* 4. PositioningStrategies */
    //==================================================================================================================


    #lx:client { /*client BEGIN*/
    //==================================================================================================================
    /* 5. Load */
    /**
     * Загружает уже полученные данные о модуле в элемент
     * */
    setPlugin(info, attributes = {}, func = null) {
        this.dropPlugin();
        if (!attributes.lxEmpty()) {
            if (!info.attributes) info.attributes = {};
            info.attributes.lxMerge(attributes, true);
        }
        lx.Loader.run(info, __getContainer(this), this.getPlugin(), func);
    }

    /**
     * Удаляет плагин из элемента, из реестра плагинов и всё, что связано с плагином
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

    unbind() {
        lx.Binder.unbindWidget(this);
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
        if (args.len == 1 && lx.isObject(args[0])) config = args[0];
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

    dropMatrix() {
        lx.Binder.unbindMatrix(this);
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
        list = self.childrenByKeys;
    for (var i=0,l=arr.length; i<l; i++) {
        var key = arr[i].split('['),
            index = (key.len > 1) ? parseInt(key[1]) : null;
        key = key[0];
        if (!(key in list)) return null;
        if (i+1 == l) {
            if (index === null) return list[key];
            return list[key][index];
        }
        list = (index === null)
            ? list[key].childrenByKeys
            : list[key][index].childrenByKeys;
    }
}

#lx:client {
    function __checkParentRenderCache(self) {
        if (!self.renderCacheStatus && self.domElem.parent && self.domElem.parent.renderCacheStatus) {
            self.useRenderCache();
        }
    }

    function __addToRenderCache(self, widget) {
        self.renderCache++;
    }

    function __renderContent(self) {
        var arr = [];

        if (!self.children || self.children.isEmpty()) return self.domElem.content;

        self.eachChild((child)=>{
            if (child.domElem.rendered()) arr.push(child.domElem.outerHtml());
            else arr.push(__render(child));
        });
        return arr.join('');
    }

    function __render(self) {
        return self.domElem.getHtmlStringBegin() + __renderContent(self) + self.domElem.getHtmlStringEnd();
    }

    function __refreshAfterRender(self) {
        if ( ! self.children) return;

        var childNum = 0,
            elemNum,
            child = self.child(childNum),
            elemsList = self.getDomElem().children;
        while (child) {
            elemNum = childNum;
            var elem = elemsList[elemNum];
            child.domElem.refreshElem(elem);
            child.trigger('displayin');
            __refreshAfterRender(child);

            child = self.child(++childNum);
        }

        delete self.renderCacheStatus;
        delete self.renderCache;
    }
};