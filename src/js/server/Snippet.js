#lx:private;

class Snippet #lx:namespace lx {
    constructor(data = {}) {
        if (data.renderParams) __extractRenderParams(data.renderParams);
        
        this.filePath = data.filePath || '';
        //TODO - клиентские параметры нафиг не нужны в таком виде. Можно красивее эту идею обыгать - как поля сниппета, н-р
        this.clientParams = data.clientParams || {};

        // Коробка, представляющая сниппет
        this._widget = new lx.Box({parent: null});

        this.selfData = {};
        this.htmlContent = '';  // строка html сниппета
        this.lx = {};  // массив пояснительных записок
        this.clientJs = null;  // js-код, который будет выполнен на клиенте

        this.plugins = [];
    }

    get widget() {
        return this._widget;
    }

    addPlugin(data) {
        this.plugins.push(data);
    }

    addSnippet(snippetPath, config = {}, renderParams = {}, clientParams = {}) {
        if (!config.key) {
            // слэши заменяются, т.к. в имени задается путь и может их содержать, а ключ должен быль одним словом
            config.key = snippetPath.isString
                ? snippetPath.replace('/', '_')
                : snippetPath.snippet.replace('/', '_');
        }

        var widgetClass = config.widget || lx.Box;
        var snippet = new widgetClass(config);

        snippet.setSnippet({
            path: snippetPath,
            renderParams,
            clientParams
        });
        return snippet;
    }

    addSnippets(list, commonPath = '') {
        var result = [];
        for (var key in list) {
            var snippetConfig = list[key],
                path = '';

            if (key.isNumber) {
                if (snippetConfig.isObject) {
                    if (!snippetConfig.path) continue;
                    path = snippetConfig.path;
                } else if (snippetConfig.isString) {
                    path = snippetConfig;
                    snippetConfig = {};
                } else continue;
            } else if (key.isString) {
                path = key;
                if (!snippetConfig.isObject) snippetConfig = {};
            }

            var renderParams = snippetConfig.lxExtract('renderParams') || {};
            var clientParams = snippetConfig.lxExtract('clientParams') || {};
            var config = (snippetConfig.config) ? snippetConfig.config : snippetConfig;
            if (!config.key) config.key = path;

            var snippetPath = path.isString
                ? commonPath + path
                : path;
            result.push(this.addSnippet(snippetPath, config, renderParams, clientParams));
        }

        return result;
    }

    onload(code) {
        var js = lx.functionToString(code);
        //TODO - есть соображение тут передавать какие-то параметры из серверного кода сниппета. Типа замыкание
        //но пока ()=> просто вырезается
        js = js.replace(/^\([^)]*?\)=>/, '');
        this.clientJs = js;
    }

    getDependencies() {
        return {
            plugins: this.plugins
        };
    }

    getResult() {
        __prepareSelfData(this);
        __renderContent(this);
        return {
            clientParams: this.clientParams,
            selfData: this.selfData,
            html: this.htmlContent,
            lx: this.lx,
            js: this.clientJs
        };
    }
}

function __prepareSelfData(self) {
    var attrs = self.widget.domElem.attributes;
    if (!attrs.lxEmpty) self.selfData.attrs = attrs;
    
    var classes = self.widget.domElem.classList;
    if (!classes.lxEmpty) self.selfData.classes = classes;
    
    var style = self.widget.domElem.styleList;
    if (!style.lxEmpty) self.selfData.style = style;

    self.widget.beforePack();
    var props = {};
    for (var name in self.widget) {
        if (name == '__self') continue;
        props[name] = self.widget[name];
    }

    if (!props.lxEmpty) self.selfData.props = props;
}

function __renderContent(self) {
    var html = '';
    self.widget.allChildren.each((a)=>html+=__renderElement(self, a));
    self.htmlContent = html;
}

function __renderElement(self, el) {
    __getElementData(self, el);

    if (el.allChildren === undefined) return el.domElem.getHtmlString();

    var result = el.domElem.getHtmlStringBegin() + el.domElem.content;
    el.allChildren.each((a)=>result += __renderElement(self, a));
    result += el.domElem.getHtmlStringEnd();
    return result;
}

function __getElementData(self, el) {
    var data = {};
    el.beforePack();
    __packProperties(self, el, data);
    __packHandlers(self, el, data);
    __packOnLoad(self, el, data);

    self.lx[el.lxid] = data;
}

function __packProperties(self, el, data) {
    for (var name in el) {
        if (name == '__self' || name == 'lxid') continue;

        var value = el[name];
        if (name == 'geom') {
            if (!value.lxEmpty) {
                var temp = [];
                temp.push(value.bpg ? value.bpg[0]+','+value.bpg[1] : '');
                temp.push(value.bpv ? value.bpv[0]+','+value.bpv[1] : '');
                data.geom = temp.join('|');
            }
            continue;
        }
        data[name] = value;
    }
}

function __packHandlers(self, el, data) {
    if (el.domElem.events.lxEmpty) return;

    data.handlers = {};
    for (var name in el.domElem.events) {
        var handlers = el.domElem.events[name];
        data.handlers[name] = [];
        for (var i=0; i<handlers.len; i++) {
            var funcText = lx.functionToString(handlers[i]);
            if (funcText) data.handlers[name].push(funcText);
        }
    }
}

function __packOnLoad(self, el, data) {
    if (!el.forOnload) return;

    data.forOnload = [];
    for (var i=0, l=el.forOnload.len; i<l; i++) {
        var item = el.forOnload[i], strItem;
        if (item.isArray) {
            strItem = lx.functionToString(item[0]);
            if (strItem) strItem = [strItem, item[1]];
        } else strItem = lx.functionToString(item);
    }

    if (strItem) data.forOnload.push(strItem);
}

function __extractRenderParams(params) {
    for (var name in params)
        lx.globalContext[name] = params[name];
}
