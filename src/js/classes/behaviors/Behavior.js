class Behavior #lx:namespace lx {
	onAfterConstruct(supportedObject) {}

	/**
	 *
	 * */
	static inject(supportedEssence, config=null) {
		var names = Object.getOwnPropertyNames(this.prototype);
		var funcNames = [];
		for (var i=0, l=names.length; i<l; i++) {
			if (names[i] == 'constructor' || names[i] == 'onAfterConstruct') continue;
			if (this.prototype[names[i]].isFunction) funcNames.push(names[i]);
		}

		if (supportedEssence.isFunction) {
			var staticNames = Object.getOwnPropertyNames(this);
			var staticFuncNames = [];
			for (var i=0, l=staticNames.length; i<l; i++) {
				if (staticNames[i] == 'inject') continue;
				if (this[staticNames[i]].isFunction) staticFuncNames.push(staticNames[i]);
			}
			for (var i=0, l=staticFuncNames.length; i<l; i++) {
				if (supportedEssence[staticFuncNames[i]] === undefined)
					supportedEssence[staticFuncNames[i]] = this[staticFuncNames[i]];
			}
			for (var i=0, l=funcNames.length; i<l; i++) {
				if (supportedEssence.prototype[funcNames[i]] === undefined) 
					supportedEssence.prototype[funcNames[i]] = this.prototype[funcNames[i]];
			}
		} else if (supportedEssence instanceof lx.Object) {
			for (var i=0, l=funcNames.length; i<l; i++) {
				if (supportedEssence[funcNames[i]] === undefined) 
					supportedEssence[funcNames[i]] = this.prototype[funcNames[i]];
			}			
		}

		supportedEssence.behaviorMap.register(this);
	}
}
