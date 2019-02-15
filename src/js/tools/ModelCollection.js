class ModelCollection extends lx.Collection #lx:namespace lx {
	setModelClass(modelClass) {
		this.modelClass = modelClass;
	}

	add(data) {
		if (!data) {
			super.add(new this.modelClass);
		} else if (data.is(this.modelClass)) {
			super.add(data);
		} else {
			super.add(new this.modelClass(data));
		}
	}

	set(i, data) {
		if (!data) {
			super.set(i, new this.modelClass);
		} else if (data.is(this.modelClass)) {
			super.set(i, data);
		} else {
			super.set(i, new this.modelClass(data));
		}
	}

	load(data) {
		data.each((fields)=>this.add(fields));
	}

	reset(data) {
		this.clear();
		if (data) this.load(data);
	}
}
