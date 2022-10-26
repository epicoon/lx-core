const __data = {
    modules: {},
    css: {},
    scripts: {}
};

/**
 * Карта, описывающая зависимости от модулей - плагины, подписанные на модули
 */
#lx:namespace lx;
class Dependencies extends lx.AppComponent {
    init() {
        this.cache = true;
    }

    getCurrentModules() {
        var result = [];
        for (var name in __data.modules)
            result.push(name);
        return result;
    }

    /**
     * Подписать плагин на ресурсы
     */
    depend(data) {
        for (var i in __data)
            __process(__data[i], data[i] || {}, 1);
    }

    /**
     * При удалении плагина он отписывается от модулей
     * Если на модуль больше никто не подписан и модули не кэшируются, такой модуль будет удален
     */
    independ(data) {
        for (var i in __data)
            __process(__data[i], data[i] || {}, -1);
        __dropZero(this);
    }

    /**
     * Получает список требуемых модулей и выделяет из него тех, о которых нет информации
     */
    defineNecessaryModules(list) {
        if (__data.modules == {}) return list;
        var result = [];
        for (let i=0, l=list.len; i<l; i++)
            if (!(list[i] in __data.modules))
                result.push(list[i]);
        return result;
    }

    defineNecessaryCss(list) {
        if (__data.css.lxEmpty()) return list;
        var result = [];
        for (let i=0, l=list.len; i<l; i++)
            if (!(list[i] in __data.css))
                result.push(list[i]);
        return result;
    }

    defineNecessaryScripts(list) {
        if (__data.scripts == {}) return list;
        var result = [];
        for (let i=0, l=list.len; i<l; i++)
            if (!(list[i].path in __data.scripts))
                result.push(list[i]);
        return result;
    }
}

function __process(data, map, modifier) {
    for (var i=0, l=map.len; i<l; i++) {
        let moduleName = map[i];
        if (!(moduleName in data)) {
            if (modifier == 1) data[moduleName] = 0;
            else continue;
        }

        data[moduleName] += modifier;
    }
}

function __dropZero(self) {
    if (self.cache) return;
    __dropZeroModules();
    __dropZeroCss();
    __dropZeroScripts(__data.scripts);
}

function __dropZeroModules() {
    // Modules are permanent cached in current implementation
}

function __dropZeroCss() {
    for (var name in __data.css) {
        if (__data.css[name] == 0) {
            var asset = lx.app.domSelector.getElementByAttrs({
                href: name,
                name: 'plugin_asset'
            });
            if (!asset) asset = lx.app.domSelector.getElementByAttrs({
                href: name,
                name: 'module_asset'
            });
            asset.parentNode.removeChild(asset);
            delete __data.css[name];
        }
    }
}

function __dropZeroScripts() {
    for (var name in __data.scripts) {
        if (__data.scripts[name] == 0) {
            var asset = lx.app.domSelector.getElementByAttrs({
                src: name,
                name: 'plugin_asset'
            });
            asset.parentNode.removeChild(asset);
            delete __data.scripts[name];
        }
    }
}
