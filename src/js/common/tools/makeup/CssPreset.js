#lx:namespace lx;
class CssPreset {
    constructor() {
        this._proxyAssets = [];
        __init(this);
    }

    createProperty(...args) {
        return new lx.CssValue(this, '', args.join(' '));
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
                return new lx.CssValue(self, name, map[name]);
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

#lx:namespace lx;
class CssValue {
    constructor(preset, name, value) {
        this.preset = preset;
        this.name = name;
        this.value = value;
    }

    toCssString() {
        if (lx.isString(this.value) || lx.isNumber(this.value))
            return this.value;

        if (lx.implementsInterface(this.value, {methods:['toCssString']}))
            return this.value.toCssString();

        return this.value;
    }

    [Symbol.toPrimitive](hint) {
        switch (hint) {
            case 'number':
            case 'string':
            default:
                return this.toCssString();
        }
    }
}