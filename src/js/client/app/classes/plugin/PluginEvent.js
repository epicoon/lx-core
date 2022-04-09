#lx:namespace lx;
class PluginEvent {
	constructor(plugin, data = {}) {
		this.plugin = plugin;
		this.data = data;
	}
}
