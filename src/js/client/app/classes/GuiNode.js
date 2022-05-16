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

    getGuiNode(name) {
        return this.getPlugin().getGuiNode(name);
    }

    getWidget() {
        return this._box;
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
