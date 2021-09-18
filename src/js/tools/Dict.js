class Dict #lx:namespace lx {
	constructor(data = {}) {
		for (var key in data) this[key] = data[key];
	}

	static create(data) {
		if (data === undefined || data === null)
			return new this();
		
		if (data.constructor === this) return data;
		return new this(data);
	}

	get len() {
		var count = 0;
		for (var i in this) count++;
		return count;
	}

	each(func) {
		var context = null;
		if (func.isArray) {
			context = func[0];
			func = func[1];
		}
		for (var key in this) func.call(context, this[key], key);
	}

	keys() {
		var result = [];
		for (var key in this) result.push(key);
		return result;
	}

	nth(index) {
		var i = 0;
		for (var key in this) {
			if (i == index) return this[key];
			i++;
		}
	}

	nthKey(index) {
		var i = 0;
		for (var key in this) {
			if (i == index) return key;
			i++;
		}		
	}

	last() {
		var result;
		for (var i in this) result = this[i];
		return result;
	}
}
