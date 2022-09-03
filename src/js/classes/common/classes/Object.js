#lx:namespace lx;
class Object {

	/**
	 *
	 * */
	afterConstruct() {
		self::behaviorMap.forEach(beh=>beh.prototype.onAfterConstruct(this));
	}

	/**
	 *
	 * */
	get behaviorMap() {
		return new lx.BehaviorMap(this);
	}

	/**
	 *
	 * */
	static get behaviorMap() {
		return new lx.BehaviorMap(this);
	}

	/**
	 *
	 * */
	addBehavior(behavior, config=null) {
		if (this.behaviorMap.has(behavior) || self::behaviorMap.has(behavior)) return;
		behavior.injectInto(this, config);
	}

	/**
	 *
	 * */
	hasBehavior(behavior) {
		return this.behaviorMap.has(behavior) || self::behaviorMap.has(behavior);
	}

	static delegateMethods(map) {
		let ownMethods = lx.globalContext.Object.getOwnPropertyNames(this.prototype);
		ownMethods.lxPushUnique('constructor');
		for (let fieldName in map) {
			let stuff = map[fieldName];
			let methods = lx.globalContext.Object.getOwnPropertyNames(stuff.prototype);
			let delegatedMethods = methods.lxDiff(ownMethods);

			for (let i=0, l=delegatedMethods.length; i<l; i++) {
				let methodName = delegatedMethods[i];
				this.prototype[methodName] = function (...args) {
					return this[fieldName][methodName](args);
				}
			}
		}
	}
	
	/**
	 *
	 * */
	static addBehavior(behavior, config=null) {
		if (this.behaviorMap.has(behavior)) return;
		behavior.injectInto(this, config);
	}

	/**
	 * Метод, автоматически вызываемый после определения класса
	 * */
	static __afterDefinition() {
		if (this.lxHasMethod('__injectBehaviors')) this.__injectBehaviors();
	}
}
