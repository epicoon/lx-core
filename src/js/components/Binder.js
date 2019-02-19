#lx:private;

/**
 * Реализованные варианты связывания:
 * - простое: единичное поле объекта <-> единичный виджет
 * - простое на форме: поля одного объекта <-> виджет с потомками, представляющими поля объекта
 * - агрегационное: как предыдущий, но связанный с коллекцией и разрешением редактирования полей, одинаковых у всех объектов в коллекции
 * - матричное: коллекция объектов <-> виджет-матрица, содержащий одинаковых потомков, каждый их которых визуализирует один объект
 * */

lx.Binder = {
	makeWidgetMatrix: function(obj, info) {
		let widget, config;
		if (info.itemBox) {
			if (info.itemBox.isArray) {
				widget = info.itemBox[0];
				config = info.itemBox[1];
			} else widget = info.itemBox;
		}
		if (widget) obj.lxcwb_widget = widget;
		if (config) obj.lxcwb_config = config;
		if (info.itemRender) obj.lxcwb_itemRender = info.itemRender;
		if (info.afterBind) obj.lxcwb_afterBind = info.afterBind;
	},

	/**
	 * Объект сигнализирует активно, что он обновился - все виджеты обновляются
	 * */
	refresh: function(obj, fieldName = null) {
		if (fieldName === null) {
			var setterEvents = obj.getSetterEvents();
			if (!setterEvents) return;
			var fields = setterEvents.fields;
			for (let i=0, l=fields.len; i<l; i++) {
				let field = fields[i];
				action(obj, field, obj[field]);
			}
		} else {
			action(obj, fieldName, obj[fieldName]);
		}
	},

	/**
	 * Отвязать от модели все привязанные виджеты по всем полям
	 * */
	unbind: function(obj) {
		unbindObject(obj);
	},

	/**
	 * Простая связь модели с одним виджетом.
	 * Виджет может быть формой с полями, имеющими значения `_field`, соответствующие полям модели,
	 * либо непосредственно таким полем
	 * */
	bind: function(obj, widget, type=lx.Binder.BIND_TYPE_FULL) {
		var setterEvents = obj.getSetterEvents();
		if (!setterEvents) return;

		var fields = setterEvents.fields;
		for (let i=0, l=fields.len; i<l; i++) {
			let _field = fields[i],
				c = widget.getChildren
					? widget.getChildren({hasProperties:{_field}, all:true})
					: new lx.Collection();

			if (widget._field == _field) c.add(widget);
			if (c.isEmpty) continue;


			var readWidgets = new lx.Collection(),
				writeWidgets = new lx.Collection();
			c.each((widget)=>{
				if (widget._bindType === undefined) widget._bindType = type;
				if (widget._bindType == lx.Binder.BIND_TYPE_READ || widget._bindType == lx.Binder.BIND_TYPE_FULL) readWidgets.add(widget);
				if (widget._bindType == lx.Binder.BIND_TYPE_WRITE || widget._bindType == lx.Binder.BIND_TYPE_FULL) writeWidgets.add(widget);
			});

			//todo - сделать инкапсулированным методом и в widget.off('blur'); отключать именно его
			function actualize(a) {
				obj[a._field] = a.lxHasMethod('value')
					? a.value()
					: a.text();
			};
			if (!readWidgets.isEmpty) {
				bind(obj, _field, readWidgets);
				action(obj, _field, obj[_field]);
			}
			writeWidgets.each((a)=>{
				/*
				todo
				по коду закоменчены - потому что надо иметь стандарт - если виджет используется для связывания, то он должен иметь
				событие change, и только с его помощью будем отслеживать изменения в виджете.
				Так же бы в топку метод .text() у виджетов - тоже надо чтобы был стандарт - только метод .value()
				*/
				// a.on('blur', function() { actualize(this); });
				a.on('change', function() { actualize(this); });
			});
		}
	},

	bindMatrix: function(c, widget, type=lx.Binder.BIND_TYPE_FULL) {
		if (!(c instanceof lx.Collection)) return;

		//todo c - коллекция существует чисто в замыкании - утечка памяти? надо рефакторить
		widget.matrixItems = c;

		let t = this,
			rowClass = widget.lxcwb_widget || lx.Box,
			rowConfig = widget.lxcwb_config ? widget.lxcwb_config.lxCopy() : {},
			itemRender = widget.lxcwb_itemRender,
			afterBind = widget.lxcwb_afterBind;

		rowConfig.parent = widget;
		rowConfig.key = 'r';

		function newRow(obj = null) {
			if (obj === null) obj = this.last();
			let r = new rowClass(rowConfig);
			r.begin();
			itemRender(r, obj);
			r.end();
			t.bind(obj, r);
			if (afterBind) afterBind(r, obj);
			r.matrixItems = function() {return this.parent.matrixItems;};
			r.matrixIndex = function() {return this.index || 0;};
			r.matrixModel = function() {return this.parent.matrixItems.at(this.index || 0);};
		};

		function delObject(i) {
			unbindObject(c.at(i));
			widget.del('r', i);
		};

		function unbindAll() {
			c.first();
			while (c.current()) {
				unbindObject(c.current());
				c.next();
			}
			widget.del('r');
			//delete widget.matrixCollection;
		};

		c.each(function(a){newRow.call(this, a);});
		c.addBehavior(lx.MethodListenerBehavior);
		c.afterMethod('add', function(){newRow.call(this);});
		c.beforeMethod('removeAt', (i)=> delObject(i));
		c.beforeMethod('clear', unbindAll);
		c.afterMethod('set', (i, obj)=> lx.Binder.bind(c.at(i), widget.get('r')[i], type) );
	},

	bindAgregation: function(c, widget, type=lx.Binder.BIND_TYPE_FULL) {
		var first = c.first();

		c.each((a)=> a.lxBindC = c);

		// блокировка в виджете отличающихся полей
		function disableDifferent() {
			var first = c.first();
			if (!first) return;
			var diff = collectionDifferent(c);
			var fields = first.getSetterEvents().fields;
			// var fields = first.constructor.__setterEvents.fields;
			for (var i=0; i<fields.len; i++) {
				var _field = fields[i],
					elem = widget.getChildren({hasProperties:{_field}, all:true}).at(0);
				if (elem) elem.disabled(_field in diff);
			}
		}

		// привязка первого элемента коллекции к виджету
		//todo практически копирует lx.bind()
		function bindFirst(obj) {
			var setterEvents = obj.getSetterEvents();
			if (!setterEvents) return;

			var fields = setterEvents.fields;
			for (let i=0, l=fields.len; i<l; i++) {
				let _field = fields[i],
					cw = widget.getChildren
						? widget.getChildren({hasProperties:{_field}, all:true})
						: new lx.Collection();

				if (widget._field == _field) cw.add(widget);
				if (cw.isEmpty) continue;

				var readWidgets = new lx.Collection(),
					writeWidgets = new lx.Collection();
				cw.each((widget)=>{
					if (widget._bindType === undefined) widget._bindType = type;
					if (widget._bindType == lx.Binder.BIND_TYPE_READ || widget._bindType == lx.Binder.BIND_TYPE_FULL) readWidgets.add(widget);
					if (widget._bindType == lx.Binder.BIND_TYPE_WRITE || widget._bindType == lx.Binder.BIND_TYPE_FULL) writeWidgets.add(widget);
				});
				function actualize(a) {
					let val = a.lxHasMethod('value')
						? a.value()
						: a.text();
					c.each((el)=> el[_field] = val);
				}
				if (!readWidgets.isEmpty) {
					bind(obj, _field, readWidgets);
					action(obj, _field, obj[_field]);
				}
				writeWidgets.each((a)=>{
					// a.on('blur', function() { actualize(this); });
					a.on('change', function() { actualize(this); });
				});
			}
		}

		// проверка при добавлении/изменении элемента коллекции
		function checkNewObj(obj) {
			if (c.isEmpty) bindFirst(obj);
			else if (c.first().constructor !== obj.constructor) return false;
			obj.lxBindC = c;
		}

		function unbindAll() {
			if (c.isEmpty) return;
			c.first();
			while(c.current()) {
				unbindObject(c.current());
				c.next();
			}
		};

		// обработчики событий-методов
		c.addBehavior(lx.MethodListenerBehavior);
		c.beforeMethod('removeAt', (i)=> delete c.at(i).lxBindC);
		c.afterMethod('removeAt', (i)=> {
			if (i == 0 && !c.isEmpty) bindFirst(c.first());
			disableDifferent();
		});
		c.beforeMethod('add', (obj)=>checkNewObj(obj));
		c.afterMethod('add', disableDifferent);
		c.beforeMethod('set', (i, obj)=>checkNewObj(obj));
		c.afterMethod('set', disableDifferent);
		c.beforeMethod('clear', unbindAll);

		c.lxBindWidget = widget;
		if (first) {
			bindFirst(first);
			disableDifferent();
		}
	},


	getBind: function(id) {
		return getBind(id);
	}
};

