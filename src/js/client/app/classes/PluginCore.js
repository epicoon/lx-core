#lx:namespace lx;
class PluginCore {
    constructor(plugin) {
        this.plugin = plugin;
        this.init();
        this.loadReferences();
        this.initHandlers();
        this.subscribeEvents();
    }

    init() {
        // pass
    }

    loadReferences() {
        // pass
    }

    initHandlers() {
        // pass
    }

    subscribeEvents() {
        // pass
    }
    
    getPlugin() {
        return this.plugin;
    }

    getGuiNode(name) {
        return this.getPlugin().getGuiNode(name);
    }
}
