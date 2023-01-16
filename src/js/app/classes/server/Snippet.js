#lx:namespace lx;
class Snippet {
    constructor(data = {}) {
        this.filePath = data.filePath || '';
        this.attributes = __extractAttributes(data.attributes);
        this.metaData = {};

        // Коробка, представляющая сниппет
        this._widget = new lx.Box({parent: null});
        this._widget.snippet = this;

        this.selfData = {};  // информация для коробки сниппета
        this.htmlContent = '';  // строка html сниппета
        this.lx = [];  // массив пояснительных записок
        this.clientJs = null;  // js-код, который будет выполнен на клиенте

        this.plugins = [];  // зависимости от плагинов
    }

    #lx:require ../src/Snippet;

    get widget() {
        return this._widget;
    }

    addPlugin(data) {
        var re = function(obj) {
            if (lx.isFunction(obj)) return lx.app.functionHelper.functionToString(obj);
            if (!lx.isObject(obj)) return obj;
            for (var name in obj) obj[name] = re(obj[name]);
            return obj;
        };
        data = re(data);

        this.plugins.push(data);
    }

    onLoad(code) {
        var js = lx.app.functionHelper.functionToString(code);
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
            attributes: this.attributes,
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

function __extractAttributes(params) {
    if (!params) return {};
    var result = {};
    if (lx.isArray(params) || lx.isObject(params))
        for (var i in params) result[i] = params[i];
    return result;
}

function __prepareSelfData(self) {
    var attrs = self.widget.domElem.attributes;
    if (!attrs.lxEmpty()) self.selfData.attrs = attrs;
    
    var classes = self.widget.domElem.classList;
    if (!classes.lxEmpty()) self.selfData.classes = classes;
    
    var style = self.widget.domElem.styleList;
    if (!style.lxEmpty()) self.selfData.style = style;

    self.widget.beforePack();
    var props = {};
    for (var name in self.widget) {
        if (name == '__self' || name == 'snippet') continue;
        props[name] = self.widget[name];
    }

    if (!props.lxEmpty()) self.selfData.props = props;
}

function __renderContent(self) {
    self.renderIndexCounter = 0;
    self.widget.children.forEach(a=>__setRenderIndex(self, a));

    var html = '';
    self.widget.children.forEach((a)=>html+=__renderWidget(self, a));
    self.htmlContent = html;
}

function __setRenderIndex(self, widget) {
    widget.renderIndex = self.renderIndexCounter++;
    if (widget.children === undefined) return;
    widget.children.forEach(a=>__setRenderIndex(self, a));
}

function __renderWidget(self, widget) {
    __getWidgetData(self, widget);

    if (widget.children === undefined) return widget.domElem.getHtmlString();

    var result = widget.domElem.getHtmlStringBegin() + widget.domElem.content;
    widget.children.forEach(a=>result += __renderWidget(self, a));
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
                if (!value.lxEmpty()) {
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
        if (this.widget.domElem.events.lxEmpty()) return;

        this.data.handlers = {};
        for (var name in this.widget.domElem.events) {
            var handlers = this.widget.domElem.events[name];
            this.data.handlers[name] = [];
            for (var i=0; i<handlers.len; i++) {
                var funcText = lx.app.functionHelper.functionToString(handlers[i]);
                if (funcText) this.data.handlers[name].push(funcText);
            }
        }
    }

    packOnLoad() {
        if (!this.widget.forOnload) return;

        this.data.forOnload = [];
        for (var i=0, l=this.widget.forOnload.len; i<l; i++) {
            var item = this.widget.forOnload[i], strItem;
            if (lx.isArray(item)) {
                strItem = lx.app.functionHelper.functionToString(item[0]);
                if (strItem) strItem = [strItem, item[1]];
            } else strItem = lx.app.functionHelper.functionToString(item);
        }

        if (strItem) this.data.forOnload.push(strItem);
    }
}
