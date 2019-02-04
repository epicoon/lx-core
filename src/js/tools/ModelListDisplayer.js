#widget Table;

class ModelListDisplayer #in lx {
	constructor(config) {
		this.init(config);
	}

	init(config) {
		if (config.headHeight) this.headHeight = config.headHeight;
		this.headHeight = this.headHeight || '25px';

		if (config.columnWidth) this.columnWidth = config.columnWidth;
		this.columnWidth = this.columnWidth || '150px';

		if (config.integerColumnWidth) this.integerColumnWidth = config.integerColumnWidth;
		this.integerColumnWidth = this.integerColumnWidth || '100px';

		if (config.booleanColumnWidth) this.booleanColumnWidth = config.booleanColumnWidth;
		this.booleanColumnWidth = this.booleanColumnWidth || '100px';


		if (config.lock) this.lock = config.lock;
		this.lock = this.lock || [];

		if (config.hide) this.hide = config.hide;
		this.hide = this.hide || [];

		if (config.formModifier) this.formModifier = config.formModifier;
		this.formModifier = this.formModifier || {};

		if (config.fieldsModifier) this.modifier = config.fieldsModifier;
		this.modifier = this.modifier || {};

		if (config.modelClass) this.modelClass = config.modelClass;
		this.modelClass = this.modelClass || null;
	}

	apply(config) {
		this.init(config);

		var box = config.box,
			headHeight = this.headHeight,
			columnWidth = this.columnWidth,
			intColumnWidth = this.integerColumnWidth,
			boolColumnWidth = this.booleanColumnWidth,
			w = lx.Geom.splitGeomValue(columnWidth),
			wInt = lx.Geom.splitGeomValue(intColumnWidth),
			wBool = lx.Geom.splitGeomValue(boolColumnWidth),
			width = [0, 0],
			data = config.data,
			schema = this.modelClass.getFieldTypes(),
			lock = this.lock,
			modif = this.modifier,
			formModif = this.formModifier,
			lockLen = 0,
			unlockLen = 0,
			widget,
			sideFields = {},
			bodyFields = {};
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
				sideFields[name] = [widget, {width: 1}];
				lockLen++;
			} else {
				bodyFields[name] = [widget, {width: 1}];
				unlockLen++;
			}
		}

		this.buildBox(box, headHeight, width[1] + w[1], width[0] + w[1]);

		for (var name in sideFields) box->headSide.add(lx.Box, {text: name}).align(lx.CENTER, lx.MIDDLE);
		for (var name in bodyFields) box->head.add(lx.Box, {text: name}).align(lx.CENTER, lx.MIDDLE);

		box->side.matrix({
			items: data,
			itemBox: [lx.Form, {grid: {cols: lockLen, indent: '10px'}}],
			itemRender: (form)=> {
				if (formModif) formModif(form);

				form.fields(sideFields);

				form.getChildren().each((a)=> {
					if (modif[a.key]) modif[a.key](a);
					else if (modif.default) modif.default(a);
				});
			}
		});

		box->body.matrix({
			items: data,
			itemBox: [lx.Form, {grid: {cols: unlockLen, indent: '10px'}}],
			itemRender: (form)=> {
				if (formModif) formModif(form);

				form.fields(bodyFields);

				form.getChildren().each((a)=> {
					if (modif[a.key]) modif[a.key](a);
					else if (modif.default) modif.default(a);
				});
			}
		});
	}

	buildBox(elem, height, sideWidth, bodyWidth) {
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

		var head = elem.add(lx.TableRow, {
			key: 'head',
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
			this->head.top(pos.y + 'px');
			this->side.left(pos.x + 'px');
			this->headSide
				.top(pos.y + 'px')
				.left(pos.x + 'px');
		});
	}
}
