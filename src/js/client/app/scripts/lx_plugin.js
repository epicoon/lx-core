let _plugin = null;

lx.getPlugin = function(name) {
	for (let key in this.plugins) {
		if (this.plugins[key].name == name) return this.plugins[key];
	}
	return null;
};

lx.getFocusedPlugin = function() {
	return _plugin;
};

lx.focusPlugin = function(plugin) {
	if (_plugin === plugin) return;
	this.unfocusPlugin();
	_plugin = plugin;
	_plugin.trigger('focus');
};

lx.unfocusPlugin = function(plugin = null) {
	if (_plugin === null) return;
	if (plugin !== null && plugin !== _plugin) return;

	_plugin.trigger('unfocus');
	_plugin = null;
};
