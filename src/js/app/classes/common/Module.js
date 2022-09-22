#lx:namespace lx;
class Module {
    static __afterDefinition() {
        #lx:client {
            (lx.app && lx.app.cssManager && lx.app.cssManager.isReady())
                ? lx.app.cssManager.renderModuleCss({ modules: [this.lxFullName()] })
                : lx.onReady(()=>lx.app.cssManager.renderModuleCss({ modules: [this.lxFullName()] }));
        }
    }

    #lx:client {
        /**
         * @param {String} key - ключ вызываемого метода
         * @param {Array} params - параметры, с которыми нужно вызвать метод
         */
        static ajax(key, params = []) {
            return new lx.ModuleRequest(this.lxFullName(), key, params);
        }

        /**
         * @param {lx.CssContext} css
         */
        static initCss(css) {
            // pass
        }

        newEvent(params = {}) {
            return new lx.ModuleEvent(this, params);
        }
    }
}
