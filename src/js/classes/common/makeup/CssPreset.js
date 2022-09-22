#lx:namespace lx;
class CssPreset {
    constructor() {
        __init(this);
    }

    createProperty(...args) {
        return new lx.CssValue(this, '', args.join(' '));
    }

    getSettings() {
        return {};
    }

    get name() {
        return self::getName();
    }

    static getName() {
        return null;
    }
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
