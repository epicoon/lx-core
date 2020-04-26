#lx:private;

class Snippet #lx:namespace lx {
    constructor(data = {}) {
        if (data.renderParams) __extractRenderParams(data.renderParams);
        
        this.filePath = data.filePath || '';
        this.clientParams = __extractClientParams(data.clientParams);
        this.metaData = {};

        // Коробка, представляющая сниппет
        this._widget = new lx.Box({parent: null});

        this.selfData = {};  // информация для коробки сниппета
        this.htmlContent = '';  // строка html сниппета
        this.lx = [];  // массив пояснительных записок
        this.clientJs = null;  // js-код, который будет выполнен на клиенте

        this.plugins = [];  // зависимости от плагинов
    }

    get widget() {
        return this._widget;
    }

    addPlugin(data) {
        var re = function(obj) {
            if (obj.isFunction) return lx.functionToString(obj);
            if ( ! obj.isObject) return obj;
            for (var name in obj) obj[name] = re(obj[name]);
            return obj;
        };
        data = re(data);

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

    setScreenModes(map) {
        this.metaData.sm = map;
        for (var i in this.metaData.sm)
            if (this.metaData.sm[i] == Infinity)
                this.metaData.sm[i] = 'INF';
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
            js: this.clientJs,
            meta: this.metaData
        };
    }
}


/***********************************************************************************************************************
 * PRIVATE
 **********************************************************************************************************************/

function __extractRenderParams(params) {
    if (params.isArray && !params.len) return;

    for (var name in params)
        lx.globalContext[name] = params[name];
}

function __extractClientParams(params) {
    if (!params) return {};
    var result = {};
    if (params.isArray)
        for (var i in params) result[i] = params[i];
    return result;
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
    self.renderIndexCounter = 0;
    self.widget.children.each(a=>__setRenderIndex(self, a));

    var html = '';
    self.widget.children.each((a)=>html+=__renderWidget(self, a));
    self.htmlContent = html;
}

function __setRenderIndex(self, widget) {
    widget.renderIndex = self.renderIndexCounter++;
    if (widget.children === undefined) return;
    widget.children.each(a=>__setRenderIndex(self, a));
}

function __renderWidget(self, widget) {
    __getWidgetData(self, widget);

    if (widget.children === undefined) return widget.domElem.getHtmlString();

    var result = widget.domElem.getHtmlStringBegin() + widget.domElem.content;
    widget.children.each((a)=>result += __renderWidget(self, a));
    result += widget.domElem.getHtmlStringEnd();
    return result;
}

function __getWidgetData(self, widget) {
    widget.setAttribute('lx');
    widget.beforePack();
    var pack = new PackData(widget);
    self.lx.push(pack.getResult());
}


/***********************************************************************************************************************
 * PackData
 **********************************************************************************************************************/

class PackData {
    constructor(widget) {
        this.data = {};
        this.widget = widget;
    }

    getResult() {
        this.packProperties();
        this.packHandlers();
        this.packOnLoad();
        return this.data;
    }

    packProperties() {
        for (var name in this.widget) {
            if (name == '__self' || name == 'lxid') continue;

            var value = this.widget[name];
            if (name == 'geom') {
                if (!value.lxEmpty) {
                    var temp = [];
                    temp.push(value.bpg ? value.bpg[0]+','+value.bpg[1] : '');
                    temp.push(value.bpv ? value.bpv[0]+','+value.bpv[1] : '');
                    this.data.geom = temp.join('|');
                }
                continue;
            }
            this.data[name] = value;
        }
    }

    packHandlers() {
        if (this.widget.domElem.events.lxEmpty) return;

        this.data.handlers = {};
        for (var name in this.widget.domElem.events) {
            var handlers = this.widget.domElem.events[name];
            this.data.handlers[name] = [];
            for (var i=0; i<handlers.len; i++) {
                var funcText = lx.functionToString(handlers[i]);
                if (funcText) this.data.handlers[name].push(funcText);
            }
        }
    }

    packOnLoad() {
        if (!this.widget.forOnload) return;

        this.data.forOnload = [];
        for (var i=0, l=this.widget.forOnload.len; i<l; i++) {
            var item = this.widget.forOnload[i], strItem;
            if (item.isArray) {
                strItem = lx.functionToString(item[0]);
                if (strItem) strItem = [strItem, item[1]];
            } else strItem = lx.functionToString(item);
        }

        if (strItem) this.data.forOnload.push(strItem);
    }
}
