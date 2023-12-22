#lx:module lx.Box;

#lx:use lx.Rect;
#lx:use lx.TextBox;

#lx:use lx.IndentData;
#lx:use lx.PositioningStrategy;
#lx:use lx.StreamPositioningStrategy;
#lx:use lx.GridPositioningStrategy;
#lx:use lx.AlignPositioningStrategy;
#lx:use lx.SlotPositioningStrategy;
#lx:use lx.MapPositioningStrategy;

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
 * imagePath(name)
 * renderHtml()
 * addChild(elem, config = {})
 * modifyNewChildConfig(config)
 * insert(c, next, config={})
 * add(type, count=1, config={}, configurator={})
 * clear()
 * del(el, index, count)
 * text(text)
 * setEditable(value = true)
 * isEditable()
 * isEditing()
 * edit()
 * blur(e)
 * showOnlyChild(key)
 * scrollTo(adr)
 * getScrollPos()
 * getScrollSize()
 * checkResizeChild(callback)
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
 * align(hor, vert, els)
 * stream(config)
 * streamProportional(config={})
 * getStreamDirection()
 * grid(config)
 * gridProportional(config={})
 * gridStream(config={})
 * gridAdaptive(config={})
 * slot(config)
 * setIndents(config)
 * tryChildReposition(elem, param, val)
 * childHasAutoresized(elem)
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
 * @widget lx.Box
 *
 * @events [
 *     contentResize,
 *     beforeAddChild,
 *     afterAddChild,
 *     xScrollBarOn,
 *     xScrollBarOff,
 *     xScrollBarChange,
 *     yScrollBarOn,
 *     yScrollBarOff,
 *     yScrollBarChange,
 *     scrollBarChange,
 *     scroll,
 *     blur
 * ]
 */
#lx:namespace lx;
class Box extends lx.Rect {

    //==================================================================================================================
    /* 1. Constructor */

    /**
     * @widget-init
     *
     * @param [config] {Object: {
     *     #merge(lx.Rect::constructor::config),
     *     [text] {String},
     *     [align]              {Object: #schema(lx.AlignPositioningStrategy::applyConfig::config)},
     *     [stream]             {Object: #schema(lx.StreamPositioningStrategy::applyConfig::config)},
     *     [streamProportional] {Object: #schema(lx.StreamPositioningStrategy::applyConfig::config)},
     *     [grid]             {Object: #schema(lx.GridPositioningStrategy::applyConfig::config)},
     *     [gridProportional] {Object: #schema(lx.GridPositioningStrategy::applyConfig::config)},
     *     [gridStream]       {Object: #schema(lx.GridPositioningStrategy::applyConfig::config)},
     *     [gridAdaptive]     {Object: #schema(lx.GridPositioningStrategy::applyConfig::config)},
     *     [slot]             {Object: #schema(lx.SlotPositioningStrategy::applyConfig::config)}
     * }}
     */
    build(config) {
        super.build(config);

        if ( config.text ) this.text( config.text );

        if (config.align)
            lx.isArray(config.align)
                ? this.align.call(this, ...config.align)
                : this.align(lx.isObject(config.align) ? config.align : {});
        if (config.stream)
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

    #lx:client {
        __construct() {
            super.__construct();
            this.children = new BoxChildren();
            this.childrenByKeys = {};
        }

        clientBuild(config) {
            super.clientBuild(config);
            var sizes = this.getScrollSize();
            this.__sizeHolder.refreshContent(sizes.width, sizes.height);
            this.on('resize', self::onresize);
            this.on('scrollBarChange', self::onresize);
        }

        postUnpack(config) {
            super.postUnpack(config);
            if (this.lxExtract('__na'))
                this.positioningStrategy.actualize();
        }

        restoreLinks(loader) {
            if (this._container)
                this._container = loader.getWidget(this._container);
        }

        destruct() {
            var container = __getContainer(this);
            #lx:client {
                container.dropPlugin();
            }

            this.setBuildMode(true);
            this.eachChild((child)=>{
                if (child.destructProcess) child.destructProcess();
            });
            this.setBuildMode(false);

            super.destruct();
        }

