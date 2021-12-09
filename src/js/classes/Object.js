class Object #lx:namespace lx {

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
