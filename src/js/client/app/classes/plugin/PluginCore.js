#lx:namespace lx;
class PluginCore {
    constructor(plugin) {
        this.plugin = plugin;
    }
    
    getPlugin() {
        return this.plugin;
    }

    getGuiNode(name) {
        return this.getPlugin().getGuiNode(name);
    }
}
