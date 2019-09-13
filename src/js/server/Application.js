#lx:private;

let __settings = {};

class Application #lx:namespace lx {
    #lx:const
        POSTUNPACK_TYPE_IMMEDIATLY = \lx::POSTUNPACK_TYPE_IMMEDIATLY,
        POSTUNPACK_TYPE_FIRST_DISPLAY = \lx::POSTUNPACK_TYPE_FIRST_DISPLAY,
        POSTUNPACK_TYPE_ALL_DISPLAY = \lx::POSTUNPACK_TYPE_ALL_DISPLAY;

    constructor(data = {}) {
        if (data.settings) __settings = data.settings;
        this.i18nArray = {};
    }

    getSetting(name) {
        return __settings[name];
    }

    useI18n(config) {
        this.i18nArray['_' + lx.HashMd5.hex(config)] = config;
    }

    getResult() {
        return {
            i18n: this.i18nArray
        };
    }
}
