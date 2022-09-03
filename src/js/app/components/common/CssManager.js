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
        //TODO!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        return this.app.getSetting('cssPreset');
    }

    isBuilded() {
        //TODO!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        return this.app.getSetting('assetBuildType') != 'none'
    }
    
    #lx:client actualizeModuleCss(config) {
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
                css.usePreset(preset);
                moduleClass.initCss(css.getContext());
                css.commit();
            }
        }
    }
}
