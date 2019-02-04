class Synchronizer #in lx {
	constructor(action) {
		this.keyCounter = 0;
		this.waitingList = [];
		this.action = action;
	}

	setAction(action) {
		this.action = action;
	}

	genKey() {
		return 'k' + (this.keyCounter++);
	}

	allAreReady() {
		for (var i in this.waitingList) {
			if (this.waitingList[i].status === false)
				return false;
		}
		return true;
	}

	ready(obj) {
		var info = this.waitingList[obj.synchronKey];
		info.status = true;
		delete obj.synchronKey;
		obj[info.successMethodName] = info.successMethod;
		obj[info.errorMethodName] = info.errorMethod;
	}

	register(obj, successMethodName, errorMethodName) {
		if (!successMethodName) {
			if (!obj.successMethodName) return false;
			successMethodName = obj.successMethodName;
		}
		if (!errorMethodName) {
			if (!obj.errorMethodName) return false;
			errorMethodName = obj.errorMethodName;
		}

		let successMethod = obj[successMethodName];
		let errorMethod = obj[errorMethodName];

		obj.synchronKey = this.genKey();
		this.waitingList[obj.synchronKey] = {
			status: false,
			successMethodName,
			successMethod,
			errorMethodName,
			errorMethod
		};

		obj[successMethodName] = (...args)=> {
			this.ready(obj);
			if (successMethod && successMethod.isFunction)
				successMethod.apply(obj, args);
		};
		obj[errorMethodName] = (...args)=> {
			//todo здесь можно собирать информацию об ошибках
			this.ready(obj);
			if (errorMethod && errorMethod.isFunction)
				errorMethod.apply(obj, args);
		};
	}

	start(action) {
		if (action) this.setAction(action);
		lx.addTimer(this);
	}

	stop() {
		lx.removeTimer(this);
	}

	go() {
		if (this.allAreReady()) {
			this.stop();
			if (this.action.isFunction) this.action();
		}
	}
}
