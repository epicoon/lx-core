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
					? list[content.lxParent].lxClone().lxMerge(content, true)
					: content;
				delete content.lxParent;
			}

			this.addStyle(name + ' ' + nameI, content);
		}
	}

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

	inheritAbstractClass(name, parent, content = {}, pseudoclasses = {}) {
		this.abstractClasses[name] = {
			name,
			parent,
			content,
			pseudoclasses
		};
	}

	inheritClasses(list, parent) {
		for (let name in list) {
			let content = list[name];
			if (content.isArray) this.inheritClass(name, parent, content[0], content[1]);
			else this.inheritClass(name, parent, content);
		}
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
	if (pProperty.isString) pProperty = {__str__:[pProperty]};
	var result = pProperty.lxClone();
	if (!result.__str__) result.__str__ = [];

	if (classData[property].isObject)
		result = result.lxMerge(classData[property], true)
	else if (classData[property].isString)
		result.__str__.push(classData[property]);
	if (!result.__str__.len) delete result.__str__;
	if (result.lxEmpty) return null;
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
	
	if (content.isString) return content;
	
	if (content.isObject) {
		var arr = [];
		for (var prop in content) {
			if (prop == '__str__') {
				if (content.__str__.len) arr.push(content.__str__.join(';'));
				continue;
			}

			var propName = prop.replace(/([A-Z])/g, function(x){return "-" + x.toLowerCase()});
			var propVal = content[prop].isString
				? content[prop]
				: (content[prop].toString ? content[prop].toString() : '');
			arr.push(propName + ':' + propVal);
		}
		return arr.join(';');
	};

	return '';
}