// Инкапсулированные поля для хранения связей
var binds = [],
	bindCounter = 0;

// Id связи для модели генерируется один, виджетов с этим id может быть связано много
// структура правила связавыния: model.id => Binder.binds[id] => bind=fields[] => field=widgets[]
// т.о. связь это массив, ключи которого - имена полей, а значения - массивы привязанных к ним виджетов
function genBindId() {
	return 'b' + bindCounter++;
}

// Получить связь по ее id
function getBind(id) {
	return binds[id];
}

function collectionDifferent(c) {
	if (c.isEmpty) return {};
	c.cachePosition();
	var first = c.first(),
		// fields = first.constructor.__setterEvents.fields,
		fields = first.getSetterEvents().fields,
		boof = {};
	while (obj = c.next()) {
		for (var i=0; i<fields.len; i++) {
			var f = fields[i];
			if ( obj[f] != first[f] ) boof[f] = 1;
		}
	}
	c.loadPosition();
	return boof;
}
function collectionAction(obj, _field) {
	if (!obj.lxBindC.lxBindWidget) return;
	var diff = collectionDifferent(obj.lxBindC);
	obj.lxBindC.lxBindWidget.getChildren({hasProperties:{_field}, all:true}).at(0).disabled(_field in diff);
}

// Метод актуализации виджетов, связанных с полем `name` модели `obj`
function action(obj, name, newVal) {
	if (obj.lxBindC) collectionAction(obj, name);

	if (!obj.lxBindId) return;
	if (!(obj.lxBindId in binds)) {
		delete obj.lxBindId;
		return;
	}
	let arr = getBind(obj.lxBindId)[name];

	if (!arr) return;
	arr.each((a)=> valueToWidget(a, newVal));
}

