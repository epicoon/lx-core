#lx:namespace lx;
class GuiNode {
    constructor(plugin, box) {
        this._plugin = plugin;
        this._box = box;
        this._box.guiNode = this;
        this.init();
        this.initHandlers();
        this.subscribeEvents();
    }

    init() {
        // pass
    }

    getPlugin() {
        return this._plugin;
    }
    
    getCore() {
        return this._plugin.core;
    }

    getGuiNode(name) {
        return this.getPlugin().getGuiNode(name);
    }

    getWidget() {
        return this._box;
    }

    get(path) {
        return this._box.get(path);
    }

    find(key, all=true) {
        return this._box.find(key, all);
    }

    triggerPluginEvent(eventName, data) {
        this.getPlugin().trigger(eventName, data);
    }

    initHandlers() {
        // pass
    }

    subscribeEvents() {
        // pass
    }
}
