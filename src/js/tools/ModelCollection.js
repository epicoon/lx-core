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

	load(list) {
		list.each(fields=>this.add(fields));
	}

	reset(list) {
		this.clear();
		if (list) this.load(list);
	}

	removeByData(data) {
		var indexes = this.searchIndexesByData(data);
		indexes.eachRevert(index=>{
			this.removeAt(index)
		});
	}

	searchIndexesByData(data) {
		var indexes = [];
		this.each((elem, index)=>{
			for (var i in data) {
				if (!(i in elem) || data[i] != elem[i]) return;
			}
			indexes.push(index);
		});
		return indexes;
	}

	unbind() {
		this.each(elem=>elem.unbind());
	}

	static create(config) {
		class _am_ extends lx.BindableModel {};
		_am_.initSchema(config.schema);
		let c = new lx.ModelCollection();
		c.setModelClass(_am_);
		c.load(config.list);
		return c;
	}
}
