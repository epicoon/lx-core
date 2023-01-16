#lx:client {
    #lx:require ../src/pluginSrcClient/;
}
#lx:server {
    #lx:require ../src/pluginSrcServer/;
}

#lx:namespace lx;
class Plugin {
    constructor(config = {}) {
        if (config.preseted) lx.app.cssManager.registerPreseted(config.preseted);
        __construct(this, config);
    }

    set title(value) {
        __setTitle(this, value);
    }

    get title() {
        return __getTitle(this);
    }

    get cssPreset() {
        return lx.app.cssManager.getPreset(this._cssPreset);
    }

    getCssAssetClasses() {
        return [];
    }

    initCss(css) {
        this.getCssAssetClasses().forEach(assetClass => {
            const asset = new assetClass(this);
            asset.init(css);
        });
    }

    /**
     * Метод для переопределения оформления виджетов для конкретных плагинов
     */
    getWidgetBasicCss(widgetClass) {
        if (widgetClass in this.widgetBasicCssList)
            return this.widgetBasicCssList[widgetClass];
        return null;
    }

    getImagesPath(key = 'default') {
        if (key in this.images) return this.images[key] + '/';
        return null;
    }

    getImage(name) {
        return self::resolveImage(this.images, name);
    }

    static resolveImage(map, name) {
        if (name[0] != '@') {
            if (!map['default']) return name;
            return map['default'] + '/' + name;
        }

        var arr = name.match(/^@([^\/]+?)(\/.+)$/);
        if (!arr || !map[arr[1]]) return '';

        return map[arr[1]] + arr[2];
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * CLIENT
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    #lx:client {
        /**
         * Удалить плагин - очистить корневой сниппет, удалить из реестра, удалить все связанные данные
         */
        del() {
            __destruct(this);
        }

        init() {
            // pass
        }

        getCoreClass() {
            return null;
        }

        getCore() {
            return this.core;
        }

        getGuiNodeClasses() {
            return {};
        }

        initGuiNodes(map) {
            for (let name in map) {
                let className = map[name];
                if (lx.isString(className) && !lx.classExists(className)) continue;
                let box = this.findOne(name);
                if (box === null) continue;
                this.guiNodes[name] = lx.isString(className)
                    ? lx.createObject(className, [this, box])
                    : new className(this, box);
            }
        }

        getGuiNode(name) {
            return this.guiNodes[name];
        }

        beforeRender() {
            // pass
        }

        beforeRun() {
            const coreClass = this.getCoreClass();
            if (coreClass) this.core = new coreClass(this);
            this.initGuiNodes(this.getGuiNodeClasses());
        }

        run() {
            // pass
        }

        get eventDispatcher() {
            if (!this._eventDispatcher)
                this._eventDispatcher = new lx.EventDispatcher();
            return this._eventDispatcher;
        }

        on(eventName, callback) {
            this.eventDispatcher.subscribe(eventName, callback);
        }

        onEvent(callback) {
            this.eventCallbacks.push(callback);
        }

        trigger(eventName, data = {}) {
            if (eventName == 'focus') {
                if (this._onFocus) this._onFocus();
                return;
            }
            if (eventName == 'unfocus') {
                if (this._onUnfocus) this._onUnfocus();
                return;
            }

            var event = new lx.PluginEvent(this, eventName, data);
            this.eventDispatcher.trigger(eventName, event);
            this.eventCallbacks.forEach(c=>c(event));
        }

        focus() {
            lx.app.plugins.focusPlugin(this);
        }

        unfocus() {
            lx.app.plugins.unfocusPlugin(this);
        }

        onFocus(callback) {
            this._onFocus = callback.bind(this);
        }

        onUnfocus(callback) {
            this._onUnfocus = callback.bind(this);
        }

        setKeypressManager(className) {
            let manager = lx.createObject(className);
            manager.setContext({plugin: this});
            manager.run();
            this._keypressManager = manager;
        }

        onKeydown(key, func) {
            lx.app.keyboard.onKeydown(key, func, {plugin:this});
        }

        offKeydown(key, func) {
            lx.app.keyboard.offKeydown(key, func, {plugin:this});
        }

        onKeyup(key, func) {
            lx.app.keyboard.onKeyup(key, func, {plugin:this});
        }

        offKeyup(key, func) {
            lx.app.keyboard.offKeyup(key, func, {plugin:this});
        }

        /**
         * Проверяет является ли данный плагин основым по отношению к формированию страницы (соответствие текущему url)
         */
        isMainContext() {
            return this.isMain;
        }

        onDestruct(callback) {
            this.destructCallbacks.push(callback);
        }

        /**
         * Вернет все дочерние плагины - только непосредственные если передать false (по умолчанию), вообще все если передать true
         */
        childPlugins(all=false) {
            var result = [];

            const list = lx.app.plugins.getList();
            for (var i in list) {
                let plugin = list[i];
                if (all) {
                    if (plugin.hasAncestor(this))
                        result.push(plugin);
                } else {
                    if (plugin.parent === this)
                        result.push(plugin);
                }
            }

            return result;
        }

        /**
         * Проверяет есть ли переданный плагин в иерархии родительских
         */
        hasAncestor(plugin) {
            var parent = this.parent;
            while (parent) {
                if (parent === plugin) return true;
                parent = parent.parent;
            }
            return false;
        }

        useModule(moduleName, callback = null) {
            this.useModules([moduleName], callback);
        }

        useModules(moduleNames, callback = null) {
            var newForPlugin = [];
            if (this.dependencies.modules) {
                for (var i=0, l=moduleNames.len; i<l; i++)
                    if (!this.dependencies.modules.includes(moduleNames[i]))
                        newForPlugin.push(moduleNames[i]);
            } else newForPlugin = moduleNames;

            if (!newForPlugin.len) return;

            if (!this.dependencies.modules) this.dependencies.modules = [];

            lx.app.loader.loadModules({
                modules: newForPlugin,
                callback: ()=>{
                    newForPlugin.forEach(a=>this.dependencies.modules.push(a));
                    if (callback) callback();
                }
            });
        }

        /**
         * AJAX-запрос в пределах плагина
         */
        ajax(respondent, data=[]) {
            return new lx.PluginRequest(this, respondent, data);
        }

        /**
         * Регистрация активного GET AJAX-запроса, который будет актуализировать состояние url
         */
        registerActiveRequest(key, respondent, handlers, useServer=true) {
            if (!this.activeRequestList)
                this.activeRequestList = new AjaxGet(this);
            this.activeRequestList.registerActiveUrl(key, respondent, handlers, useServer);
        }

        /**
         * Вызов активного GET AJAX-запроса, если он был зарегистрирован
         */
        activeRequest(key, data={}) {
            if (this.activeRequestList)
                this.activeRequestList.request(key, data);
        }

        // todo селекторы
        get(path) {
            return this.root.get(path);
        }

        // поиск по ключу виджетов любого уровня вложенности
        find(key, all) {
            var c = this.root.find(key, all);
            if (this.root.key == key) c.add(this.root);
            if (c.empty) return null;
            if (c.len == 1) return c.at(0);
            return c;
        }

        findOne(key, all) {
            var c = this.root.find(key, all);
            if (c instanceof lx.Rect) return c;
            if (this.root.key == key) c.add(this.root);
            if (c.empty) return null;
            return c.at(0);
        }

        subscribeNamespacedClass(namespace, className) {
            //TODO
            console.log('Plugin.subscribeNamespacedClass:', namespace, className);
        }
    }

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * SERVER
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
    #lx:server {
        set icon(value) {
            this._icon = value;
            this._changes.icon = value;
        }

        get icon() {
            return this._icon;
        }

        findFile(fileName) {

        }

        getDependencies() {
            return {};
        }

        getResult() {
            var result = {};

            var changedAttributes = {};
            for (var key in this.attributes) {
                if (!(key in this._oldAttributes)
                    || (!this.attributes[key].lxCompare(this._oldAttributes[key]))
                ) {
                    changedAttributes[key] = this.attributes[key];
                }
            }

            if (!changedAttributes.lxEmpty()) result.attributes = changedAttributes;
            if (this._changes.title) result.title = this._changes.title;
            if (this._changes.icon) result.icon = this._changes.icon;

            return result;
        }
    }
}
