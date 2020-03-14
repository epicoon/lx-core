class Plugin #lx:namespace lx {
    constructor(data = {}) {
        this.serviceName = data.serviceName;
        this.name = data.name;
        this.path = data.path;
        this.images = data.images;
        this.widgetBasicCss = data.widgetBasicCss || {};

        this._title = data.title;
        this._icon = data.icon;
        this._onloadList = [];
        this._changes = {
            title: null,
            icon: null,
            onloadList: []
        };

        this._oldParams = data.params ? data.params.lxCopy() : {};
        this.params = data.params || {};
    }

    get title() {
        return this._title;
    }

    set title(value) {
        this._title = value;
        this._changes.title = value;
    }

    get icon() {
        return this._icon;
    }

    set icon(value) {
        this._icon = value;
        this._changes.icon = value;
    }

    getWidgetBasicCss(widgetClass) {
        if (widgetClass in this.widgetBasicCss)
            return this.widgetBasicCss[widgetClass];
        return null;
    }

    findFile(fileName) {

    }

    onload(code) {
        this._onloadList.push(lx.functionToString(code));
        this._changes.onloadList.push(lx.functionToString(code));
    }

    getDependencies() {
        return {};
    }

    getResult() {
        var result = {};

        var changedParams = {};
        for (var key in this.params) {
            if (!(key in this._oldParams) || this.params[key] != this._oldParams[key]) {
                changedParams[key] = this.params[key];
            }
        }

        if (!changedParams.lxEmpty) result.params = changedParams;
        if (this._changes.onloadList.len) result.onload = this._changes.onloadList;
        if (this._changes.title) result.title = this._changes.title;
        if (this._changes.icon) result.icon = this._changes.icon;
        
        return result;
    }
}
