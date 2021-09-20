class Synchronizer #lx:namespace lx {
	constructor(callback) {
		this.keyCounter = 0;
		this.waitingList = [];
		this.callback = callback;
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
			if (lx.isFunction(successMethod))
				successMethod.apply(obj, args);
		};
		obj[errorMethodName] = (...args)=> {
			//todo здесь можно собирать информацию об ошибках
			this.ready(obj);
			if (lx.isFunction(errorMethod))
				errorMethod.apply(obj, args);
		};
	}

	setCallback(callback) {
		this.callback = callback;
	}

	start(callback) {
		if (callback) this.setCallback(callback);
		lx.addTimer(this);
	}

	stop() {
		lx.removeTimer(this);
	}

	go() {
		if (this.allAreReady()) {
			this.stop();
			if (lx.isFunction(this.callback)) this.callback();
		}
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
}
