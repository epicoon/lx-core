#lx:private;

class SetterListenerBehavior extends lx.Behavior #lx:namespace lx {

	/**
	 *
	 * */
	static inject(supportedEssence, config=null) {
		if (!supportedEssence.isFunction) {
			console.error('lx.SetterListenerBehavior can be added only to class');
			return;
		}

		super.inject(supportedEssence);

		var setterEvents = supportedEssence.behaviorMap.set(behKey, 'setterEvents', {
			fields: [],
			beforeMap: [],  // функции, выполняющиеся ДО присваивания, для ВЫБРАННОГО сеттера
			afterMap: [],   // функции, выполняющиеся ПОСЛЕ присваивания, для ВЫБРАННОГО сеттера
			before: [],     // функции, выполняющиеся ДО присваивания, для ВСЕХ сеттеров
			after: [],      // функции, выполняющиеся ПОСЛЕ присваивания, для ВСЕХ сеттеров
			fail: []        // функции, выполняющиеся в случае неудачного присвоения (какой-то сеттер вернул false)
		});

		let fieldsMap = config ? config : __defineFields(supportedEssence),
			fields = fieldsMap.fields,
			extraFields = fieldsMap.extraFields,
			funcBlank = __getFuncBlank(),
			prototype = supportedEssence.isFunction ? supportedEssence.prototype : supportedEssence;

		for (var i=0, l=fields.len; i<l; i++) {
			let name = fields[i];
			let key = '_' + name;

			Object.defineProperty(prototype, name, {
				//todo - надо чтобы можно было аккуратно снять бихевиор
				configurable: true,
				set: function(val) {
					(new Function("val,name,key,behKey", funcBlank)).call(this, val, name, key, behKey);
				},
				get: function() {
					return this[key];
				}
			});
		}

		/**
		 * Может ассоциировать ключ с любым полем, в т.ч. находящимся в используемых объектах
		 * Может ассоциировать ключ с методом, который может принимать значение, а если не принимает, то возвращает
		 * */
		for (let name in extraFields) {
			fields.push(name);
			var definition = extraFields[name],
				str = definition.isString ? definition : definition.ref,
				assignStr;
			if ( str[str.length-1] == ")" ) {
				assignStr = str.replace(/\(\)$/, "(val);");
			} else assignStr = str + "=val;";
			let ff = funcBlank.replace("this[key] = val;", 'this.' + assignStr);
			Object.defineProperty(prototype, name, {
				configurable: true,
				set: function(val) {
					(new Function("val,name,behKey", ff)).call(this, val, name, behKey);
				},
				get: new Function("", "return(this." + str + ");")
			});
		}

		for (var i=0, l=fields.length; i<l; i++)
			setterEvents.fields.pushUnique(fields[i]);

		if (prototype.constructor.onBeforeSet)
			prototype.constructor.beforeSet(prototype.constructor.onBeforeSet);
		if (prototype.constructor.onAfterSet)
			prototype.constructor.afterSet(prototype.constructor.onAfterSet);
	}

	/**
	 *
	 * */
	getSetterEvents() {
		var thisEvents = this.behaviorMap.get(behKey, 'setterEvents'),
			selfEvents = self::behaviorMap.get(behKey, 'setterEvents');
		if (!thisEvents && !selfEvents) return null;
		if (!thisEvents) return selfEvents;
		if (!selfEvents) return thisEvents;

		var result = {};
		for (var name in selfEvents) {
			var item = [].lxMerge(selfEvents[name]);
			if (thisEvents[name]) item = item.lxMerge(thisEvents[name]);
			result[name] = item;
		}

		return result;
	}

	/**
	 *
	 * */
	static getSetterEvents() {
		return this.prototype.getSetterEvents();
	}

	/**
	 *
	 * */
	static setterListenerFields() { return []; }

	/**
	 *
	 * */
	static setterListenerExtraFields() { return {}; }

	/**
	 *
	 * */
	static beforeSet(name, func) {
		__beforeSet.call(this, name, func);
	}

	/**
	 *
	 * */
	static afterSet(name, func) {
		__afterSet.call(this, name, func);
	}

	/**
	 *
	 * */
	static afterSetFail(func) {
		var setterEvents = this.behaviorMap.get(behKey, 'setterEvents');
		setterEvents.fail.push(func);
	}

	/**
	 *
	 * */
	beforeSet(name, func) {
		if (!this.behaviorMap.get(behKey, 'setterEvents'))
			this.behaviorMap.set(behKey, 'setterEvents', {
				beforeMap: [],  // функции, выполняющиеся ДО присваивания, для ВЫБРАННОГО сеттера
				afterMap: [],   // функции, выполняющиеся ПОСЛЕ присваивания, для ВЫБРАННОГО сеттера
				before: [],     // функции, выполняющиеся ДО присваивания, для ВСЕХ сеттеров
				after: [],      // функции, выполняющиеся ПОСЛЕ присваивания, для ВСЕХ сеттеров
				fail: []        // функции, выполняющиеся в случае неудачного присвоения (какой-то сеттер вернул false)
			});
		__beforeSet.call(this, name, func);
	}

	/**
	 *
	 * */
	afterSet(name, func) {
		if (!this.behaviorMap.get(behKey, 'setterEvents'))
			this.behaviorMap.set(behKey, 'setterEvents', {
				beforeMap: [],  // функции, выполняющиеся ДО присваивания, для ВЫБРАННОГО сеттера
				afterMap: [],   // функции, выполняющиеся ПОСЛЕ присваивания, для ВЫБРАННОГО сеттера
				before: [],     // функции, выполняющиеся ДО присваивания, для ВСЕХ сеттеров
				after: [],      // функции, выполняющиеся ПОСЛЕ присваивания, для ВСЕХ сеттеров
				fail: []        // функции, выполняющиеся в случае неудачного присвоения (какой-то сеттер вернул false)
			});
		__afterSet.call(this, name, func);
	}

	/**
	 *
	 * */
	ignoreSetterListener(fieldName, bool) {
		var ignoreSetterListener = this.behaviorMap.get(behKey, 'ignoreSetterListener');
		if (ignoreSetterListener === null) ignoreSetterListener = {};

		if (fieldName.isBoolean && bool === undefined) {
			if (fieldName) ignoreSetterListener.__ALL__ = true;
			else delete ignoreSetterListener.__ALL__;
			this.behaviorMap.set(behKey, 'ignoreSetterListener', ignoreSetterListener);
			return;
		}

		if (!fieldName.isArray) fieldName = [fieldName];
		if (bool)
			for (var i=0, l=fieldName.length; i<l; i++)
				ignoreSetterListener[fieldName[i]] = true;
		else
			for (var i=0, l=fieldName.length; i<l; i++)
				delete ignoreSetterListener[fieldName[i]];

		this.behaviorMap.set(behKey, 'ignoreSetterListener', ignoreSetterListener);
	}
}


