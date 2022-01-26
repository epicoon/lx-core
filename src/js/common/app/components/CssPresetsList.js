const __list = {};

class CssPresetsList #lx:namespace lx {
    static registerPreset(name, preset) {
        __list[name] = preset;
    }

    static getCssPreset(name) {
        return __list[name] || null;
    }
    
    static getCssPresets() {
        return __list;
    }
}
