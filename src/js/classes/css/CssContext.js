#lx:private;

class CssContext #lx:namespace lx {
	constructor() {
		this.sequens = [];

		this.styles = {};
		this.abstractClasses = {};
		this.classes = {};
	}

	addStyle(name, content = {}) {
		if (name.isArray) name = name.join(',');

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
					? list[content.lxParent].lxCopy().lxMerge(content, true)
					: content;
				delete content.lxParent;
			}

			this.addStyle(name + ' ' + nameI, content);
		}
	}

	//TODO pseudoclasses - не реализовано наследование псевдоклассов
	addAbstractClass(name, content = {}, pseudoclasses = {}) {
		this.abstractClasses[name] = {
			name,
			content,
			pseudoclasses
		};
	}

	addClass(name, content = {}, pseudoclasses = {}) {
		this.sequens.push({
			name,
			type: 'classes'
		});

		this.classes[name] = {
			name,
			content,
			pseudoclasses
		};
	}

	addClasses(list) {
		for (let name in list) {
			let content = list[name];
			if (content.isArray) this.addClass(name, content[0], content[1]);
			else this.addClass(name, content);
		}
	}

	inheritClass(name, parent, content = {}, pseudoclasses = {}) {
		this.sequens.push({
			name,
			type: 'classes'
		});

		this.classes[name] = {
			name,
			parent,
			content,
			pseudoclasses
		};
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

	var content = __getContentWithParent(self, classData);
	var contentString = __getContentString(content);

	text += contentString + '}';

	for (var pseudoName in classData.pseudoclasses) {
		var data;
		if (pseudoName == 'disabled') {
			data = {name: className + '[' + pseudoName + ']'};
		} else {
			data = {name: className + ':' + pseudoName};
		}

		var pseudoContent = classData.pseudoclasses[pseudoName];
		if (pseudoContent.lxParent) {
			data.parent = pseudoContent.lxParent;
			delete pseudoContent.lxParent;
		}
		data.content = pseudoContent;

		text += __renderClass(self, data);
	}

	return text;
}

function __getContentWithParent(self, classData) {
	if (!classData.parent) return classData.content;
	var parentClass = null;
	if (self.abstractClasses[classData.parent])
		parentClass = self.abstractClasses[classData.parent];
	if (self.classes[classData.parent])
		parentClass = self.classes[classData.parent];
	if (!parentClass) return classData.content;

	if (parentClass.parent)
		return __getContentWithParent(self, parentClass).lxCopy().lxMerge(classData.content, true);
	else {
		var pContent = parentClass.content.isObject
			? parentClass.content.lxCopy()
			: (parentClass.content.isString ? {__str__: [parentClass.content]} : {});
		if (pContent.__str__ === undefined) pContent.__str__ = [];

		var result = pContent;
		if (classData.content.isObject)
			result = pContent.lxMerge(classData.content, true)
		else if (classData.content.isString)
			pContent.__str__.push(classData.content);
		return result;
	}
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
	
	if (content.isString) return content;
	
	if (content.isObject) {
		var arr = [];
		for (var prop in content) {
			if (prop == '__str__') {
				if (content.__str__.len) arr.push(content.__str__.join(';'));
				continue;
			}

			var propName = prop.replace(/([A-Z])/g, function(x){return "-" + x.toLowerCase()});
			arr.push(propName + ':' + content[prop]);
		}
		return arr.join(';');
	};

	return '';
}
