#lx:namespace lx;
class Module {
    /**
     * @param {String} key - ключ вызываемого метода
     * @param {Array} params - параметры, с которыми нужно вызвать метод
     */
    static ajax(key, params = []) {
        return new lx.ModuleRequest(this.lxFullName(), key, params);
    }

    /**
     * @param {lx.CssAsset} css
     */
    static initCssAsset(css) {
        // pass
    }

    newEvent(params = {}) {
        return new lx.ModuleEvent(this, params);
    }
}
