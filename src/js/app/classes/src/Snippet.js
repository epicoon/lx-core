#lx:public;

addSnippet(snippetPath, config = {}) {
    let widgetClass = config.widget || lx.Box,
        attributes = config.lxExtract('attributes') || {},
        backLock = lx.getFirstDefined(config.backLock, false),
        hidden = lx.getFirstDefined(config.hidden, false);
    config = (config.config) ? config.config : config;
    if (!config.key) {
        // слэши заменяются, т.к. в имени задается путь и может их содержать, а ключ должен быль одним словом
        config.key = lx.isString(snippetPath)
            ? snippetPath.replace('/', '_')
            : snippetPath.snippet.replace('/', '_');
    }

    let widget, head;
    if (backLock) {
        const wrapper = new lx.Box({ key: config.key + 'Wrapper', geom: true });
        const back = wrapper.add(lx.Box, {geom:true});
        back.fill('black');
        back.opacity(0.5);
        widget = wrapper.add(widgetClass, config);
        wrapper.onLoad(function() {
            this.child(1).show = () => this.show();
            this.child(1).hide = () => this.hide();
            this.child(0).click(() => this.hide());
        });
        head = wrapper;
    } else {
        widget = new widgetClass(config);
        head = widget;
    }

    widget.setSnippet({
        path: snippetPath,
        attributes
    });
    if (hidden) head.hide();

    return widget.snippet;
}

addSnippets(list, commonPath = '') {
    let result = [];
    for (let key in list) {
        let snippetConfig = list[key],
            path = '';

        if (lx.isNumber(key)) {
            if (lx.isObject(snippetConfig)) {
                if (!snippetConfig.path) continue;
                path = snippetConfig.path;
            } else if (lx.isString(snippetConfig)) {
                path = snippetConfig;
                snippetConfig = {};
            } else continue;
        } else if (lx.isString(key)) {
            path = key;
            if (!lx.isObject(snippetConfig)) snippetConfig = {};
        }

        if (snippetConfig.config) snippetConfig.config.key = path;
        else snippetConfig.key = path;

        if (commonPath != '' && commonPath[commonPath.length - 1] != '/') {
            commonPath += '/';
        }
        let snippetPath = lx.isString(path)
            ? commonPath + path
            : path;
        result.push(this.addSnippet(snippetPath, snippetConfig));
    }

    return result;
}
