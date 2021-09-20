#lx:private;

class Snippet #lx:namespace lx {
    constructor(widget, info) {
        this.widget = widget;
        widget.snippet = this;
        this.attributes = info.attributes || {};
        if (info.meta) __setMeta(this, info.meta);
    }

    run() {
        if (this.screenWatcher) this.widget.trigger('resize');
        delete this.run;
    }

    get(key) {
        return this.widget.get(key);
    }

    find(key) {
        return this.widget.find(key);
    }

    addSnippet(snippetPath, config = {}) {
        var widgetClass = config.widget || lx.Box;
        var attributes = config.lxExtract('attributes') || {};
        var config = (config.config) ? config.config : config;
        if (!config.key) {
            // слэши заменяются, т.к. в имени задается путь и может их содержать, а ключ должен быль одним словом
            config.key = lx.isString(snippetPath)
                ? snippetPath.replace('/', '_')
                : snippetPath.snippet.replace('/', '_');
        }

        var widget = new widgetClass(config);

        widget.setSnippet({
            path: snippetPath,
            attributes
        });
        return widget.snippet;
    }

    addSnippets(list, commonPath = '') {
        var result = [];
        for (var key in list) {
            var snippetConfig = list[key],
                path = '';

            if (lx.isNumber(key)) {
                if (lx.isObject(snippetConfig)) {
                    if (!snippetConfig.path) continue;
                    path = snippetConfig.path;
                } else if (lx.isString(snippetConfig)) {
                    path = snippetConfig;
                    snippetConfig = {};
                } else continue;
            } else if (lx.isString(key)) {
                path = key;
                if (!lx.isObject(snippetConfig)) snippetConfig = {};
            }

            if (snippetConfig.config) snippetConfig.config.key = path;
            else snippetConfig.key = path;

            var snippetPath = lx.isString(path)
                ? commonPath + path
                : path;
            result.push(this.addSnippet(snippetPath, snippetConfig));
        }

        return result;
    }

    setScreenModes(map) {
        __setScreenModes(this, map);
    }

    onLoad(callback) {
        this.onLoadCallback = callback;
    }

    setLoaded() {
        var code = this.onLoadCallback.toString();
        delete this.onLoadCallback;
        lx._f.createAndCallFunctionWithArguments({
            Plugin: this.widget.getPlugin(),
            Snippet: this
        }, code);
    }
}

function __setMeta(self, meta) {
    if (meta.sm) __setScreenModes(self, meta.sm);
}

function __setScreenModes(self, map) {
    self.screenWatcher = new ScreenWatcher(self, map);
    if (!self.widget.hasTrigger('resize', __onResize))
        self.widget.on('resize', __onResize);
}

function __onResize(event) {
    var snippet = this.snippet;
    if (!snippet.screenWatcher.checkModeChange()) return;
    function rec(el) {
        el.trigger('changeScreenMode', event, snippet.screenWatcher.mode);
        if (!el.childrenCount) return;
        for (var i=0; i<el.childrenCount(); i++) {
            var child = el.child(i);
            if (!child || !child.getDomElem()) continue;
            rec(child);
        }
    }
    rec(this);
}

class ScreenWatcher {
    constructor(snippet, map) {
        this.snippet = snippet;
        var modes = [];
        for (var i in map) {
            var item = {
                name: i,
                lim: map[i]
            };
            if (item.lim == 'INF') item.lim = Infinity;
            modes.push(item);
        }
        modes.sort(function(a, b) {
            if (a.lim > b.lim) return 1;
            if (a.lim < b.lim) return -1;
            return 0;
        });
        this.map = modes;
        // this.idenfifyScreenMode();
    }

    checkModeChange() {
        var currentMode = this.mode;
        this.idenfifyScreenMode();
        return (currentMode != this.mode);
    }

    idenfifyScreenMode() {
        var w = this.snippet.widget.width('px'),
            mode;
        for (var i=0, l=this.map.length; i<l; i++) {
            if (w <= this.map[i].lim) {
                mode = this.map[i].name;
                break;
            }
        }
        this.mode = mode;
    };
}
