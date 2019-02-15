//todo - сделать универсально, чтобы применять можно было и к классам и к объектам

lx.BehaviorOLD = {
	//todo способ регистрации основанный на именах функций это плохо

	hasOwnBehavior: function(cstr, beh) {
		if (cstr.isObject) cstr = cstr.constructor;
		if (cstr.hasOwnProperty('behaviors'))
			return (beh.name in cstr.behaviors);
		return false;
	},

	hasBehavior: function(cstr, beh) {
		if (cstr.isObject) cstr = cstr.constructor;
		if (!cstr.behaviors) return false;
		return (beh.name in cstr.behaviors)
	},

	get: function(cstr, beh) {
		if (cstr.isObject) cstr = cstr.constructor;
		if (!cstr.behaviors || !(beh.name in cstr.behaviors)) return false;

		return cstr.behaviors[beh.name];
		// for (var i=0, l=cstr.behaviors.len; i<l; i++)
		// 	if (cstr.behaviors[i].func == beh) return cstr.behaviors[i];
		// return false;
	},

	register: function(cstr, func, data) {
		if (func.name == '') console.log(func);

		if (this.hasBehavior(cstr, func)) return false;

		if (!cstr.behaviors) cstr.behaviors = [];
		cstr.behaviors[func.name] = { func, data };
		return cstr.behaviors[func.name];
	}
};






