class ModelCollection extends lx.Collection #in lx {
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

	load(data) {
		data.each((fields)=>this.add(fields));
	}

	reset(data) {
		this.clear();
		if (data) this.load(data);
	}
}
