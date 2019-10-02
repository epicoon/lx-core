class Plugin #lx:namespace lx {
    constructor(data = {}) {
        this.title = null;
        this.icon = null;
        
        this.name = data.name;
        this.images = data.images;
        this.serviceName = data.serviceName;
        this.renderParams = data.renderParams || {};
        this.clientParams = data.clientParams || {};
        this.widgetBasicCss = data.widgetBasicCss || {};
    }

    getWidgetBasicCss(widgetClass) {
        if (widgetClass in this.widgetBasicCss)
            return this.widgetBasicCss[widgetClass];
        return null;
    }

    getDependencies() {
        return {};
    }

    getResult() {
        var result = {
            clientParams: this.clientParams
        };
        if (this.title) result.title = this.title;
        if (this.icon) result.icon = this.icon;
        
        return result;
    }
}
