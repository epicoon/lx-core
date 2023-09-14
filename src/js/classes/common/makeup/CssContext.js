#lx:namespace lx;
class CssContext {
    constructor() {
        this.reset();
    }

    reset() {
        this.sequens = [];
        this.styles = {};
        this.abstractClasses = {};
        this.classes = {};
        this.mixins = {};
        this.preset = null;
        this.proxyContexts = [];
        this.presetedClasses = [];
        this.presetedStyles = [];
    }

    init(cssPreset) {
        // pass
    }

    configure(config) {
        this.reset();
        this.preset = config.preset;
        this.proxyContexts = config.proxyContexts;
        this.prepare();
    }

    usePreset(preset) {
        this.preset = preset;
    }

    useContext(context) {
        this.proxyContexts.lxPushUnique(context);
    }

    useContexts(contexts) {
        for (let i in contexts)
            this.useContext(contexts[i]);
    }

    prepare() {
        this.proxyContexts.forEach(context=>{
            context.reset();
            context.init(this.preset);
        });
    }

    get cssPreset() {
        return this.preset;
    }

    addStyle(name, content = {}) {
        if (lx.isArray(name)) name = name.join(',');

        this.sequens.push({name, type: 'styles'});

        let constructor = (name[0] == '@') ? CssDirective : CssStyle;
        this.styles[name] = new constructor({
            context: this,
            name,
            content
        });
    }

    addClass(name, content = {}, pseudoclasses = {}) {
        if (name[0] != '.') name = '.' + name;

        this.sequens.push({name, type: 'classes'});

        let processed = __processContent(this, content, pseudoclasses);
        this.classes[name] = new CssClass({
            context: this,
            name,
            content: processed.content,
            pseudoclasses: processed.pseudoclasses
        });
    }

    inheritClass(name, parent, content = {}, pseudoclasses = {}) {
        if (name[0] != '.') name = '.' + name;
        if (parent[0] != '.') parent = '.' + parent;

        this.sequens.push({name, type: 'classes'});

        let processed = __processContent(this, content, pseudoclasses);
        this.classes[name] = new CssClass({
            context: this,
            parent,
            name,
            content: processed.content,
            pseudoclasses: processed.pseudoclasses
        });
    }

    addAbstractClass(name, content = {}, pseudoclasses = {}) {
        if (name[0] != '.') name = '.' + name;

        let processed = __processContent(this, content, pseudoclasses);
        this.abstractClasses[name] = new CssClass({
            context: this,
            isAbstract: true,
            name,
            content: processed.content,
            pseudoclasses: processed.pseudoclasses
        });
    }

    inheritAbstractClass(name, parent, content = {}, pseudoclasses = {}) {
        if (name[0] != '.') name = '.' + name;
        if (parent[0] != '.') parent = '.' + parent;

        let processed = __processContent(this, content, pseudoclasses);
        this.abstractClasses[name] = new CssClass({
            context: this,
            isAbstract: true,
            parent,
            name,
            content: processed.content,
            pseudoclasses: processed.pseudoclasses
        });
    }

    addStyleGroup(name, list) {
        for (let nameI in list) {
            let content = list[nameI];
            if (content.lxParent) {
                content = list[content.lxParent]
                    ? list[content.lxParent].lxClone().lxMerge(content, true)
                    : content;
                delete content.lxParent;
            }

            this.addStyle(name + ' ' + nameI, content);
        }
    }

    addClasses(list) {
        for (let name in list) {
            let content = list[name];
            if (lx.isArray(content)) this.addClass(name, content[0], content[1]);
            else this.addClass(name, content);
        }
    }

    inheritClasses(list, parent) {
        for (let name in list) {
            let content = list[name];
            if (lx.isArray(content)) this.inheritClass(name, parent, content[0], content[1]);
            else this.inheritClass(name, parent, content);
        }
    }

    registerMixin(name, callback) {
        this.mixins[name] = callback;
    }
    
