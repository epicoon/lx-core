class DomElementDefinition #lx:namespace lx {
	constructor(owner, tag) {
		this.owner = owner;
		this.elem = null;
		this.parent = null;

		if (tag === undefined) return;
		this.tag = tag;
		this.classList = [];
		this.attributes = {};
		this.styleList = {};
		this.content = '';
		this.params = {};
		this.events = {};
		this.actions = [];
		this.next = null;
	}

	#lx:client createElement() {
		if (this.elem) return;

		this.elem = document.createElement(this.tag);

		if (this.parent) {
			var pElem = this.parent.getDomElem();
			if (pElem) {
				if (this.next) pElem.insertBefore( this.elem, this.next.getDomElem() );
				else pElem.appendChild( this.elem );
			}
		}

		if (this.classList.len) this.elem.className = this.classList.join(' ');

		for (var name in this.attributes) {
			this.elem.setAttribute(name, this.attributes[name]);
		}

		for (var name in this.styleList) {
			this.elem.style[name] = this.styleList[name];
		}

		if (this.content != '') this.elem.innerHTML = this.content;

		this.setElem(this.elem);
		this.applyElemFeatures();
	}

	setParent(parent, next) {
		this.parent = parent;
		this.next = next;
	}

	clear() {
		if (this.elem) delete this.elem.__lx;

		this.owner = null;
		this.elem = null;
		this.parent = null;
		this.tag = null;
		this.classList = null;
		this.attributes = null;
		this.styleList = null;
		this.content = null;
		this.params = null;
		this.events = null;
		this.actions = null;
		this.next = null;
	}

	removeChild(elemDefinition) {
		if (elemDefinition.parent) elemDefinition.parent = null;

		#lx:client {
			if (this.elem && elemDefinition.elem)
				this.elem.removeChild(elemDefinition.elem);
		}
	}

	getTagName() {
		if (this.elem) return this.elem.tagName;
		return this.tag;
	}

	nextSibling() {
		if (!this.parent) return null;
		return this.parent.children.next(this.owner);
	}

	prevSibling() {
		if (!this.parent) return null;
		return this.parent.children.prev(this.owner);
	}

	hasClass(className) {
		if (this.elem) return this.elem.classList.contains(className);
		return this.classList.contains(className);
	}

	addClass(className) {
		if (this.elem) this.elem.classList.add(className);
		else this.classList.push(className);
	}

	removeClass(className) {
		if (this.elem) this.elem.classList.remove(className);
		else this.classList.remove(className);
	}

	clearClasses() {
		if (this.elem) this.elem.className = '';
		else this.classList = [];
	}

	getAttribute(name) {
		if (this.elem) return this.elem.getAttribute(name);
		return this.attributes[name];
	}

	setAttribute(name, value) {
		if (this.elem) this.elem.setAttribute(name, value);
		else this.attributes[name] = value;
	}

	removeAttribute(name) {
		if (this.elem) this.elem.removeAttribute(name);
		else delete this.attributes[name];
	}

	style(name, value) {
		if (name === undefined) {
			if (this.elem) return this.elem.style;
			return this.styleList;
		}

		if (value === undefined) {
			if (this.elem) return this.elem.style[name] ? this.elem.style[name] : null;
			if (name in this.styleList) return this.styleList[name];
			return null;
		}

		if (value === null) {
			if (this.elem) this.elem.style[name] = null;
			else delete this.styleList[name];
			return;
		}

		if (this.elem) this.elem.style[name] = value;
		else this.styleList[name] = value;
	}

	html(text) {
		if (text === undefined) {
			if (this.elem) return this.elem.innerHTML;
			return this.content;
		}

		if (this.elem) this.elem.innerHTML = text;
		else this.content = text;
	}

	outerHtml() {
		if (this.elem) return this.elem.outerHTML;

		//TODO перенести сюда логику рендеринга?
	}

	param(name, value) {
		if (value === undefined) {
			if (this.elem) return this.elem[name];
			return this.params[name];
		}

		if (this.elem) this.elem[name] = value;
		else this.params[name] = value;
	}

	addAction(funcName, args) {
		if (this.elem) {
			this.elem[funcName].apply(this.elem, args);
		} else this.actions.push([funcName, args]);
	}

	addEvent(eventName, func) {
		if (this.elem) lx.Event.add(this.elem, eventName, func);
		else {
			if (!(eventName in this.events)) this.events[eventName] = [];
			this.events[eventName].push(func);
		}
	}

	delEvent(eventName, func) {
		if (this.elem) lx.Event.remove(this.elem, eventName, func);
		else {
			if (func === undefined) delete this.events[eventName];
			else if (eventName in this.events) this.events[eventName].remove(func);
		}
	}

	hasEvent(eventName, func) {
		if (this.elem) return lx.Event.has(this.elem, type, func);
		if (func === undefined) return (eventName in this.events);
		return ((eventName in this.events) && this.events[eventName].contains(func));
	}

	getEvents() {
		if (this.elem) return this.elem.events;
		return this.events;
	}

	getHtmlStringBegin() {
		var tag = new lx.Tag({
			tag: this.tag,
			attributes: this.attributes,
			classList: this.classList,
			style: this.styleList
		});
		return tag.getOpenString();
	}

	getHtmlStringEnd() {
		return '</' + this.tag + '>';
	}
	
	getHtmlString() {
		if (this.elem) return this.elem.outerHTML;
		
		//TODO возможно тут можно учесть потомков
		
		return this.getHtmlStringBegin() + this.content + this.getHtmlStringEnd();
	}

	getElem() {
		return this.elem;
	}

	
	/*******************************************************************************************************************
	 * CLIENT ONLY
	 ******************************************************************************************************************/
	#lx:client {
		rendered() {
			return this.elem !== null;
		}

		setElem(elem) {
			this.elem = elem;
			this.elem.__lx = this.owner;
		}

		refreshElem(domElem) {
			if (this.elem) delete this.elem.__lx;

			this.elem = domElem;
			this.elem.__lx = this.owner;
			this.applyElemFeatures();
		}

		applyElemFeatures() {
			if (this.params) for (var name in this.params) {
				this.elem[name] = this.params[name];
			}

			if (this.actions) for (var i = 0, l = this.actions.len; i < l; i++) {
				var item = this.actions[i],
					funcName = item[0],
					args = item[1];
				if (args.isArray) this.owner[funcName].apply(this.owner, args);
				else this.owner[funcName].call(this.owner);
			}

			if (this.events) for (var eventName in this.events) {
				for (var i = 0, l = this.events[eventName].len; i < l; i++) {
					lx.Event.add(this.elem, eventName, this.events[eventName][i]);
				}
			}

			this.dropMetaData();
		}

		dropMetaData() {
			delete this.tag;
			delete this.classList;
			delete this.attributes;
			delete this.styleList;
			delete this.content;
			delete this.params;
			// delete this.events;
			delete this.actions;
			delete this.next;
		}
	}
}