/******************************************************************************************************************************
 * PRIVATE
 *****************************************************************************************************************************/

const behKey = lx.SetterListenerBehavior.lxFullName;

/**
 *
 * */
function __defineFields(supportedClass) {
	var fields = supportedClass.setterListenerFields(),
		result = {
			fields: [],
			extraFields: supportedClass.setterListenerExtraFields()
		};

	if (fields.isArray) {
		for (var i=0, l=fields.length; i<l; i++) {
			var temp = fields[i].split(/\s*<<\s*/);
			if (temp.length == 1) result.fields.push(temp[0]);
			else result.extraFields[temp[0]] = temp[1];
		}
	}

	return result;
}

/**
 *
 * */
function __getFuncBlank() {
	var f = (function(){
		var info = this.getSetterEvents();
		function run(arr, withName=false) {
			for (var i=0, l=arr.len; i<l; i++) {
				var res = withName ? arr[i].call(this, name, val) : arr[i].call(this, val);
				if (res === false) {
					if (info && info.fail)
						info.fail.each((func)=> func.call(this, name, val));
					return false;
				} else if (res !== undefined) {
					val = res;
				}
			}
		}
		var ignoreSetterListener = this.behaviorMap.get(behKey, 'ignoreSetterListener');
		if (!ignoreSetterListener || (!ignoreSetterListener.__ALL__ && !ignoreSetterListener[name])) {
			if (info) {
				if (info.beforeMap && info.beforeMap[name])
					if (run.call(this, info.beforeMap[name]) === false) return;
				if (info.before)
					if (run.call(this, info.before, true) === false) return;
			}
		}
		this[key] = val;
		if (!ignoreSetterListener || (!ignoreSetterListener.__ALL__ && !ignoreSetterListener[name])) {
			if (info) {
				if (info.afterMap && info.afterMap[name])
					if (run.call(this, info.afterMap[name]) === false) return;
				if (info.after)
					if (run.call(this, info.after, true) === false) return;
			}
		}
	}).toString();
	f = f.substring(12, f.length - 1);
	return f;
}

/**
 *
 * */
function __beforeSet(name, func) {
	if (name.isObject) {
		for (let i in name) this.beforeSet(i, name[i]);
		return;
	}

	var setterEvents = this.behaviorMap.get(behKey, 'setterEvents');
	if (name.isString) {
		if (!setterEvents.beforeMap[name])
			setterEvents.beforeMap[name] = [];
		setterEvents.beforeMap[name].pushUnique(func);
		return;
	}
	if (name.isFunction) {
		setterEvents.before.pushUnique(name);
	}
}

/**
 *
 * */
function __afterSet(name, func) {
	if (name.isObject) {
		for (let i in name) this.afterSet(i, name[i]);
		return;
	}
	var setterEvents = this.behaviorMap.get(behKey, 'setterEvents');
	if (name.isString) {
		if (!setterEvents.afterMap[name])
			setterEvents.afterMap[name] = [];
		setterEvents.afterMap[name].pushUnique(func);
		return;
	}
	if (name.isFunction) {
		setterEvents.after.pushUnique(name);
	}
}
