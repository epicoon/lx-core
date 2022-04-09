const __list = {};

#lx:namespace lx;
class CssPresetsList {
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
