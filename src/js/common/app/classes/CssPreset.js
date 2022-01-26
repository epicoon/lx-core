class CssPreset #lx:namespace lx {
    constructor() {
        this._proxyAssets = [];
        __init(this);
    }

    getSettings() {
        return {};
    }

    getProxyCssAssets() {
        return [];
    }

    get name() {
        return self::getName();
    }

    get proxyAssets() {
        return this._proxyAssets;
    }

    static getName() {
        return null;
    }

    static __afterDefinition() {
        if (__isAbstract(this)) return;
        lx.CssPresetsList.registerPreset(this.getName(), new this());
    }
}

function __isAbstract(self) {
    return self.getName() === null;
}

function __init(self) {
    const map = self.getSettings();
    for (let name in map) {
        Object.defineProperty(self, name, {
            get: function() {
                return map[name];
            }
        });
    }

    const assets = self.getProxyCssAssets();
    for (let i in assets) {
        let asset = assets[i];
        asset.init(self);
        self._proxyAssets.push(asset);
    }
}
