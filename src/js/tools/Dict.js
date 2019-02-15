class Dict #lx:namespace lx {
	constructor(data = {}) {
		for (var key in data) this[key] = data[key];
	}

	get isArray() { return true; }
	get isAssoc() { return true; }

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

	len() {
		var count = 0;
		for (var i in this) count++;
		return count;
	}

	nth(index) {
		var i = 0;
		for (var key in this) {
			if (i == index) return this[key];
			i++;
		}
	}

	last() {
		var result;
		for (var i in this) result = this[i];
		return result;
	}
}