        checkResize(e) {
            let resized = super.checkResize(e);
            if (resized) this.children.forEach(c=>c.checkResize(e));
            return resized;
        }

        checkContentResize(e) {
            if (e && e.boxInitiator === this) return;
            e = e || this.newEvent({
                boxInitiator: this
            });
            let sizes = this.getScrollSize(),
                res = this.__sizeHolder.refreshContent(sizes.width, sizes.height);
            if (res) {
                this.trigger('contentResize');
                const container = this.getContainer();
                if (container !== this)
                    container.trigger('contentResize');
                if (!this.checkResize(e))
                    this.children.forEach(c=>c.checkResize(e));
            }
            return res;
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
            if (this._container) this._container = this._container.renderIndex;
        }
    }

    static getCommonEventNames() {
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
        if (val === undefined)
            return this.style('overflow');
        __eachContainer(this, c=>super.overflow.call(c, val));
    }
    /* 1. Constructor */
    //==================================================================================================================


    //==================================================================================================================
    /* 2. Content managment */

    imagePath(name) {
        if (name[0] == '/') return name;

        #lx:server {
            return this.getPlugin().getImage(name);
        }

        #lx:client {
            if (this.imagesMap) return lx.Plugin.resolveImage(this.imagesMap, name);

            const c = __getContainer(this);
            if (c.plugin)
                return c.plugin.getImage(name);

            if (this.parent)
                return this.parent.imagePath(name);

            return name;
        }
    }

    renderHtml() {
        const renderContent = function(node) {
            if (!node.children || node.children.isEmpty()) return node.domElem.content;
            let arr = [];
            let mode = node.__buildMode;
            node.setBuildMode(true);
            node.eachChild(child=>{
                if (child.domElem.rendered()) arr.push(child.domElem.outerHtml());
                else arr.push(render(child));
            });
            node.setBuildMode(mode);
            return arr.join('');
        }

        const render = function(node) {
            return node.domElem.getHtmlStringBegin() + renderContent(node) + node.domElem.getHtmlStringEnd();
        }

        return renderContent(this);
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
            let cssPreset = null;
            if (attributes === undefined && lx.isObject(name)) {
                attributes = name.attributes;
                cssPreset = name.cssPreset || App.getCssPreset();
                name = name.name;
            } else cssPreset = App.getCssPreset();

            if (!lx.isString(name)) return;

            var container = __getContainer(this);
            container.pluginAnchor = App.genId();

            var data = {name, anchor:container.pluginAnchor, cssPreset};
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
            var maker = lx.app.snippetMap.getSnippetMaker(config.path);
            if (!maker) return;

            var container = __getContainer(this);
            container.isSnippet = true;

            if (!config.attributes) config.attributes = {};
            var snippet = new lx.Snippet(this, config);

            this.useRenderCache();
            this.begin();
            maker(this.getPlugin(), snippet);
            snippet.setLoaded();
            this.end();
            this.applyRenderCache();
        }

        useRenderCache() {
            if (this.renderCacheStatus !== undefined) return;

            this.stopPositioning();
            this.renderCacheStatus = true;
            this.renderCache = 0;
            var container = __getContainer(this);
            if (container !== this) {
                container.renderCacheStatus = true;
                container.renderCache = 0;
            }
        }

        applyRenderCache() {
            // Если элемент не существует - применять некуда. Скорее всего, этот элемент сам находится в кэше
            // и применять кэш нужно на уровень выше
            if (!this.getDomElem()) return;

            // Если на элементе не стартовали сбор кэша
            if (!this.renderCacheStatus) return;

            delete this.renderCacheStatus;

            // Если ничего не добавлялось
            if (this.renderCache === 0) {
                let container = __getContainer(this);
                // Возможно, менялось содержимое
                if (container === this) {
                    this.getChildren(c=>{if (c.applyRenderCache) c.applyRenderCache()});
                    this.startPositioning();
                // Элемент является обёрткой над контейнером
                } else container.applyRenderCache();
                return;
            }

            let text = this.renderHtml();
            this.domElem.html(text);
            __refreshAfterRender(this);
            this.startPositioning();
            this.checkContentResize();
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
    _getContainer() {
        if (this._container) return this._container;
        return this;
    }

    getContainer() {
        let temp = this;
        let contaner = temp._getContainer();
        if (contaner) {
            while (contaner !== temp) {
                temp = contaner;
                contaner = temp._getContainer();
            }
        }
        return temp;
    }
    
    setContainer(box) {
        this._container = box;
    }

    /**
     * Метод, используемый новым виджетом для регистрации в родителе
     */
    addChild(widget, config = {}) {
        #lx:client {
            this.trigger('beforeAddChild', this.newEvent({child: widget}));
        }

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

        #lx:client {
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

                this.checkContentResize();
                widget.trigger('displayin');
            }

            this.trigger('afterAddChild', this.newEvent({child: widget}));
        }
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
     */
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
     */
    add(type, count=1, config={}, configurator={}) {
        var conf = (lx.isObject(count)) ? count : config;
        if (conf.buildMode)
            return this.addStructure(type, count, config, configurator);
        return this.addContent(type, count, config, configurator);
    }

    addContainer() {
        if (!this.isEmpty())
            throw 'You can create container only in empty box';

        this.setContainer(this.add(lx.Box, {geom: true}));
    }

    addContent(type, count=1, config={}, configurator={}) {
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
        delete config.buildMode;

        var result = (count == 1)
            ? new type(config)
            : type.construct(count, config, configurator);

        return result;
    }

    addStructure(type, count=1, config={}, configurator={}) {
        this.setBuildMode(true);
        this.addContent(type, count, config, configurator);
        this.setBuildMode(false);
    }

    /**
     * Удаляет всех потомков
     */
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
        container.positioning().onClearOwner();
        #lx:client{ this.checkContentResize(); }
    }

    /**
     * Удаление элементов в вариантах:
     * 1. Без аргументов - удаление элемента, на котором метод вызван
     * 2. Аргумент el - элемент - если такой есть в элементе, на котом вызван метод, он будет удален
     * 3. Аргумент el - ключ (единственный аргумент) - удаляется элемент по ключу, если по ключу - массив,
     *    то удаляются все элементы из этого массива
     * 4. Аргументы el (ключ) + index - имеет смысл, если по ключу - массив, удаляется из массива
     * элемент с индексом index в массиве
     * 5. Аргументы el (ключ) + index + count - как 4, но удаляется count элементов начиная с index
     */
    del(el, index, count) {
        // ситуация 1 - элемент не передан, надо удалить тот, на котором вызван метод
        if (el === undefined) return super.del();

        var c = this.remove(el, index, count);
        c.forEach(a=>a.destructProcess());
    }

    /**
     * Извлечение элементов в вариантах:
     * 1. Аргумент el - элемент - если такой есть в элементе, на котом вызван метод, он будет удален
     * 2. Аргумент el - ключ (единственный аргумент) - удаляется элемент по ключу, если по ключу - массив,
     *    то удаляются все элементы из этого массива
     * 3. Аргументы el (ключ) + index - имеет смысл, если по ключу - массив, удаляется из массива
     * элемент с индексом index в массиве
     * 4. Аргументы el (ключ) + index + count - как 4, но удаляется count элементов начиная с index
     */
    remove(el, index, count) {
        const container = __getContainer(this);

        // el - объект
        if (!lx.isString(el)) {
            // Не удаляем чужой элемент
            if (el.parent !== this) return false;

            // Если у элемента есть ключ - будем удалять по ключу
            if (el.key && el.key in container.childrenByKeys) return this.remove(el.key, el._index, 1);

            // Если ключа нет - удаляем проще
            var result = new lx.Collection();
            var pre = el.prevSibling();
            container.domElem.removeChild(el.domElem);
            container.children.remove(el);
            #lx:client{ lx.DepthClusterMap.checkFrontMap(); }
            container.positioning().actualize({from: pre, deleted: [el]});
            container.positioning().onElemDel();
            result.add(el);
            #lx:client{ this.checkContentResize(); }
            return result;
        }

        // el - ключ
        var key = el;
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
            container.positioning().onElemDel();
            result.add(elem);
            #lx:client{ this.checkContentResize(); }
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
        container.positioning().onElemDel();
        result.add(deleted);
        #lx:client{ this.checkContentResize(); }
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

    setEditable(value = true) {
        if (value && !this.isEditable()) {
            let container = __getContainer(this);
            if (!container.contains('text'))
                this.add(lx.TextBox);
            container->text.setAttribute('contentEditable','true');
            container.on('click', __handlerEdit);
            container->text.on('blur', __handlerBlur);
        } else if (this.isEditable()) {
            let container = __getContainer(this);
            container->text.removeAttribute('contentEditable');
            container.off('click', __handlerEdit);
            container->text.off('blur', __handlerBlur);
        }
    }

    isEditable() {
        var container = __getContainer(this);
        if (!container.contains('text')) return false;
        return !!container->text.getAttribute('contentEditable');
    }
    
    isEditing() {
        return this.__isEditing;
    }

    edit() {
        if (!this.isEditable() || this.__isEditing) return;
        this.__isEditing = true;

        const textElem = this->text.getDomElem();
        textElem.focus();
        if (textElem.innerText.length) {
            let sel = window.getSelection();
            if (textElem.lastChild.innerText && /(\r\n|\r|\n)/.test(textElem.lastChild.innerText)) {
                sel.collapse(textElem.lastChild, 0);
                return;
            }
            sel.collapse(
                textElem.lastChild.innerText ? textElem.lastChild.firstChild : textElem.lastChild,
                textElem.lastChild.innerText ? textElem.lastChild.innerText.length : textElem.lastChild.length
            );
        }
    }

    blur(e) {
        if (!this.__isEditing) return;
        
        let container = __getContainer(this);
        // container.overflow('hidden');

        let text = container->text.html();
        text = text.replaceAll('<div>', '\r\n');
        text = text.replaceAll('</div>', '');
        container->text.html(text);

        delete this.__isEditing;
        this.trigger('blur', e);
    }

    showOnlyChild(key) {
        this.eachChild(c=>c.visibility(c.key == key));
    }

    scrollTo(adr) {
        if (!lx.isObject(adr)) adr = {y:adr};
        const c = this.getContainer();

        if (adr.x !== undefined) c.domElem.param('scrollLeft', +adr.x);
        if (adr.y !== undefined) c.domElem.param('scrollTop', +adr.y);

        if (adr.xShift !== undefined) {
            let size = c.getScrollSize();
            let shift = Math.round((size.width - c.width('px')) * adr.xShift);
            c.domElem.param('scrollLeft', shift);
        }
        if (adr.yShift !== undefined) {
            let size = c.getScrollSize();
            let shift = Math.round((size.height - c.height('px')) * adr.yShift);
            c.domElem.param('scrollTop', shift);
        }

        this.trigger('scroll');
        return this;
    }

    getScrollPos() {
        const c = this.getContainer();
        return {
            x: c.domElem.param('scrollLeft'),
            y: c.domElem.param('scrollTop')
        };
    }

    getScrollSize() {
        let c = this.getContainer();
        if (!c.getDomElem())
            return {width: null, height: null};

        return {
            width: c.getDomElem().scrollWidth,
            height: c.getDomElem().scrollHeight
        };
    }

    /**
     * @param [config] {Object: {
     *     condition {Fuction},
     *     hint {String},
     *     css {String}
     * }}
     */
    #lx:client setEllipsisHint(config = {}) {
        config.condition = config.condition || function(box) {
            if (lx.isString(this.hint) || box.hintText) return true;
            const elem = box->text;
            if (!elem) return false;
            if (elem.getDomElem().offsetWidth == elem.getDomElem().scrollWidth) return false;
            return true;
        };
        config.hint = config.hint || function(box) {
            return box.hintText || box->text.html();
        }
        this.mouseover(()=>{
            if (!config.condition(this)) return;
            this.__hint = new lx.Box({
                geom: [0, 0, 'auto', 'auto'],
                css: config.css,
                depthCluster: lx.DepthClusterMap.CLUSTER_FRONT
            });
            let hint = '';
            if (lx.isString(config.hint)) hint = config.hint;
            else if (lx.isFunction(config.hint)) hint = config.hint(this);
            this.__hint.html(hint);
            this.__hint.satelliteTo(this);
        });
        this.mouseout(()=>{
            if (this.__hint) this.__hint.del();
        });
    }

    #lx:client hasOverflow(direction = null) {
        if (!this.getDomElem()) return false;

        let c = this.getContainer(),
            scrollSize = c.getScrollSize();
        if (direction == lx.VERTICAL)
            return scrollSize.height - 1 > this.getDomElem().clientHeight;

        if (direction == lx.HORIZONTAL)
            return scrollSize.width - 1 > this.getDomElem().clientWidth;

        return scrollSize.height - 1 > this.getDomElem().clientHeight
            || scrollSize.width - 1 > this.getDomElem().clientWidth;
    }
    /* 2. Content managment */
    //==================================================================================================================


    //==================================================================================================================
    /* 3. Content navigation */

    isEmpty() {
        var container = __getContainer(this);
        return container.children.isEmpty();
    }

    get(path) {
        var result = __get(this, path);
        if (result) return result;

        var container = __getContainer(this);
        if (container !== this) result = __get(container, path);
        return result;
    }

    /**
     * Возвращает всегда коллекцию потомков
     */
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
        if (lx.isNumber(num)) return container.children.get(num);
        if (lx.isFunction(num)) return container.children.getByCondition(num);
        return null;
    }

    lastChild() {
        var container = __getContainer(this);
        return container.children.last();
    }

    divideChildren(info) {
        const all = (info.all !== undefined) ? info.all : false;
        if (info.hasProperty) info.hasProperties = [info.hasProperty];
        const match = (info.notMatch === true) ? null : new lx.Collection();
        const notMatch = info.match === true ? null : new lx.Collection();
        function rec(el) {
            if (el === null || !el.childrenCount) return;
            for (let i=0; i<el.childrenCount(); i++) {
                const child = el.child(i);
                if (!child) continue;

                let matched = true;
                if (info.callback) matched = info.callback(child);

                if (matched && info.hasProperties) {
                    let prop = info.hasProperties;
                    if (lx.isObject(prop)) {
                        for (let j in prop) {
                            if (!(j in child)) { matched = false; break; }
                            let val = prop[j];
                            if (lx.isArray(val)) {
                                if (!val.includes(child[j])) { matched = false; break; }
                            } else {
                                if (child[j] != val) { matched = false; break; }
                            }
                        }
                    } else if (lx.isArray(prop)) {
                        for (let j=0, l=prop.len; j<l; j++)
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
     */
    getChildren(info={}, all=false) {
        if (info === true) info = {all:true};
        if (lx.isFunction(info)) info = {callback: info, all};
        info.match = true;
        return this.divideChildren(info).match;
    }

    /**
     * Проход по всем потомкам без построения промежуточных структур - самый производительный метод для этой цели
     */
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
        const container = __getContainer(this);
        if (container.positioningStrategy) {
            container.positioningStrategy.autoActualize = true;
            container.positioningStrategy.actualize();
        }
    }

    /**
     * @positioning lx.AlignPositioningStrategy
     * @param horizontal {Number&Enum(lx.LEFT, lx.CENTER, lx.RIGHT)}
     * @param vertical {Number&Enum(lx.TOP, lx.MIDDLE, lx.BOTTOM)}
     */
    align(horizontal, vertical) {
        let config = (vertical === undefined && lx.isObject(horizontal))
            ? horizontal
            : {horizontal, vertical};
        __preparePositioningStrategy(this, lx.AlignPositioningStrategy, config);
        return this;
    }

    /**
     * @positioning lx.MapPositioningStrategy
     * @param [config] {Object: #schema(lx.MapPositioningStrategy::init::config)}
     */
    map(config) {
        __preparePositioningStrategy(this, lx.MapPositioningStrategy, config);
        return this;
    }

    /**
     * @positioning lx.StreamPositioningStrategy[.TYPE_SIMPLE]
     * @param [config] {Object: #schema(lx.StreamPositioningStrategy::init::config)}
     */
    stream(config) {
        __preparePositioningStrategy(this, lx.StreamPositioningStrategy, config);
        return this;
    }

    /**
     * @positioning lx.StreamPositioningStrategy[.TYPE_PROPORTIONAL]
     * @param [config] {Object: #schema(lx.StreamPositioningStrategy::init::config)}
     */
    streamProportional(config={}) {
        config.type = lx.StreamPositioningStrategy.TYPE_PROPORTIONAL;
        return this.stream(config);
    }

    getStreamDirection() {
        if (!this.positioningStrategy || this.positioningStrategy.lxClassName() != 'StreamPositioningStrategy')
            return null;
        return this.positioningStrategy.direction;
    }

    /**
     * @positioning lx.GridPositioningStrategy[.TYPE_SIMPLE]
     * @param [config] {Object: #schema(lx.GridPositioningStrategy::init::config)}
     */
    grid(config) {
        __preparePositioningStrategy(this, lx.GridPositioningStrategy, config);
        return this;
    }

    /**
     * @positioning lx.GridPositioningStrategy[.TYPE_PROPORTIONAL]
     * @param [config] {Object: #schema(lx.GridPositioningStrategy::init::config)}
     */
    gridProportional(config={}) {
        config.type = lx.GridPositioningStrategy.TYPE_PROPORTIONAL;
        return this.grid(config);
    }

    /**
     * @positioning lx.GridPositioningStrategy[.TYPE_STREAM]
     * @param [config] {Object: #schema(lx.GridPositioningStrategy::init::config)}
     */
    gridStream(config={}) {
        config.type = lx.GridPositioningStrategy.TYPE_STREAM;
        return this.grid(config);
    }

    /**
     * @positioning lx.GridPositioningStrategy[.TYPE_ADAPTIVE]
     * @param [config] {Object: #schema(lx.GridPositioningStrategy::init::config)}
     */
    gridAdaptive(config={}) {
        config.type = lx.GridPositioningStrategy.TYPE_ADAPTIVE;
        return this.grid(config);        
    }

    /**
     * @positioning lx.SlotPositioningStrategy
     * @param [config] {Object: #schema(lx.SlotPositioningStrategy::init::config)}
     */
    slot(config) {
        __preparePositioningStrategy(this, lx.SlotPositioningStrategy, config);
        return this;
    }

    setIndents(config) {
        var container = __getContainer(this);
        if (!container.positioningStrategy) return this;
        container.positioningStrategy.setIndents(config);
        container.positioningStrategy.actualize();
        return this;
    }

    dropPositioning() {
        let container = __getContainer(this);
        if (container.positioningStrategy) {
            container.positioningStrategy.clear();
            container.positioningStrategy = null;
        }
    }

    tryChildReposition(elem, param, val) {
        let el = this.getDomElem(),
            container = __getContainer(this);

        if (lx.isObject(param)) {
            let config = param;
            if (!el) {
                for (let paramName in config)
                    container.positioning().tryReposition(elem, lx.Geom.geomConst(paramName), config[paramName]);
                return;
            }

            for (let paramName in config)
                container.positioning().tryReposition(elem, lx.Geom.geomConst(paramName), config[paramName]);
            #lx:client{ elem.checkResize(); }
            return;
        }

        if (!el) {
            this.positioning().tryReposition(elem, param, val);
            return;
        }

        this.positioning().tryReposition(elem, param, val);
        #lx:client{ this.checkContentResize(); }
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
     * Загружает уже полученные данные о плагине в элемент
     */
    setPlugin(info, attributes = {}, func = null) {
        this.dropPlugin();
        if (!attributes.lxEmpty()) {
            if (!info.attributes) info.attributes = {};
            info.attributes.lxMerge(attributes, true);
        }
        lx.app.loader.loadPlugin(info, __getContainer(this), this.getPlugin(), func);
    }

    /**
     * Удаляет плагин из элемента, из реестра плагинов и всё, что связано с плагином
     */
    dropPlugin() {
        const c = __getContainer(this);
        if (c.plugin) {
            c.plugin.del();
            delete c.plugin;
        }
    }

    /* 5. Load */
    //==================================================================================================================


    //==================================================================================================================
    /* 6. Client-features */
    /**
     * Если один аргумент - полная передача конфига:
     * {
     * 	items: lx.Collection,
     * 	itemBox: [Widget, Config],
     * 	itemRender: function(itemBox, model) {}
     *  afterBind: function(itemBox, model) {}
     * 	type: bool
     * }
     * Если три(два) аргумента - краткая передача коллекции и коллбэков:
     * - lx.Collection
     * - Function  - itemRender
     * - Function  - afterBind
     */
    matrix(...args) {
        let config;
        if (args.len == 1 && lx.isObject(args[0])) config = args[0];
        else { config = {
            items: args[0],
            itemRender: args[1],
            afterBind: args[2]
        }; };
        if (!config.itemBox) config.itemBox = self::defaultMatrixItemBox;

        lx.app.binder.makeWidgetMatrix(this, config);
        lx.app.binder.bindMatrix(
            config.items,
            this,
            lx.getFirstDefined(config.type, lx.app.binder.BIND_TYPE_FULL)
        );
    }

    dropMatrix() {
        lx.app.binder.unbindMatrix(this);
    }

    agregator(c, type=lx.app.binder.BIND_TYPE_FULL) {
        lx.app.binder.bindAggregation(c, this, type);
    }
    /* 6. Client-features */
    //==================================================================================================================
    } /*client END*/
}

lx.Box.defaultMatrixItemBox = lx.Box;


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * PRIVATE
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function __preparePositioningStrategy(self, strategy, config) {
    var container = __getContainer(self);
    if (container.positioningStrategy) {
        container.positioningStrategy.clear();
        if (container.positioningStrategy.lxFullClassName() == strategy.lxFullName()) {
            container.positioningStrategy.init(config);
            return container.positioningStrategy;
        }
    }
    container.positioningStrategy = (strategy === lx.PositioningStrategy)
        ? null
        : new strategy(container);
    if (container.positioningStrategy)
        container.positioningStrategy.init(config);
    return container.positioningStrategy;
}


function __getContainer(self) {
    if (self.__buildMode) return self;
    return self.getContainer();
}

function __eachContainer(self, func) {
    func(self);
    let temp = self;
    let contaner = temp._getContainer();
    if (!contaner) return;
    while (contaner !== temp) {
        func(contaner);
        temp = contaner;
        contaner = temp._getContainer();
    }
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
        if (self.renderCache === undefined)
            self.renderCache = 0;
        self.renderCache++;
    }

    function __refreshAfterRender(self) {
        if ( ! self.children) return;

        let mode = self.__buildMode;
        self.setBuildMode(true);
        let childNum = 0,
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
        self.setBuildMode(mode);

        self.startPositioning();
        delete self.renderCacheStatus;
        delete self.renderCache;
    }

    function __handlerEdit(e) {
        this.overflow('auto');
        if (this.getDomElem() !== e.target) return;
        this.edit();
    }

    function __handlerBlur(e) {
        this.parent.blur(e);
    }
};