    getClass(name) {
        if (name[0] != '.') name = '.' + name;

        if (name in this.abstractClasses)
            return this.abstractClasses[name];
        if (name in this.classes)
            return this.classes[name];
        return null;
    }

    toString() {
        let result = '';
        for (let i=0, l=this.sequens.length; i<l; i++) {
            const rule = this[this.sequens[i].type][this.sequens[i].name];
            result += rule.render();
        }

        for (let i=0, l=this.presetedStyles.length; i<l; i++) {
            let name = this.presetedStyles[i];
            let reg = new RegExp('([ :])' + name + '([ ;{}])', 'g');
            result = result.replace(reg, '$1' + name + '-' + this.preset.name + '$2');
        }

        return result;
    }
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * CssRule
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class CssRule {

}

function __defineRulePreseted(rule) {
    if (__defineAttrsPreseted(rule.content))
        return true;

    if (rule.pseudoclasses) {
        for (let i in rule.pseudoclasses)
            if (__defineAttrsPreseted(rule.pseudoclasses[i]))
                return true;
    }

    return false;
}

function __defineAttrsPreseted(attrs) {
    for (let i in attrs) {
        let attr = attrs[i];
        if (lx.isInstance(attr, lx.CssValue)) return true;
        if (lx.isCleanObject(attr) && __defineAttrsPreseted(attr))
            return true;
    }
    return false;
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * CssClass
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class CssClass {
    constructor(config) {
        this.context = config.context;
        this.basicName = config.name;
        this.isAbstract = lx.getFirstDefined(config.isAbstract, false);
        this.parent = lx.getFirstDefined(config.parent, null);

        this.selfContent = config.content;
        this.selfPseudoclasses = config.pseudoclasses;
        this.content = null;
        this.pseudoclasses = null;
        this._isPreseted = false;
        this.refresh();
    }

    isPreseted() {
        return this._isPreseted;
    }

    refresh() {
        this.content = lx.clone(this.selfContent);
        this.pseudoclasses = lx.clone(this.selfPseudoclasses);
        if (this.parent) __applyClassParent(this);
        this._isPreseted = __defineRulePreseted(this);
    }

    render() {
        const className = (this.isPreseted() && this.context.preset)
            ? this.basicName + '-' + this.context.preset.name
            : this.basicName;

        let text = className + '{' + __getContentString(this.content) + '}';

        for (let pseudoclassName in this.pseudoclasses) {
            let pseudoclass = this.pseudoclasses[pseudoclassName];
            pseudoclassName = (pseudoclassName == 'disabled')
                ? className + '[' + pseudoclassName + ']'
                : className + ':' + pseudoclassName;

            text += pseudoclassName + '{' + __getContentString(pseudoclass) + '}';
        }

        if (this.isPreseted()) {
            let className = this.basicName.substr(1);
            if (!this.context.presetedClasses.includes(className))
                this.context.presetedClasses.push(className);
        }
        return text;
    }
}

function __applyClassParent(self) {
    self.content = __getClassPropertyWithParent(self, 'content');
    self.pseudoclasses = __getClassPropertyWithParent(self, 'pseudoclasses');
}

function __getClassPropertyWithParent(cssClass, property) {
    if (!cssClass.parent) return cssClass[property];

    let parentClass = null;
    if (lx.isObject(cssClass.parent))
        parentClass = cssClass.parent;
    if (!parentClass) parentClass = __getCssClass(cssClass.context, cssClass.parent);
    if (!parentClass) return cssClass[property];

    let pProperty = __getClassPropertyWithParent(parentClass, property) || {};
    if (lx.isString(pProperty)) pProperty = {__str__:[pProperty]};

    let result = pProperty.lxClone();
    if (!result.__str__) result.__str__ = [];

    if (lx.isObject(cssClass[property]))
        result = cssClass[property].lxMerge(result);
    else if (lx.isString(cssClass[property]))
        result.__str__.push(cssClass[property]);

    if (!result.__str__.len) delete result.__str__;
    if (result.lxEmpty()) return null;
    return result;
}

function __getCssClass(context, name) {
    if (name in context.abstractClasses)
        return context.abstractClasses[name];

    if (name in context.classes)
        return context.classes[name];

    for (let i in context.proxyContexts) {
        let c = __getCssClass(context.proxyContexts[i], name);
        if (c) return c;
    }

    return null;
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * CssStyle
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class CssStyle {
    constructor(config) {
        this.context = config.context;
        this.selector = config.name;
        this.content = config.content;
    }

    render() {
        let selector = this.selector,
            list = [...selector.matchAll(/\.\b[\w\d_-]+\b/g)];
        for (let i in list) {
            let cssClassName = list[i][0],
                cssClass = this.context.getClass(cssClassName);
            if (!cssClass) continue;
            if (cssClass.isPreseted()) {
                let reg = new RegExp(cssClassName + '($|[^\w\d_-])');
                selector = selector.replace(reg, cssClassName + '-' + cssClass.context.preset.name + '$1');
            }
        }

        return selector + '{' + __getContentString(this.content) + '}';
    }
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * CssDirective
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class CssDirective extends CssStyle {
    render() {
        if (!/^@keyframes/.test(this.selector)) return super.render();

        if (__defineRulePreseted(this))
            this.context.presetedStyles.lxPushUnique(this.selector.replace(/^@keyframes\s+/, ''));

        let content = [];
        for (let key in this.content) {
            let attrs = this.content[key];
            let row = __getContentString(attrs);
            content.push(key + '{' + row + '}');
        }
        return this.selector + '{' + content.join('') + '}';
    }
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * PRIVATE
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function __processContent(self, content, pseudoclasses) {
    if (lx.isString(content)) {
        return {content, pseudoclasses};
    }

    let processedContent = {};
    for (let name in content) {
        if (name[0] != '@') {
            processedContent[name] = content[name];
            continue;
        }

        let mixin = __getMixin(self, name);
        if (!mixin) continue;

        let args = content[name];
        if (!lx.isArray(args)) args = [args];
        let result = mixin.apply(null, args);

        if (result.content) {
            processedContent.lxMerge(result.content);
            if (result.pseudoclasses) pseudoclasses.lxMerge(result.pseudoclasses);
        } else {
            processedContent.lxMerge(result);
        }
    }

    return {
        content: processedContent,
        pseudoclasses
    };
}

function __getMixin(self, name) {
    let mixinName = (name[0] == '@') ? name.replace(/^@/, '') : name;
    if (mixinName in self.mixins)
        return self.mixins[mixinName];

    for (let i in self.proxyContexts) {
        let mixin = __getMixin(self.proxyContexts[i], name);
        if (mixin) return mixin;
    }

    return null;
}

function __getContentString(content) {
    let str = __prepareContentString(content);
    return __postProcessContentString(str);
}

function __postProcessContentString(str) {
    let result = str;
    result = result.replace(/(,|:) /g, '$1');
    result = result.replace(/ !important/g, '!important');
    result = result.replace(/([^\d])0(px|%)/g, '$10');

    result = result.replace(/color:white/g, 'color:#fff');
    result = result.replace(/color:black/g, 'color:#000');

    return result;
}

function __prepareContentString(content) {
    if (!content) return '';

    if (lx.isString(content)) return content;

    if (lx.isObject(content)) {
        let arr = [];
        for (let prop in content) {
            if (prop == '__str__') {
                if (content.__str__.len) arr.push(content.__str__.join(';'));
                continue;
            }

            let propName = prop.replace(/([A-Z])/g, function(x){return "-" + x.toLowerCase()});

            let propVal = null;
            if (lx.isString(content[prop]) || lx.isNumber(content[prop]))
                propVal = content[prop]
            else if (lx.implementsInterface(content[prop], {methods:['toCssString']}))
                propVal = content[prop].toCssString();
            if (propVal === null) continue;

            arr.push(propName + ':' + propVal);
        }
        return arr.join(';');
    };

    return '';
}
