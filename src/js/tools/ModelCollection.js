class ModelCollection extends lx.Collection #lx:namespace lx {
	setModelClass(modelClass) {
		this.modelClass = modelClass;
	}

	add(data) {
		var obj;
		if (!data) {
			obj = new this.modelClass;
		} else if (lx.isInstance(data, this.modelClass)) {
			obj = data;
		} else {
			obj = new this.modelClass(data);
		}
		super.add(obj);
		return obj;
	}

	set(i, data) {
		if (!data) {
			super.set(i, new this.modelClass);
		} else if (lx.isInstance(data, this.modelClass)) {
			super.set(i, data);
		} else {
			super.set(i, new this.modelClass(data));
		}
	}

	insert(i, data) {
		if (!data) {
			super.insert(i, new this.modelClass);
		} else if (lx.isInstance(data, this.modelClass)) {
			super.insert(i, data);
		} else {
			super.insert(i, new this.modelClass(data));
		}
	}

	load(list) {
		list.forEach(fields=>this.add(fields));
	}

	reset(list) {
		this.clear();
		if (list) this.load(list);
	}

	removeByData(data) {
		var indexes = this.searchIndexesByData(data);
		indexes.lxForEachRevert(index=>{
			this.removeAt(index)
		});
	}

	searchIndexesByData(data) {
		var indexes = [];
		this.forEach((elem, index)=>{
			for (var i in data) {
				if (!(i in elem) || data[i] != elem[i]) return;
			}
			indexes.push(index);
		});
		return indexes;
	}

	select(fields) {
		var result = [];
		this.forEach(elem=>{
			for (let name in fields) {
				if (!(name in elem) || fields[name] != elem[name]) return;
			}
			result.push(elem);
		});
		return result;
	}

	unbind() {
		this.forEach(elem=>elem.unbind());
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
