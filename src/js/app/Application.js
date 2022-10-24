#lx:require scripts/js_extends;
#lx:require scripts/lx_core;
#lx:require components/common/;
#lx:require classes/common/;
#lx:client {
    #lx:require components/client/;
    #lx:require classes/client/;
}
#lx:server {
    #lx:require components/server/;
    #lx:require classes/server/;
}

let __settings = {};
let __ready = false;
let __onReady = [];
let prefix = null;
let idCounter = 1;

function __componentsMap() {
    return {
        cssManager: lx.CssManager,
        functionHelper: lx.FunctionHelper,
        #lx:client {
            dependencies: lx.Dependencies,
            plugins: lx.PluginsList,
            loader: lx.Loader,
            queues: lx.Queues,
            dialog: lx.Dialog,
            domSelector: lx.DomSelector,
            domEvent: lx.DomEvent,
            cookie: lx.Cookie,
            storage: lx.Storage,
            lifeCycle: lx.LifeCycle,
            binder: lx.Binder,
            snippetMap: lx.SnippetMap,
            alert: lx.Alert,
            tost: lx.Tost,
            keyboard: lx.Keyboard,
            dragAndDrop: lx.DragAndDrop,
            animation: lx.Animation,
            user: lx.User,
        }
    };
}

#lx:namespace lx;
class Application {
    constructor(onReady = []) {
        const map = __componentsMap();
        this.components = {};
        for (let key in map) {
            this.components[key] = new map[key](this);
            Object.defineProperty(this, key, {
                get: function() { return this.components[key]; }
            });
        }
        __onReady = onReady;
    }

    isReady() {
        return __ready;
    }

    genId() {
        var id = __getPrefix() + '_' + lx.Math.decChangeNotation(idCounter, 62);
        idCounter++;
        return id;
    }

    getSetting(name) {
        return __settings[name];
    }

    getCssPreset() {
        return __settings.cssPreset;
    }

    #lx:client {
        start(config, modulesCode, moduleNames, pluginInfo) {
            __settings = config.settings;

            this.domEvent.add(window, 'resize', e=>lx.body.checkResize(e));
            this.keyboard.setWatchForKeypress(true);
            this.dragAndDrop.useElementMoving();
            this.animation.useTimers(true);
            this.animation.useAnimation();

            // Js-модули
            if (modulesCode && modulesCode != '')
                lx.app.functionHelper.createAndCallFunction('', modulesCode);

            __applyComponentsData(this, config.components);

            // Запуск загрузчика
            lx.body = lx.Box.rise(this.domSelector.getBodyElement());
            if (pluginInfo) this.loader.loadPlugin(pluginInfo, lx.body);

            __setReady();

            #lx:mode-case: dev
                var elems = document.getElementsByClassName('lx-var-dump');
                if (!elems.length) return;
                var elem = elems[0];
                var text = elem.innerHTML;
                elem.offsetParent.removeChild(elem);
                lx.alert(text);
            #lx:mode-end;
        }
    }

    #lx:server {
        start(config = {}) {
            if (config.settings) __settings = config.settings;
            if (config.components) __applyComponentsData(this, config.components);
            this.i18nArray = {};
            __setReady();
        }

        useI18n(config) {
            this.i18nArray['_' + lx.HashMd5.hex(config)] = config;
        }

        getDependencies() {
            return {
                i18n: this.i18nArray
            };
        }

        getResult() {
            return {};
        }
    }
}

function __getPrefix() {
    if (prefix === null) {
        var time = (new Date()).getTime();
        prefix = '' + lx.Math.decChangeNotation(time, 62);
    }
    return prefix;
}

function __applyComponentsData(self, components) {
    for (let name in self.components) {
        const component = self.components[name];
        if (component.lxHasMethod('applyData') && (name in components))
            component.applyData(components[name]);
    }
}

function __setReady() {
    __onReady.forEach(f=>f());
    __onReady = [];
    lx.dropReady();
    __ready = true;
}
