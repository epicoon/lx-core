class CssContext #lx:namespace lx {
    constructor() {
        this.sequens = [];

        this.styles = {};
        this.abstractClasses = {};
        this.classes = {};
        this.mixins = {};
    }

    addStyle(name, content = {}) {
        if (lx.isArray(name)) name = name.join(',');

        this.sequens.push({
            name,
            type: 'styles'
        });

        this.styles[name] = {
            name,
            content
        };
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

    addAbstractClass(name, content = {}, pseudoclasses = {}) {
        var processed = __processContent(this, content, pseudoclasses);
        this.abstractClasses[name] = {
            name,
            content: processed.content,
            pseudoclasses: processed.pseudoclasses
        };
    }

    addClass(name, content = {}, pseudoclasses = {}) {
        this.sequens.push({
            name,
            type: 'classes'
        });

        var processed = __processContent(this, content, pseudoclasses);
        this.classes[name] = {
            name,
            content: processed.content,
            pseudoclasses: processed.pseudoclasses
        };
    }

    addClasses(list) {
        for (let name in list) {
            let content = list[name];
            if (lx.isArray(content)) this.addClass(name, content[0], content[1]);
            else this.addClass(name, content);
        }
    }

    inheritClass(name, parent, content = {}, pseudoclasses = {}) {
        this.sequens.push({
            name,
            type: 'classes'
        });

        var processed = __processContent(this, content, pseudoclasses);
        this.classes[name] = {
            name,
            parent,
            content: processed.content,
            pseudoclasses: processed.pseudoclasses
        };
    }

    inheritAbstractClass(name, parent, content = {}, pseudoclasses = {}) {
        var processed = __processContent(this, content, pseudoclasses);
        this.abstractClasses[name] = {
            name,
            parent,
            content: processed.content,
            pseudoclasses: processed.pseudoclasses
        };
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

    toString() {
        var result = '';
        for (var i=0, l=this.sequens.length; i<l; i++) {
            result += __renderRule(this, this.sequens[i]);
        }

        return result;
    }
}

/***********************************************************************************************************************
 * PRIVATE
 **********************************************************************************************************************/
function __processContent(self, content, pseudoclasses) {
    if (lx.isString(content)) {
        return {content, pseudoclasses};
    }

    var processedContent = {};
    for (var name in content) {
        if (name[0] != '@') {
            processedContent[name] = content[name];
            continue;
        }

        name = name.replace('@', '');
        if (!(name in self.mixins)) continue;

        var args = content['@'+name];
        if (!lx.isArray(args)) args = [args];
        var result = self.mixins[name].apply(null, args);

        processedContent.lxMerge(result.content);
        pseudoclasses.lxMerge(result.pseudoclasses);
    }

    return {
        content: processedContent,
        pseudoclasses
    };
}

function __renderRule(self, rule) {
    switch (rule.type) {
        case 'styles': return __renderStyle(self, self.styles[rule.name]);
        case 'classes': return __renderClass(self, self.classes[rule.name]);
    }
}

function __renderStyle(self, styleData) {
    var text = styleData.name + '{';
    var contentString = __getContentString(styleData.content);
    return text + contentString + '}';
}

function __renderClass(self, classData) {
    var className = classData.name[0] == '.'
        ? classData.name
        : '.' + classData.name;

    var text = className + '{';

    var content = __getPropertyWithParent(self, classData, 'content');
    var contentString = __getContentString(content);

    text += contentString + '}';

    var pseudoclasses = __getPropertyWithParent(self, classData, 'pseudoclasses');
    if (pseudoclasses) for (var pseudoName in pseudoclasses) {
        var data;
        if (pseudoName == 'disabled') {
            data = {name: className + '[' + pseudoName + ']'};
        } else {
            data = {name: className + ':' + pseudoName};
        }

        var pseudoContent = pseudoclasses[pseudoName];
        if (pseudoContent.lxParent) {
            data.parent = pseudoContent.lxParent;
            delete pseudoContent.lxParent;
        }
        data.content = pseudoContent;

        text += __renderClass(self, data);
    }

    return text;
}

function __getPropertyWithParent(self, classData, property) {
    if (!classData.parent) return classData[property];
    var parentClass = null;
    if (self.abstractClasses[classData.parent])
        parentClass = self.abstractClasses[classData.parent];
    if (self.classes[classData.parent])
        parentClass = self.classes[classData.parent];
    if (!parentClass) return classData[property];


    var pProperty = parentClass.parent
        ? __getPropertyWithParent(self, parentClass, property)
        : parentClass[property];
    if (!pProperty) pProperty = {};
    if (lx.isString(pProperty)) pProperty = {__str__:[pProperty]};
    var result = pProperty.lxClone();
    if (!result.__str__) result.__str__ = [];

    if (lx.isObject(classData[property]))
        result = result.lxMerge(classData[property], true)
    else if (lx.isString(classData[property]))
        result.__str__.push(classData[property]);
    if (!result.__str__.len) delete result.__str__;
    if (result.lxEmpty()) return null;
    return result;
}

function __getContentString(content) {
    var result = __prepareContentString(content);
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
        var arr = [];
        for (var prop in content) {
            if (prop == '__str__') {
                if (content.__str__.len) arr.push(content.__str__.join(';'));
                continue;
            }

            var propName = prop.replace(/([A-Z])/g, function(x){return "-" + x.toLowerCase()});
            var propVal = lx.isString(content[prop])
                ? content[prop]
                : (content[prop].toString ? content[prop].toString() : '');
            arr.push(propName + ':' + propVal);
        }
        return arr.join(';');
    };

    return '';
}
