class Plugin #lx:namespace lx {
    constructor(data = {}) {
        this.title = null;
        this.icon = null;
        
        this.name = data.name;
        this.path = data.path;
        this.images = data.images;
        this.serviceName = data.serviceName;
        this.renderParams = data.renderParams || {};
        this.clientParams = data.clientParams || {};
        this.widgetBasicCss = data.widgetBasicCss || {};
        this.__preJs = [];
        this.__postJs = [];
    }

    getWidgetBasicCss(widgetClass) {
        if (widgetClass in this.widgetBasicCss)
            return this.widgetBasicCss[widgetClass];
        return null;
    }

    findFile(fileName) {

    }

    preJs(code) {
        this.__preJs.push(lx.functionToString(code));
    }

    postJs(code) {
        this.__postJs.push(lx.functionToString(code));
    }

    getDependencies() {
        return {};
    }

    getResult() {
        var result = {
            clientParams: this.clientParams,
            preJs: this.__preJs,
            postJs: this.__postJs
        };
        if (this.title) result.title = this.title;
        if (this.icon) result.icon = this.icon;
        
        return result;
    }
}
