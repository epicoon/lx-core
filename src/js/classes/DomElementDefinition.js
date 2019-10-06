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

    #lx:server createElement() {
		lx.WidgetHelper.register(this.owner);
		this.lxid = this.owner.lxid;
		this.setAttribute('lxid', this.lxid);

		if (this.parent) {
			if (this.next) this.parent.allChildren.insertBefore(this.owner, this.next);
			else this.parent.allChildren.push(this.owner);
		}
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
	}

	setParent(parent, next) {
		this.parent = parent;
		this.next = next;
	}

	clear() {
		lx.WidgetHelper.unregister(this.lxid);

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

		#lx:server {
			this.owner.allChildren.remove(elemDefinition.owner);
		}
		
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
		#lx:server {
			if (!this.parent) return null;
			return this.parent.allChildren.next(this.owner);
		}
		
		#lx:client {
			if (!this.elem) return null;
			var ns = this.elem.nextSibling;
			if (!ns) return null;
			return lx.WidgetHelper.getByElem(ns);
		}
	}

	prevSibling() {
		#lx:server {
			if (!this.parent) return null;
			return this.parent.allChildren.prev(this.owner);
		}
		
		#lx:client {
			if (!this.elem) return null;
			var ps = this.elem.previousSibling;
			if (!ps) return null;
			return lx.WidgetHelper.getByElem(ps);
		}
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
		var result = '<' + this.tag;

		if (this.classList.len) result += ' class="' + this.classList.join(' ') + '"';

		for (var name in this.attributes) {
			result += ' ' + name + '="' + this.attributes[name] + '"';
		}

		if (!this.styleList.lxEmpty) {
			result += ' style="';
			for (var name in this.styleList) {
				var val = this.styleList[name];
				if (val === undefined || val === null || val === '') continue;
				var propName = name.replace(/([A-Z])/g, function(x){return "-" + x.toLowerCase()});
				result += propName + ':' + val + ';';
			}
			result += '"';
		}

		return result + '>';
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
		setElem(elem) {
			lx.WidgetHelper.register(this.owner, elem);
			this.lxid = this.owner.lxid;
			this.elem = elem;
			this.applyElemFeatures();
		}

		refreshElem(parent = null) {
			this.elem = lx.WidgetHelper.getElementByLxid(this.lxid, parent);
			for (var eventName in this.events) {
				for (var i = 0, l = this.events[eventName].len; i < l; i++) {
					lx.Event.add(this.elem, eventName, this.events[eventName][i]);
				}
			}
		}

		applyElemFeatures() {
			if (this.params === undefined) return;

			for (var name in this.params) {
				this.elem[name] = this.params[name];
			}

			for (var i = 0, l = this.actions.len; i < l; i++) {
				var item = this.actions[i],
					funcName = item[0],
					args = item[1];
				if (args.isArray) this.owner[funcName].apply(this.owner, args);
				else this.owner[funcName].call(this.owner);
			}

			for (var eventName in this.events) {
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