lx.BehaviorOLD.setterListener = function(constructor, fields=[], extraFields={}) {

	//todo костыльно захреначено в функцию, чтобы бихевиор имя имел, в Binder тоже есть такое
	var setterBehavior = function(constructor, fields, extraFields) {
		if (!lx.BehaviorOLD.hasOwnBehavior(constructor, arguments.callee)) {
			constructor.__setterEvents = {
				fields: [],
				beforeMap: [],  // функции, выполняющиеся ДО присваивания, для ВЫБРАННОГО сеттера
				afterMap: [],   // функции, выполняющиеся ПОСЛЕ присваивания, для ВЫБРАННОГО сеттера
				before: [],     // функции, выполняющиеся ДО присваивания, для ВСЕХ сеттеров
				after: [],      // функции, выполняющиеся ПОСЛЕ присваивания, для ВСЕХ сеттеров
				fail: []        // функции, выполняющиеся в случае неудачного присвоения (какой-то сеттер вернул false)
			};
		}

		if (!lx.BehaviorOLD.hasBehavior(constructor, arguments.callee)) {
			lx.BehaviorOLD.register(constructor, arguments.callee);

			constructor.onBeforeSet = function(name, func) {
				if (name.isObject) {
					for (let i in name) this.beforeSet(i, name[i]);
					return;
				}
				if (name.isString) {
					if (!this.__setterEvents.beforeMap[name])
						this.__setterEvents.beforeMap[name] = [];
					this.__setterEvents.beforeMap[name].push(func);
					return;
				}
				if (name.isFunction) {
					this.__setterEvents.before.push(name);
				}
			};
			constructor.onAfterSet = function(name, func) {
				if (name.isObject) {
					for (let i in name) this.afterSet(i, name[i]);
					return;
				}
				if (name.isString) {
					if (!this.__setterEvents.afterMap[name])
						this.__setterEvents.afterMap[name] = [];
					this.__setterEvents.afterMap[name].push(func);
					return;
				}
				if (name.isFunction) {
					this.__setterEvents.after.push(name);
				}
			};
			constructor.onSetterFail = function(func) {
				this.__setterEvents.fail.push(func);
			};
			constructor.prototype.__beforeSetIgnore = [];
			constructor.prototype.beforeSetIgnore = function(name, bool) {
				if (name.isBoolean && bool === undefined) {
					if (name) this.__beforeSetIgnore.__ALL__ = true;
					else delete this.__beforeSetIgnore.__ALL__;
					return
				}

				if (!name.isArray) name = [name];
				if (bool)
					for (var i=0, l=name.length; i<l; i++)
						this.__beforeSetIgnore[name[i]] = true;
				else
					for (var i=0, l=name.length; i<l; i++)
						delete this.__beforeSetIgnore[name[i]];
			};
		}

		if (fields === undefined || fields === true) fields = [];
		if (constructor.__schema) {
			for (let name in constructor.__schema) {
				let definition = constructor.__schema[name];
				if (definition.ref) extraFields[name] = definition;
				else if (fields !== false) fields.push(name);
			}
		}

		// args(val, name, key)
		var f = (function(){
			var info = self::__setterEvents;
			function run(arr) {
				for (var i=0, l=arr.len; i<l; i++) {
					var res = arr[i].call(this, name, val);
					if (res === false) {
						info.fail.each((func)=> func.call(this, name, val));
						return false;
					} else if (res !== undefined) {
						val = res;
					}
				}
			}
			if (info.beforeMap[name]
				&&
				(!this.__beforeSetIgnore || (!this.__beforeSetIgnore.__ALL__ && !this.__beforeSetIgnore[name]))
			) if (run.call(this, info.beforeMap[name]) === false) return;
			if (run.call(this, info.before) === false) return;
			this[key] = val;
			if (info.afterMap[name])
				if (run.call(this, info.afterMap[name]) === false) return;
			if (run.call(this, info.after) === false) return;
		}).toString();
		f = f.substring(12, f.length - 1);


		for (var i=0, l=fields.len; i<l; i++) {
			let name = fields[i];
			// if (constructor.__setterEvents.fields.contain(name)) continue;
			let key = '_' + name;

			Object.defineProperty(constructor.prototype, name, {
				//todo - надо чтобы можно было аккуратно снять бихевиор
				configurable: true,
				set: function(val) {
					(new Function("val,name,key", f)).call(this, val, name, key);
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
			if (constructor.__setterEvents.fields.contain(name)) continue;
			fields.push(name);
			var definition = extraFields[name],
				str = definition.isString ? definition : definition.ref,
				assignStr;
			if ( str[str.length-1] == ")" ) {
				assignStr = str.replace(/\(\)$/, "(val);");
			} else assignStr = str + "=val;";
			let ff = f.replace("this[key] = val;", 'this.' + assignStr);
			Object.defineProperty(constructor.prototype, name, {
				configurable: true,
				set: function(val) {
					(new Function("val,name", ff)).call(this, val, name);
				},
				get: new Function("", "return(this." + str + ");")
			});
		}

		for (var i=0, l=fields.length; i<l; i++)
			constructor.__setterEvents.fields.pushUnique(fields[i]);

		//todo еще добавить чтобы на экземпляре были такие же методы
	};
	setterBehavior(constructor, fields, extraFields);
};



/*
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
setterListener переписан - SetterListenerBehavior
	он пока не работает только со схемой
	схему надо классом делать, отсюда плясать, дописывать бихевиор и удалять этот код
*/






































/*
todo - это поведение вызывается как функция, работает с самим объектом
не связано с lx.Behavior никак вообще. Путаница, надо что-то придумать
для разграничения логики поведений, работающих с объектами и классами
*/
lx.BehaviorOLD.methodListener = function(obj) {
	//todo !!!!
	if (obj.onBeforeMethod) return;

	obj.methodEvents = [];

	function setMethodEvent(obj, funcName, func, category) {
		var temp = obj.__proto__,
			finded = false;
		while (temp && !finded) {
			var names = Object.getOwnPropertyNames(temp);
			if (names.contain(funcName) && obj[funcName].isFunction) finded = true;
			temp = temp.__proto__;
		}
		var names = Object.getOwnPropertyNames(obj);
		var selfFail = (!names.contain(funcName) || !obj[funcName].isFunction);
		var prototypeFail = true;
		if (obj.prototype) {
			var names = Object.getOwnPropertyNames(obj.prototype);
			prototypeFail = (!names.contain(funcName) || !obj[funcName].isFunction);
		}
		if (!finded && selfFail && prototypeFail) return;

		if (!obj.methodEvents[funcName]) {
			obj.methodEvents[funcName] = {
				before: [],
				after: []
			};
			var funcClosure = obj[funcName];

			Object.defineProperty(obj, funcName, {
				enumerable: false,
				value: function() {
					if (this.methodEvents[funcName].before)
					for (var i=0, l=this.methodEvents[funcName].before.len; i<l; i++)
						if (this.methodEvents[funcName].before[i].apply(this, arguments) === false) return;
					funcClosure.apply(this, arguments);
					if (this.methodEvents[funcName].after)
					for (var i=0, l=this.methodEvents[funcName].after.len; i<l; i++)
						if (this.methodEvents[funcName].after[i].apply(this, arguments) === false) return;
				}
			});
		}

		obj.methodEvents[funcName][category].push(func);
	}


	Object.defineProperty(obj, "onBeforeMethod", {
		enumerable: false,
		value: function(funcName, func) {
			setMethodEvent(this, funcName, func, 'before');
		}
	});

	Object.defineProperty(obj, "onAfterMethod", {
		enumerable: false,
		value: function(funcName, func) {
			setMethodEvent(this, funcName, func, 'after');
		}
	});
};
