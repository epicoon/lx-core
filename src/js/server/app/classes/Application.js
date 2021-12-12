let __settings = {};
let prefix = null;
let idCounter = 1;

class Application #lx:namespace lx {
    constructor(data = {}) {
        if (data.settings) __settings = data.settings;
        this.i18nArray = {};
    }

    genId() {
        var id = __getPrefix() + '_' + lx.Math.decChangeNotation(idCounter, 62);
        idCounter++;
        return id;
    }

    getSetting(name) {
        return __settings[name];
    }

    useI18n(config) {
        this.i18nArray['_' + lx.HashMd5.hex(config)] = config;
    }

    getDependencies() {
        return {
            i18n: this.i18nArray
        };
    }

    getResult() {
        return {};
    }
}

function __getPrefix() {
    if (prefix === null) {
        var time = (new Date()).getTime();
        prefix = '' + lx.Math.decChangeNotation(time, 62);
    }
    return prefix;
}
