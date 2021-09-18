class LocalEventSupervisor #lx:namespace lx {
	constructor() {
		this.listeners = {};
	}

	subscribe(eventName, callback) {
		if (eventName.isObject) {
			for (var i in eventName) this.subscribe(i, eventName[i]);
			return;
		}

		if (!(eventName in this.listeners)) this.listeners[eventName] = [];
		this.listeners[eventName].push(callback);
	}

	trigger(eventName, args = []) {
		if (eventName in this.listeners) {
			if (args === null || args === undefined || !args.isArray) args = [args];
			for (var i=0, l=this.listeners[eventName].len; i<l; i++)
				lx._f.callFunction(this.listeners[eventName][i], args);
		}
	}
}
