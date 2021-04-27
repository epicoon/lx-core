class HashMap #lx:namespace lx {
	constructor() {
		this.map = {};
	}

	add(key, value) {
		if (!(key in this.map)) {
			this.map[key] = value;
			return;
		}

		if (this.map[key].isArray) {
			if (value.isArray) {
				this.map[key].lxMerge(value);
			} else {
				this.map[key].push(value);
			}
			return;
		}

		this.map[key] = [this.map[key], value];
	}
	
	toObject() {
		return this.map;
	}
}
