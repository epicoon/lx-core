class HashMap #lx:namespace lx {
	constructor() {
		this.map = {};
	}

	add(key, value) {
		if (!(key in this.map)) {
			this.map[key] = value;
			return;
		}

		if (lx.isArray(this.map[key])) {
			if (lx.isArray(value)) {
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
