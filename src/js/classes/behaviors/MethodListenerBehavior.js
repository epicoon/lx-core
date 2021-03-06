#lx:private;

/**
 *
 * */
class MethodListenerBehavior extends lx.Behavior #lx:namespace lx {

	/**
	 *
	 * */
	static inject(supportedEssence, config=null) {
		super.inject(supportedEssence);

		supportedEssence.behaviorMap.set(behKey, 'methodEvents', {});
	}

	/**
	 *
	 * */
	static beforeMethod(funcName, func, duplicate = false) {
		__setMethodEvent.call(this, funcName, func, 'before', duplicate);
	}

	/**
	 *
	 * */
	static afterMethod(funcName, func, duplicate = false) {
		__setMethodEvent.call(this, funcName, func, 'after', duplicate);
	}

	/**
	 *
	 * */
	beforeMethod(funcName, func, duplicate = false) {
		if (!this.behaviorMap.get(behKey, 'methodEvents'))
			this.behaviorMap.set(behKey, 'methodEvents', {});
		__setMethodEvent.call(this, funcName, func, 'before', duplicate);
	}

	/**
	 *
	 * */
	afterMethod(funcName, func, duplicate = false) {
		if (!this.behaviorMap.get(behKey, 'methodEvents'))
			this.behaviorMap.set(behKey, 'methodEvents', {});
		__setMethodEvent.call(this, funcName, func, 'after', duplicate);
	}
}


/******************************************************************************************************************************
 * PRIVATE
 *****************************************************************************************************************************/

const behKey = lx.MethodListenerBehavior.lxFullName;

/**
 *
 * */
function __setMethodEvent(funcName, func, category, duplicate) {
	var methodEvents = this.behaviorMap.get(behKey, 'methodEvents');
	if (!duplicate
		&& methodEvents[funcName]
		&& methodEvents[funcName][category]
		&& methodEvents[funcName][category].contains(func)
	) return;

	var obj = this.isFunction ? this.prototype : this,
		temp = obj.__proto__,
		finded = false;
	while (temp && !finded) {
		var names = Object.getOwnPropertyNames(temp);
		if (names.contains(funcName) && obj[funcName].isFunction) finded = true;
		temp = temp.__proto__;
	}
	var names = Object.getOwnPropertyNames(obj);
	var selfFail = (!names.contains(funcName) || !obj[funcName].isFunction);
	var prototypeFail = true;
	if (obj.prototype) {
		var names = Object.getOwnPropertyNames(obj.prototype);
		prototypeFail = (!names.contains(funcName) || !obj[funcName].isFunction);
	}
	if (!finded && selfFail && prototypeFail) return;

	__wrapMethod.call(obj, funcName);

	if (!methodEvents[funcName]) {
		methodEvents[funcName] = {
			before: [],
			after: []
		};
	}
	methodEvents[funcName][category].push(func);
}

/**
 *
 * */
function __wrapMethod(methodName) {
	var info = self::behaviorMap.get(behKey, 'methodEvents'),
		thisInfo = this.behaviorMap.get(behKey, 'methodEvents');

	if ((info && info[methodName]) || (thisInfo && thisInfo[methodName])) return;

	var funcClosure = this[methodName];

	Object.defineProperty(this, methodName, {
		enumerable: false,
		value: function() {
			var info = self::behaviorMap.get(behKey, 'methodEvents'),
				thisInfo = this.behaviorMap.get(behKey, 'methodEvents');

			if (thisInfo && thisInfo[methodName]) {
				if (thisInfo[methodName].before)
					for (var i=0, l=thisInfo[methodName].before.len; i<l; i++)
						if (thisInfo[methodName].before[i].apply(this, arguments) === false) return;
			}
			if (info && info[methodName]) {
				if (info[methodName].before)
					for (var i=0, l=info[methodName].before.len; i<l; i++)
						if (info[methodName].before[i].apply(this, arguments) === false) return;
			}
			let result = funcClosure.apply(this, arguments);
			if (thisInfo && thisInfo[methodName]) {
				if (thisInfo[methodName].after)
					for (var i=0, l=thisInfo[methodName].after.len; i<l; i++)
						if (thisInfo[methodName].after[i].apply(this, arguments) === false) return;
			}
			if (info && info[methodName]) {
				if (info[methodName].after)
					for (var i=0, l=info[methodName].after.len; i<l; i++)
						if (info[methodName].after[i].apply(this, arguments) === false) return;
			}
			return result;
		}
	});
}
