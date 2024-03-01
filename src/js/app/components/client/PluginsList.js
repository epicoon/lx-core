let __list = {};
let __plugin = null;

#lx:namespace lx;
class PluginsList extends lx.AppComponent {
    getList() {
        return __list;
    }

    get(key) {
        if (!(key in __list)) return null;
        return __list[key];
    }

    add(key, plugin) {
        __list[key] = plugin;
    }

    remove(plugin) {
        if (!lx.isString(plugin))
            plugin = plugin.key;
        if (!(plugin in __list)) return;
        delete __list[plugin];
    }

    getFocusedPlugin() {
        return __plugin;
    }

    focusPlugin(plugin) {
        if (__plugin === plugin) return;
        this.unfocusPlugin();
        __plugin = plugin;
        __plugin.trigger('focus');
    }

    unfocusPlugin(plugin = null) {
        if (__plugin === null) return;
        if (plugin !== null && plugin !== __plugin) return;

        __plugin.trigger('unfocus');
        __plugin = null;
    }

    #lx:mode-case: dev
    status() {
        console.log('Plugins list:');
        console.log(__list);
        console.log('Focused plugin:');
        console.log(__plugin);
    }
    #lx:mode-end;
}
