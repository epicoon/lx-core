#lx:private;

class SetterListenerBehavior extends lx.Behavior #lx:namespace lx {

	/**
	 *
	 * */
	static inject(supportedEssence, config=null) {
		super.inject(supportedEssence);

		var setterEvents = supportedEssence.behaviorMap.set('lx.SetterListenerBehavior', 'setterEvents', {
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
					(new Function("val,name,key", funcBlank)).call(this, val, name, key);
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
					(new Function("val,name", ff)).call(this, val, name);
				},
				get: new Function("", "return(this." + str + ");")
			});
		}

		for (var i=0, l=fields.length; i<l; i++)
			setterEvents.fields.pushUnique(fields[i]);
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
	static onSetterFail(func) {
		var setterEvents = this.behaviorMap.get('lx.SetterListenerBehavior', 'setterEvents');
		setterEvents.fail.push(func);
	}

	/**
	 *
	 * */
	beforeSet(name, func) {
		if (!this.behaviorMap.get('lx.SetterListenerBehavior', 'setterEvents'))
			this.behaviorMap.set('lx.SetterListenerBehavior', 'setterEvents', {
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
		if (!this.behaviorMap.get('lx.SetterListenerBehavior', 'setterEvents'))
			this.behaviorMap.set('lx.SetterListenerBehavior', 'setterEvents', {
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
	beforeSetIgnore(fieldName, bool) {
		var beforeSetIgnore = this.behaviorMap.get('lx.SetterListenerBehavior', 'beforeSetIgnore');
		if (beforeSetIgnore === null) beforeSetIgnore = {};

		if (fieldName.isBoolean && bool === undefined) {
			if (fieldName) beforeSetIgnore.__ALL__ = true;
			else delete beforeSetIgnore.__ALL__;
			this.behaviorMap.set('lx.SetterListenerBehavior', 'beforeSetIgnore', beforeSetIgnore);
			return;
		}

		if (!fieldName.isArray) fieldName = [fieldName];
		if (bool)
			for (var i=0, l=fieldName.length; i<l; i++)
				beforeSetIgnore[fieldName[i]] = true;
		else
			for (var i=0, l=fieldName.length; i<l; i++)
				delete beforeSetIgnore[fieldName[i]];

		this.behaviorMap.set('lx.SetterListenerBehavior', 'beforeSetIgnore', beforeSetIgnore);
	}


}


/******************************************************************************************************************************
 * PRIVATE
 *****************************************************************************************************************************/

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
		var info = self::behaviorMap.get('lx.SetterListenerBehavior', 'setterEvents'),
			thisInfo = this.behaviorMap.get('lx.SetterListenerBehavior', 'setterEvents');
		function run(arr) {
			for (var i=0, l=arr.len; i<l; i++) {
				var res = arr[i].call(this, name, val);
				if (res === false) {
					if (thisInfo && thisInfo.fail)
						thisInfo.fail.each((func)=> func.call(this, name, val));
					if (info && info.fail)
						info.fail.each((func)=> func.call(this, name, val));
					return false;
				} else if (res !== undefined) {
					val = res;
				}
			}
		}
		var beforeSetIgnore = this.behaviorMap.get('lx.SetterListenerBehavior', 'beforeSetIgnore');
		if (!beforeSetIgnore || (!beforeSetIgnore.__ALL__ && !beforeSetIgnore[name])) {
			if (thisInfo) {
				if (thisInfo.beforeMap && thisInfo.beforeMap[name])
					if (run.call(this, thisInfo.beforeMap[name]) === false) return;
				if (thisInfo.before)
					if (run.call(this, thisInfo.before) === false) return;
			}
			if (info) {
				if (info.beforeMap && info.beforeMap[name])
					if (run.call(this, info.beforeMap[name]) === false) return;
				if (info.before)
					if (run.call(this, info.before) === false) return;
			}
		}
		this[key] = val;
		if (thisInfo) {
			if (thisInfo.afterMap && thisInfo.afterMap[name])
				if (run.call(this, thisInfo.afterMap[name]) === false) return;
			if (thisInfo.after)
				if (run.call(this, thisInfo.after) === false) return;
		}
		if (info) {
			if (info.afterMap && info.afterMap[name])
				if (run.call(this, info.afterMap[name]) === false) return;
			if (info.after)
				if (run.call(this, info.after) === false) return;
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

	var setterEvents = this.behaviorMap.get('lx.SetterListenerBehavior', 'setterEvents');
	if (name.isString) {
		if (!setterEvents.beforeMap[name])
			setterEvents.beforeMap[name] = [];
		setterEvents.beforeMap[name].push(func);
		return;
	}
	if (name.isFunction) {
		setterEvents.before.push(name);
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
	var setterEvents = this.behaviorMap.get('lx.SetterListenerBehavior', 'setterEvents');
	if (name.isString) {
		if (!setterEvents.afterMap[name])
			setterEvents.afterMap[name] = [];
		setterEvents.afterMap[name].push(func);
		return;
	}
	if (name.isFunction) {
		setterEvents.after.push(name);
	}
}
