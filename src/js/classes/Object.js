class Object #lx:namespace lx {

	/**
	 *
	 * */
	afterConstruct() {
		self::behaviorMap.each((beh)=>beh.prototype.onAfterConstruct(this));
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
		behavior.inject(this, config);
	}

	/**
	 *
	 * */
	static addBehavior(behavior, config=null) {
		if (this.behaviorMap.has(behavior)) return;
		behavior.inject(this, config);
	}

	/**
	 * Метод, автоматически вызываемый после определения класса
	 * */
	static __afterDefinition() {
		if (this.lxHasMethod('__injectBehaviors')) this.__injectBehaviors();
	}

}