// Отвязать от модели все привязанные виджеты по всем полям
function unbindObject(obj) {
	if (!obj.lxBindId) return;
	var bb = getBind(obj.lxBindId);
	for (let name in bb) bb[name].each((a)=> {
		delete a.lxBindId;
		valueToWidgetWithoutBind(a, '');
		// a.off('blur');
		a.off('change');
	});
	delete binds[obj.lxBindId];
	delete obj.lxBindId;
}

// Без обновления модели
function valueToWidgetWithoutBind(widget, value) {
	if (widget.lxHasMethod('innerValue'))
		widget.innerValue(value);
	else if (widget.lxHasMethod('value'))
		widget.value(value);
	else if (widget.lxHasMethod('text'))
		widget.text(value);
}

// Метод непосредственного помещения значения в виджет
function valueToWidget(widget, value) {
	if (widget.lxHasMethod('value'))
		widget.value(value);
	else if (widget.lxHasMethod('text'))
		widget.text(value);
}

// Отвязать виджет от поля модели, если в этой связи не осталось виджетов, связь удаляется.
// Id связи из модели удалится при изменении ее поля, когда при попытке актуализации не будет найдена связь.
function unbindWidget(widget) {
	if (!widget.lxBindId) return;
	binds[widget.lxBindId][widget._field].remove(widget);
	if (binds[widget.lxBindId][widget._field].lxEmpty)
		delete binds[widget.lxBindId][widget._field];
	if (binds[widget.lxBindId].lxEmpty)
		delete binds[widget.lxBindId];
	delete widget.lxBindId;
	// widget.off('blur');
	widget.off('change');
}

// Привязывает виджет по определенному id, если по такому id связи нет, она будет создана
function bindWidget(widget, id) {
	unbindWidget(widget);
	widget.lxBindId = id;
	if (!(id in binds))
		binds[id] = [];
	if (!(widget._field in binds[id]))
		binds[id][widget._field] = [];
	binds[id][widget._field].push(widget);
}

// Связывает поле `name` модели `obj` с переданными виджетами. Связь создается автоматически
function bind(obj, name, widgets) {
	if (!obj.lxBindId)
		obj.lxBindId = genBindId();
	if (!(obj.lxBindId in binds))
		binds[obj.lxBindId] = [];
	if (!(name in binds[obj.lxBindId]))
		binds[obj.lxBindId][name] = [];
	widgets.each((a)=> bindWidget(a, obj.lxBindId));
}

lx.Binder.BIND_TYPE_FULL = 1;
lx.Binder.BIND_TYPE_WRITE = 2;
lx.Binder.BIND_TYPE_READ = 3;
