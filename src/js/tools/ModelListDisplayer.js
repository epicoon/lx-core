#lx:widget Table;

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
		this.headHeight = [config.headHeight, this.headHeigh, '25px'].lxGetFirstDefined();
		this.columnWidth = [config.columnWidth, this.columnWidth, '150px'].lxGetFirstDefined();
		this.integerColumnWidth = [config.integerColumnWidth, this.integerColumnWidth, '100px'].lxGetFirstDefined();
		this.booleanColumnWidth = [config.booleanColumnWidth, this.booleanColumnWidth, '100px'].lxGetFirstDefined();

		this.modelClass = [config.modelClass, this.modelClass, null].lxGetFirstDefined();
		this.lock = [config.lock, this.lock, []].lxGetFirstDefined();
		this.hide = [config.hide, this.hide, []].lxGetFirstDefined();

		this.formModifier = [config.formModifier, this.formModifier, null].lxGetFirstDefined();
		this.fieldsModifier = [config.fieldsModifier, this.fieldsModifier, {}].lxGetFirstDefined();

		this.box = [config.box, this.box, null].lxGetFirstDefined();
		this.data = [config.data, this.data, null].lxGetFirstDefined();
	}

	/**
	 * Добавить колонку, не являющуюся частью схемы представляемой модели
	 * */
	addColumn(config) {
		if (!config.render || !config.render.isFunction) return;

		config.lock = [config.lock, true].lxGetFirstDefined();
		config.widget = [config.widget, lx.Box].lxGetFirstDefined();
		config.position = [config.position, lx.RIGHT].lxGetFirstDefined();
		config.width = [config.width, '100px'].lxGetFirstDefined();
		config.label = [config.label, ''].lxGetFirstDefined();

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

	/**
	 * Наполенние основных контейнеров содержимым согласно переданной коллекции моделей
	 * */
	__buildComponentContent(data, pre, fields, post, colsCount, head, body) {
		if (fields.lxEmpty) return;

		var fieldsModifier = this.fieldsModifier,
			formModifier = this.formModifier;


		pre.each((a)=>head.add(lx.Box, {text:a.label}).align(lx.CENTER, lx.MIDDLE));
		for (var name in fields) head.add(lx.Box, {text: name}).align(lx.CENTER, lx.MIDDLE);
		post.each((a)=>head.add(lx.Box, {text:a.label}).align(lx.CENTER, lx.MIDDLE));

		body.matrix({
			items: data,
			itemBox: [lx.Form, {grid: {cols: colsCount, indent: '10px'}}],
			itemRender: (form)=> {
				if (formModifier) formModifier(form);

				pre.each((a)=>{
					var w = a.widget, c = {};
					if (w.isArray) { c = w[1]; w = w[0]; }
					if (!c.width) c.width = 1;
					var widget = new w(c);
					a.render(widget);
				});
				form.fields(fields);
				post.each((a)=>{
					var w = a.widget, c = {};
					if (w.isArray) { c = w[1]; w = w[0]; }
					if (!c.width) c.width = 1;
					var widget = new w(c);
					a.render(widget);
				});

				form.getChildren().each((a)=> {
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
			stream: {sizeBehavior: lx.StreamPositioningStrategy.SIZE_BEHAVIOR_BY_CONTENT}
		});
		body.border();

		var side = elem.add(lx.Box, {
			key: 'side',
			top: height,
			width: sideWidth,
			style: {border:'', fill:'lightgray'},
			stream: {sizeBehavior: lx.StreamPositioningStrategy.SIZE_BEHAVIOR_BY_CONTENT}
		});

		var headBody = elem.add(lx.TableRow, {
			key: 'headBody',
			left: sideWidth,
			width: bodyWidth,
			height: height,
			style: {border:'', fill:'lightgray'}
		});

		var headSide = elem.add(lx.TableRow, {
			key: 'headSide',
			width: sideWidth,
			height: height,
			style: {border:'', fill:'lightgray'}
		});

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
			if (this.hide.contain(name)) continue;

			var side = +lock.contain(name);
			switch (schema[name]) {
				case 'pk'     : width[side] += wInt[0];  widget = lx.Box;      break;
				case 'boolean': width[side] += wBool[0]; widget = lx.Checkbox; break;
				case 'integer': width[side] += wInt[0];  widget = lx.Input;    break;
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

		this.sideColumns.each((column)=>{
			let cw = lx.Geom.splitGeomValue( column.width )[0];
			width[1] += cw;
			result.sideCols++;
		});
		this.bodyColumns.each((column)=>{
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

		this.sideColumns.each((column)=>{
			if (column.position == lx.RIGHT) result.postSideFields.push(column);
			else result.preSideFields.push(column);
		});
		this.bodyColumns.each((column)=>{
			if (column.position == lx.RIGHT) result.postBodyFields.push(column);
			else result.preBodyFields.push(column);
		});
		return result;
	}
}
