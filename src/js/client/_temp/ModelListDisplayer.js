class ModelListDisplayer #lx:namespace lx {
	/**
	 * config = {
	 *	headHeight
	 *	columnWidth
	 *	integerColumnWidth
	 *	booleanColumnWidth
	 *	modelClass
	 *	lock
	 *	hide
	 *	formModifier
	 *	fieldsModifier
	 *	data
	 * }
	 * */
	constructor(config = {}) {
		this.sideColumns = [];
		this.bodyColumns = [];

		this.init(config);
	}

	/**
	 * Можно повторно передать конфиг, изменив некоторые параметры
	 * */
	apply(config = {}) {
		this.init(config);
		this.reset();
	}

	reset() {
		if (!this.box) return;

		var box = this.box;
		box.clear();

		if (!this.data) return;

		var fieldConfigs = this.__defineFieldConfigs();
		var addedColumns = this.__defineAddedColumns();
		this.__buildMainComponents(box, this.headHeight, fieldConfigs.sideWidth, fieldConfigs.bodyWidth);
		this.__buildComponentContent(
			this.data,
			addedColumns.preSideFields,
			fieldConfigs.sideFields,
			addedColumns.postSideFields,
			fieldConfigs.sideCols,
			box->headSide,
			box->side
		);
		this.__buildComponentContent(
			this.data,
			addedColumns.preBodyFields,
			fieldConfigs.bodyFields,
			addedColumns.postBodyFields,
			fieldConfigs.bodyCols,
			box->headBody,
			box->body
		);
	}

	/**
	 * Инициализация параметров - перезаписываются переданные, непереданные остаются прежними
	 * */
	init(config) {
		this.headHeight = lx.getFirstDefined(config.headHeight, this.headHeigh, '25px');
		this.columnWidth = lx.getFirstDefined(config.columnWidth, this.columnWidth, '150px');
		this.integerColumnWidth = lx.getFirstDefined(config.integerColumnWidth, this.integerColumnWidth, '100px');
		this.booleanColumnWidth = lx.getFirstDefined(config.booleanColumnWidth, this.booleanColumnWidth, '100px');

		this.modelClass = lx.getFirstDefined(config.modelClass, this.modelClass, null);
		this.lock = lx.getFirstDefined(config.lock, this.lock, []);
		this.hide = lx.getFirstDefined(config.hide, this.hide, []);

		this.formModifier = lx.getFirstDefined(config.formModifier, this.formModifier, null);
		this.fieldsModifier = lx.getFirstDefined(config.fieldsModifier, this.fieldsModifier, {});

		this.box = lx.getFirstDefined(config.box, this.box, null);
		this.data = lx.getFirstDefined(config.data, this.data, null);
	}

	/**
	 * Добавить колонку, не являющуюся частью схемы представляемой модели
	 * */
	addColumn(config) {
		if (!config.render || !lx.isFunction(config.render)) return;

		config.lock = lx.getFirstDefined(config.lock, true);
		config.widget = lx.getFirstDefined(config.widget, lx.Box);
		config.position = lx.getFirstDefined(config.position, lx.RIGHT);
		config.width = lx.getFirstDefined(config.width, '100px');
		config.label = lx.getFirstDefined(config.label, '');

		if (config.lock) this.sideColumns.push(config);
		else this.bodyColumns.push(config);
	}

	/**
	 *
	 * */
	getRow(index) {
		return {
			side: this.box->side.child(index),
			body: this.box->body.child(index)
		};
	}

	unbindData() {
		if (this.box.contains('side'))
			lx.Binder.unbindMatrix(this.box->side);
		if (this.box.contains('body'))
			lx.Binder.unbindMatrix(this.box->body);
	}

	/**
	 *
	 * */
	dropData() {
		if (!this.data) return;

		this.unbindData();
		this.data = null;
		this.box.clear();
	}

	/**
	 * Наполенние основных контейнеров содержимым согласно переданной коллекции моделей
	 * */
	__buildComponentContent(data, pre, fields, post, colsCount, head, body) {
		if (fields.lxEmpty()) return;

		var fieldsModifier = this.fieldsModifier,
			formModifier = this.formModifier;

		pre.forEach((a)=>head.add(lx.Box, {text:a.label}).align(lx.CENTER, lx.MIDDLE));
		for (var name in fields) head.add(lx.Box, {text: name}).align(lx.CENTER, lx.MIDDLE);
		post.forEach((a)=>head.add(lx.Box, {text:a.label}).align(lx.CENTER, lx.MIDDLE));

		body.matrix({
			items: data,
			itemBox: [lx.Form, {grid: {cols: colsCount, indent: '10px'}}],
			itemRender: (form)=> {
				if (formModifier) formModifier(form);

				pre.forEach((a)=>{
					var w = a.widget, c = {};
					if (lx.isArray(w)) { c = w[1]; w = w[0]; }
					if (!c.width) c.width = 1;
					var widget = new w(c);
					a.render(widget);
				});
				form.fields(fields);
				post.forEach((a)=>{
					var w = a.widget, c = {};
					if (lx.isArray(w)) { c = w[1]; w = w[0]; }
					if (!c.width) c.width = 1;
					var widget = new w(c);
					a.render(widget);
				});

				form.getChildren().forEach(a=>{
					if (fieldsModifier[a.key]) fieldsModifier[a.key](a);
					else if (fieldsModifier['default']) fieldsModifier['default'](a);
				});
			}
		});
	}

	/**
	 * Построение основных контейнеров виджета:
	 * - несмещаемого заголовка
	 * - смещаемого заголовка
	 * - горизонтально несмещаемого содержимого
	 * - смещаемого содержимого
	 * */
	__buildMainComponents(elem, height, sideWidth, bodyWidth) {
		elem.overflow('auto');

		var body = elem.add(lx.Box, {
			key: 'body',
			left: sideWidth,
			top: height,
			width: bodyWidth,
			stream: true
		});
		body.border();

		var side = elem.add(lx.Box, {
			key: 'side',
			top: height,
			width: sideWidth,
			style: {border:'', fill:'lightgray'},
			stream: true
		});

		var headBody = elem.add(lx.Box, {
			key: 'headBody',
			geom: [sideWidth, 0, bodyWidth, height],
			style: {border:'', fill:'lightgray'}
		});
		headBody.streamProportional({direction: lx.HORIZONTAL});

		var headSide = elem.add(lx.Box, {
			key: 'headSide',
			geom: [0, 0, sideWidth, height],
			style: {border:'', fill:'lightgray'}
		});
		headSide.streamProportional({direction: lx.HORIZONTAL});

		elem.on('scroll', function() {
			var pos = this.scrollPos();
			this->headBody.top(pos.y + 'px');
			this->side.left(pos.x + 'px');
			this->headSide
				.top(pos.y + 'px')
				.left(pos.x + 'px');
		});
	}

	/**
	 * В зависимости от типов полей модели вычисляются ширины и подходящие виджеты для каждого поля, с учетом смещаемости
	 * */
	__defineFieldConfigs() {
		//todo - все для пикселей работает, для смешанных нет. Надо переделывать
		var columnWidth = this.columnWidth,
			intColumnWidth = this.integerColumnWidth,
			boolColumnWidth = this.booleanColumnWidth,
			w = lx.Geom.splitGeomValue(columnWidth),
			wInt = lx.Geom.splitGeomValue(intColumnWidth),
			wBool = lx.Geom.splitGeomValue(boolColumnWidth),
			width = [0, 0],
			lock = this.lock,
			widget,
			schema = this.modelClass.getFieldTypes(),
			result = {
				sideCols: 0,
				bodyCols: 0,
				sideFields: {},
				bodyFields: {}
			};
		for (var name in schema) {
			if (this.hide.includes(name)) continue;

			var side = +lock.includes(name);
			switch (schema[name]) {
				case 'pk'     : width[side] += wInt[0];  widget = lx.Box;      break;
				case 'bool': width[side] += wBool[0]; widget = lx.Checkbox; break;
				case 'int': width[side] += wInt[0];  widget = lx.Input;    break;
				default: width[side] += w[0]; widget = lx.Input;
			}
			if (side) {
				result.sideFields[name] = [widget, {width: 1}];
				result.sideCols++;
			} else {
				result.bodyFields[name] = [widget, {width: 1}];
				result.bodyCols++;
			}
		}

		this.sideColumns.forEach(column=>{
			let cw = lx.Geom.splitGeomValue( column.width )[0];
			width[1] += cw;
			result.sideCols++;
		});
		this.bodyColumns.forEach(column=>{
			let cw = lx.Geom.splitGeomValue( column.width )[0];
			width[0] += cw;
			result.bodyCols++;
		});

		result.sideWidth = width[1] + w[1];
		result.bodyWidth = width[0] + w[1];
		return result;
	}

	/**
	 * Раскидываем добавочные колонки по положению
	 * */
	__defineAddedColumns() {
		var result = {
			preSideFields:[],
			postSideFields:[],
			preBodyFields:[],
			postBodyFields:[]
		};

		this.sideColumns.forEach(column=>{
			if (column.position == lx.RIGHT) result.postSideFields.push(column);
			else result.preSideFields.push(column);
		});
		this.bodyColumns.forEach(column=>{
			if (column.position == lx.RIGHT) result.postBodyFields.push(column);
			else result.preBodyFields.push(column);
		});
		return result;
	}
}
