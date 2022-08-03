#lx:namespace lx;
class Plugin {
    constructor(data = {}) {
        this.serviceName = data.serviceName;
        this.name = data.name;
        this.path = data.path;
        this.images = data.images;
        this._cssPreset = data.cssPreset;
        this.widgetBasicCss = data.widgetBasicCss || {};
        this._title = data.title;
        this._icon = data.icon;
        this._changes = {
            title: null,
            icon: null
        };

        this._oldAttributes = data.attributes ? data.attributes.lxClone() : {};
        this.attributes = data.attributes || {};
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

    get cssPreset() {
        return lx.CssPresetsList.getCssPreset(this._cssPreset);
    }

    getImage(name) {
        if (name[0] != '@') {
            if (!this.images['default']) return name;
            return this.images['default'] + '/' + name;
        }

        var arr = name.match(/^@([^\/]+?)(\/.+)$/);
        if (!arr || !this.images[arr[1]]) return '';

        return this.images[arr[1]] + arr[2];
    }

    getWidgetBasicCss(widgetClass) {
        if (widgetClass in this.widgetBasicCss)
            return this.widgetBasicCss[widgetClass];
        return null;
    }

    findFile(fileName) {

    }

    getDependencies() {
        return {};
    }

    getResult() {
        var result = {};

        var changedAttributes = {};
        for (var key in this.attributes) {
            if (!(key in this._oldAttributes)
                || (!this.attributes[key].lxCompare(this._oldAttributes[key]))
            ) {
                changedAttributes[key] = this.attributes[key];
            }
        }

        if (!changedAttributes.lxEmpty()) result.attributes = changedAttributes;
        if (this._changes.title) result.title = this._changes.title;
        if (this._changes.icon) result.icon = this._changes.icon;
        
        return result;
    }
}
