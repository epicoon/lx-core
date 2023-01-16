const __list = {};

class PresetsList {
    register(name, preset) {
        __list[name] = preset;
    }

    get(name) {
        return __list[name] || null;
    }

    getAll() {
        return __list;
    }
}

#lx:namespace lx;
class CssManager extends lx.AppComponentSettable {
    init() {
        this.presetsList = new PresetsList();
    }

    getPresetName() {
        return this.settings.cssPreset;
    }

    isBuilded() {
        return this.settings.assetBuildType != 'none';
    }

    registerPreset(name, preset) {
        this.presetsList.register(name, preset);
    }

    getPreset(name) {
        return this.presetsList.get(name);
    }

    registerPreseted(preseted) {
        preseted.forEach(item=>this.settings.preseted.lxPushUnique(item));
    }

    defineCssClassNames(context, names, plugin = null) {
        let result = [];
        names.forEach(name=>{
            if (name == '') return;

            if (!this.settings.preseted.includes(name)) {
                result.push(name);
                return;
            }

            if (context && context.lxHasMethod('getCssPreset')) {
                const cssPreset = context.getCssPreset();
                if (cssPreset) {
                    result.push(name + '-' + cssPreset.name);
                    return;
                }
            }

            #lx:server { result.push(name + '-#lx:preset:lx#'); }
            #lx:client {
                if (plugin === null && context && context.lxHasMethod('getPlugin'))
                    plugin = context.getPlugin();
                result.push(name + '-' + (plugin ? plugin.cssPreset.name : lx.app.cssManager.getPresetName()));
            }
        });

        return result;
    }

    applyData(data) {
        this.addSettings(data);
        for (let name in data.cssPresets) {
            let presetClass = data.cssPresets[name],
                preset = lx.createObject(presetClass);
            if (preset) this.registerPreset(name, preset);
        }
    }

    getProxyContexts() {
        let list = [];
        this.settings.cssContexts.forEach(contextClass=>{
            let context = lx.createObject(contextClass);
            if (context) list.push(context);
        });
        return list;
    }

    #lx:server renderModuleCss(moduleNames) {
        let result = {},
            presets = this.presetsList.getAll();

        for (let i in moduleNames) {
            let module = moduleNames[i],
                moduleClass = lx.getClassConstructor(module);
            if (!moduleClass || !moduleClass.initCss || lx.app.functionHelper.isEmptyFunction(moduleClass.initCss))
                continue;

            result[module] = {};
            let presetedClasses = [];
            for (let j in presets) {
                let preset = presets[j],
                    context = new lx.CssContext();
                context.configure({
                    proxyContexts: this.getProxyContexts(),
                    preset
                });
                moduleClass.initCss(context);
                result[module][preset.name] = context.toString();
                if (!presetedClasses.len)
                    context.presetedClasses.forEach(name=>presetedClasses.push(name));
            }
            result[module].presetedClasses = presetedClasses;
        }

        return result;
    }

    #lx:client renderModuleCss(config) {
        if (this.isBuilded()) return;

        let modules = config.modules || lx.app.dependencies.getCurrentModules(),
            presets = config.presets || this.presetsList.getAll();

        for (let i in modules) {
            let module = modules[i],
                moduleClass = lx.getClassConstructor(module);
            if (!moduleClass || !moduleClass.initCss || lx.app.functionHelper.isEmptyFunction(moduleClass.initCss))
                continue;

            for (let j in presets) {
                let preset = presets[j],
                    cssName = module + '-' + preset.name;
                if (lx.CssTag.exists(cssName)) continue;
                const css = new lx.CssTag({id: cssName, preset});
                moduleClass.initCss(css.getContext());
                css.commit();
            }
        }
    }

    #lx:client renderPluginCss(plugin) {
        if (!plugin.initCss || lx.app.functionHelper.isEmptyFunction(plugin.initCss)) return;

        const cssPreset = plugin.cssPreset;
        let cssName = plugin.name + '-' + cssPreset.name;
        if (!lx.CssTag.exists(cssName)) {
            const css = new lx.CssTag({id: cssName, preset: cssPreset});
            plugin.initCss(css.getContext());
            css.commit();
        }
    }
}
