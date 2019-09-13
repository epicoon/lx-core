class Snippet #lx:namespace lx {
    constructor(widget, params) {
        this.widget = widget;
        this.clientParams = params;
    }

    get(key) {
        return this.widget.get(key);
    }

    find(key) {
        return this.widget.find(key);
    }
}
