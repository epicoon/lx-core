#lx:namespace lx;
class Snippet {
    constructor(widget, info) {
        this.widget = widget;
        widget.snippet = this;
        this.attributes = info.attributes || {};
        if (info.meta) __setMeta(this, info.meta);
    }

    #lx:require ../src/Snippet;

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

    setScreenModes(map) {
        __setScreenModes(this, map);
    }

    onLoad(callback) {
        this.onLoadCallback = callback;
    }

    setLoaded() {
        var code = this.onLoadCallback.toString();
        delete this.onLoadCallback;
        lx.app.functionHelper.createAndCallFunctionWithArguments({
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
