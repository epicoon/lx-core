class Css #lx:namespace lx {
	constructor(factor) {
		if (factor.isString) {
			var tag = document.getElementById(factor);
			if (!tag) {
				tag = document.createElement('style');
				var head = document.getElementsByTagName('head')[0];
				head.appendChild(tag);
				tag.setAttribute('id', factor);
			}
			factor = {tag};
		}

		if (factor.isObject) {
			this.tag = factor.tag;
		}
	}

	static exists(name) {
		return !!document.getElementById(name);
	}

	addClass(name, definition) {
		var definitoinString;
		if (definition.isString) definitoinString = definition;
		else if (definition.isObject) {
			var arr = [];
			for (var prop in definition) {
				var propName = prop.replace(/([A-Z])/g, function(x){return "-" + x.toLowerCase()});
				arr.push(propName + ':' + definition[prop]);
			}
			definitoinString = arr.join(';');
		} else return;

		this.tag.innerHTML += '.' + name + '{' + definitoinString + '}';
	}

	//TODO пришлось бы в конце регулярки написать }? (если бы не еще одна дисбалансная регулярка в методе delClass),
	// т.к. парсер JS-компилятора по рекурсивной подмаске не может нормально работать при некорректном
	// количестве фигурных скобок. То, что он лезет в строки - хрень, он их должен игнорировать. Надо поправить
	hasClass(name) {
		var reg = new RegExp('\\.' + name + '\\s*{');
		return reg.test(this.tag.innerHTML);
	}

	delClass(name) {
		var reg = new RegExp('\\.' + name + '\\s*{[^}]*?}');
		this.tag.innerHTML = this.tag.innerHTML.replace(reg, '');
	}
}
