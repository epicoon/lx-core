class BehaviorMap #lx:namespace lx {
	constructor(supportedEssence) {
		this.supportedEssence = supportedEssence;
	}

	get map() { return this.supportedEssence.__lxBehaviors; }
	get isEmpty() { return !this.supportedEssence.__lxBehaviors; }

	/**
	 *
	 * */
	register(behavior) {
		if (this.isEmpty) this.__resetMap();
		this.map.list.push(behavior);
	}

	/**
	 *
	 * */
	set(behaviorKey, key, value) {
		if (this.isEmpty) this.__resetMap();
		if (!this.map.data[behaviorKey]) this.map.data[behaviorKey] = {};
		this.map.data[behaviorKey][key] = value;
		return value;
	}

	/**
	 *
	 * */
	get(behaviorKey, key) {
		if (this.isEmpty) return null;
		if (!this.map.data[behaviorKey] || this.map.data[behaviorKey][key] === undefined) return null;
		return this.map.data[behaviorKey][key];		
	}

	/**
	 *
	 * */
	has(behavior) {
		if (this.isEmpty) return false;
		return this.map.list.contain(behavior);
	}

	/**	
	 *
	 * */
	each(func) {
		if (this.isEmpty) return;
		this.map.list.each(func);
	}

	/**
	 *
	 * */
	__resetMap() {
		this.supportedEssence.__lxBehaviors = {
			list: [],
			data: {}
		};
	}
}
