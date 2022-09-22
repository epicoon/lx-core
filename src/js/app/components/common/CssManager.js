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
    isReady() {
        return !__list.lxEmpty();
    }

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

    #lx:client renderModuleCss(config) {
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
                const css = new lx.CssTag({id: cssName});
                css.getContext().configure({
                    proxyContexts: this.getProxyContexts(),
                    preset
                });
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
            const css = new lx.CssTag({id: cssName});
            css.getContext().configure({
                proxyContexts: this.getProxyContexts(),
                preset: cssPreset
            });
            plugin.initCss(css.getContext());
            css.commit();
        }
    }
}
