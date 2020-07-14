#lx:private;

/**
 * Реализованные варианты связывания:
 * - простое: единичное поле объекта <-> единичный виджет
 * - простое на форме: поля одного объекта <-> виджет с потомками, представляющими поля объекта
 * - агрегационное: как предыдущий, но связанный с коллекцией и разрешением редактирования полей, одинаковых у всех объектов в коллекции
 * - матричное: коллекция объектов <-> виджет-матрица, содержащий одинаковых потомков, каждый их которых визуализирует один объект
 * */
class BindManager {
	get BIND_TYPE_FULL  (){return 1;}
	get BIND_TYPE_WRITE (){return 2;}
	get BIND_TYPE_READ  (){return 3;}

	/**
	 * Простая связь модели с одним виджетом.
	 * Виджет может быть формой с полями, имеющими значения `_field`, соответствующие полям модели,
	 * либо непосредственно таким полем
	 * */
	bind(obj, widget, type=lx.Binder.BIND_TYPE_FULL) {
		return __bind(obj, widget, type);
	}

	/**
	 * Отвязать от модели все привязанные виджеты по всем полям (или по переданному виджету)
	 * */
	unbind(obj, widget=null) {
		return __unbind(obj, widget);
	}

	/**
	 * Объект сигнализирует активно, что он обновился - все виджеты обновляются
	 * */
	refresh(obj, fieldName = null) {
		return __refresh(obj, fieldName);
	}

	makeWidgetMatrix(obj, info) {
		return __makeWidgetMatrix(obj, info);
	}

	unbindMatrix(widget) {
		return __unbindMatrix(widget);
	}

	bindMatrix(c, widget, type=lx.Binder.BIND_TYPE_FULL) {
		return __bindMatrix(c, widget, type);
	}

	bindAgregation(c, widget, type=lx.Binder.BIND_TYPE_FULL) {
		return __bindAgregation(c, widget, type);
	}

	getBind(id) {
		return __getBind(id);
	}
}
lx.Binder = new BindManager();


// Поля для хранения связей
let __binds = [],
	__matrixBinds = [],
	__bindCounter = 0;


/******************************************************************************************************************************
 * IMPLEMENTATION
 *****************************************************************************************************************************/

/**
 * Простая связь модели с одним виджетом.
 * Виджет может быть формой с полями, имеющими значения `_field`, соответствующие полям модели,
 * либо непосредственно таким полем
 * */
function __bind(obj, widget, type=lx.Binder.BIND_TYPE_FULL) {
	if (!obj.lxHasMethod('getSetterEvents')) return;
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
			__bindProcess(obj, _field, readWidgets);
			__action(obj, _field, obj[_field]);
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
}

// Отвязать от модели все привязанные виджеты по всем полям (в рамках определенного виджета, если передан)
function __unbind(obj, widget=null) {
	if (!obj.lxBindId) return;
	var bb = __getBind(obj.lxBindId);

	for (let name in bb) bb[name].eachRevert((a)=> {
		if (!widget || (a === widget || a.hasAncestor(widget))) {
			delete a.lxBindId;
			__valueToWidgetWithoutBind(a, '');
			__binds[obj.lxBindId][name].remove(a);
			if (__binds[obj.lxBindId][name].lxEmpty) delete __binds[obj.lxBindId][name];
			// a.off('blur');
			a.off('change');
		}
	});

	if (__binds[obj.lxBindId].lxEmpty) {
		delete __binds[obj.lxBindId];
		delete obj.lxBindId;
	}
}

/**
 * Объект сигнализирует активно, что он обновился - все виджеты обновляются
 * */
function __refresh(obj, fieldName = null) {
	if (fieldName === null) {
		if (!obj.lxHasMethod('getSetterEvents')) return;
		var setterEvents = obj.getSetterEvents();
		if (!setterEvents) return;
		var fields = setterEvents.fields;
		for (let i=0, l=fields.len; i<l; i++) {
			let field = fields[i];
			__action(obj, field, obj[field]);
		}
	} else {
		__action(obj, fieldName, obj[fieldName]);
	}
}

function __makeWidgetMatrix(obj, info) {
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
}

function __bindMatrix(c, widget, type=lx.Binder.BIND_TYPE_FULL) {
	if (!(c instanceof lx.Collection)) return;

	if (c._lxMatrixBindId === undefined) c._lxMatrixBindId = __genBindId();
	if (!(c._lxMatrixBindId in __matrixBinds))
		__matrixBinds[c._lxMatrixBindId] = {collection: c, type, widgets:[widget]};
	else
		__matrixBinds[c._lxMatrixBindId].widgets.push(widget);
	widget._lxMatrixBindId = c._lxMatrixBindId;

	widget.stopPositioning();
	c.each((a)=>__matrixNewBox(widget, a, type));
	widget.startPositioning();

	c.addBehavior(lx.MethodListenerBehavior);
	c.afterMethod('add',       __matrixHandlerOnAdd   );
	c.afterMethod('insert',    __matrixHandlerOnInsert);
	c.beforeMethod('removeAt', __matrixHandlerOnRemove);
	c.beforeMethod('clear',    __matrixHandlerOnClear );
	c.afterMethod('set',       __matrixHandlerOnSet   );
}

function __unbindMatrix(widget) {
	if (widget._lxMatrixBindId === undefined) return;

	var bind = __matrixBinds[widget._lxMatrixBindId],
		c = bind.collection;
	c.first();
	let i = 0;
	while (c.current()) {
		__unbind(c.current(), widget.getAll('r').at(i++));
		c.next();
	}

	delete widget._lxMatrixBindId;
	bind.widgets.remove(widget);
	if (bind.widgets.lxEmpty) {
		delete __matrixBinds[c._lxMatrixBindId];
		delete c._lxMatrixBindId;
	}
}

function __bindAgregation(c, widget, type=lx.Binder.BIND_TYPE_FULL) {
	var first = c.first();

	c.each((a)=> a.lxBindC = c);

	// блокировка в виджете отличающихся полей
	function disableDifferent() {
		var first = c.first();
		if (!first) return;
		var diff = __collectionDifferent(c);
		var fields = first.getSetterEvents().fields;
		for (var i=0; i<fields.len; i++) {
			var _field = fields[i],
				elem = widget.getChildren({hasProperties:{_field}, all:true}).at(0);
			if (elem) elem.disabled(_field in diff);
		}
	}

	// привязка первого элемента коллекции к виджету
	//todo практически копирует __bind()
	function bindFirst(obj) {
		if (!obj.lxHasMethod('getSetterEvents')) return;
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
				__bindProcess(obj, _field, readWidgets);
				__action(obj, _field, obj[_field]);
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
		let i = 0;
		while(c.current()) {
			__unbind(c.current(), widget.getAll('r').at(i++));
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
}

// Получить связь по ее id
function __getBind(id) {
	return __binds[id];
}


/******************************************************************************************************************************
 * INNER
 *****************************************************************************************************************************/

// Id связи для модели генерируется один, виджетов с этим id может быть связано много
// структура правила связавыния: model.id => Binder.binds[id] => bind=fields[] => field=widgets[]
// т.о. связь это массив, ключи которого - имена полей, а значения - массивы привязанных к ним виджетов
function __genBindId() {
	return 'b' + __bindCounter++;
}

function __collectionDifferent(c) {
	if (c.isEmpty) return {};
	c.cachePosition();
	var first = c.first(),
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
function __collectionAction(obj, _field) {
	if (!obj.lxBindC.lxBindWidget) return;
	var diff = __collectionDifferent(obj.lxBindC);
	obj.lxBindC.lxBindWidget.getChildren({hasProperties:{_field}, all:true}).at(0).disabled(_field in diff);
}

// Метод актуализации виджетов, связанных с полем `name` модели `obj`
function __action(obj, name, newVal) {
	if (obj.lxBindC) __collectionAction(obj, name);

	if (!obj.lxBindId) return;
	if (!(obj.lxBindId in __binds)) {
		delete obj.lxBindId;
		return;
	}
	let arr = __getBind(obj.lxBindId)[name];
	if (!arr || !arr.isArray) return;
	arr.each((a)=> __valueToWidget(a, newVal));
}

// Без обновления модели
function __valueToWidgetWithoutBind(widget, value) {
	if (widget.lxHasMethod('innerValue'))
		widget.innerValue(value);
	else if (widget.lxHasMethod('value'))
		widget.value(value);
	else if (widget.lxHasMethod('text'))
		widget.text(value);
}

// Метод непосредственного помещения значения в виджет
function __valueToWidget(widget, value) {
	if (widget.lxHasMethod('value'))
		widget.value(value);
	else if (widget.lxHasMethod('text'))
		widget.text(value);
}

// Отвязать виджет от поля модели, если в этой связи не осталось виджетов, связь удаляется.
// Id связи из модели удалится при изменении ее поля, когда при попытке актуализации не будет найдена связь.
function __unbindWidget(widget) {
	if (!widget.lxBindId) return;
	__binds[widget.lxBindId][widget._field].remove(widget);
	if (__binds[widget.lxBindId][widget._field].lxEmpty)
		delete __binds[widget.lxBindId][widget._field];
	if (__binds[widget.lxBindId].lxEmpty)
		delete __binds[widget.lxBindId];
	delete widget.lxBindId;
	// widget.off('blur');
	widget.off('change');
}

// Привязывает виджет по определенному id, если по такому id связи нет, она будет создана
function bindWidget(widget, id) {
	__unbindWidget(widget);
	widget.lxBindId = id;
	if (!(id in __binds))
		__binds[id] = [];
	if (!(widget._field in __binds[id]))
		__binds[id][widget._field] = [];
	__binds[id][widget._field].push(widget);
}

// Связывает поле `name` модели `obj` с переданными виджетами. Связь создается автоматически
function __bindProcess(obj, name, widgets) {
	if (!obj.lxBindId)
		obj.lxBindId = __genBindId();
	if (!(obj.lxBindId in __binds))
		__binds[obj.lxBindId] = [];
	if (!(name in __binds[obj.lxBindId]))
		__binds[obj.lxBindId][name] = [];
	widgets.each((a)=> bindWidget(a, obj.lxBindId));
}

function __getMatrixCollection(widget) {
	return __matrixBinds[widget._lxMatrixBindId].collection;
}

function __prepareMatrixNewBoxConfig(w) {
	let rowConfig = w.lxcwb_config ? w.lxcwb_config.lxClone() : {}
	rowConfig.key = 'r';
	rowConfig.parent = w;
	return rowConfig;
}

function __matrixNewBox(w, obj, type, rowConfig = null) {
	rowConfig = rowConfig || __prepareMatrixNewBoxConfig(w);
	let rowClass = w.lxcwb_widget || lx.Box;
	let r = new rowClass(rowConfig);
	r.begin();
	w.lxcwb_itemRender(r, obj);
	r.end();
	__bind(obj, r, type);
	if (w.lxcwb_afterBind) w.lxcwb_afterBind(r, obj);
	r.matrixItems = function() {return __getMatrixCollection(this.parent);};
	r.matrixIndex = function() {return this.index || 0;};
	r.matrixModel = function() {return __getMatrixCollection(this.parent).at(this.index || 0);};
}

function __matrixInsertNewBox(w, obj, index, type) {
	if (index > w.childrenCount()) index = w.childrenCount();
	if (index == w.childrenCount()) {
		__matrixNewBox(w, obj, type);
		return;
	}

	let rowConfig = __prepareMatrixNewBoxConfig(w);
	rowConfig.before = w.child(index);
	__matrixNewBox(w, obj, type, rowConfig);
}

function __matrixHandlerOnAdd(obj = null) {
	if (this._lxMatrixBindId === undefined) return;
	var widgets = __matrixBinds[this._lxMatrixBindId].widgets;
	widgets.each(w=>__matrixNewBox(w, this.last(), __matrixBinds[this._lxMatrixBindId].type));
}

function __matrixHandlerOnInsert(i, obj = null) {
	if (this._lxMatrixBindId === undefined) return;
	var widgets = __matrixBinds[this._lxMatrixBindId].widgets;
	widgets.each(w=>__matrixInsertNewBox(w, this.at(i), i, __matrixBinds[this._lxMatrixBindId].type));
}

function __matrixHandlerOnRemove(i) {
	if (this._lxMatrixBindId === undefined) return;
	var widgets = __matrixBinds[this._lxMatrixBindId].widgets;
	widgets.eachRevert((w)=>{
		__unbind(this.at(i), w.getAll('r').at(i));
		w.del('r', i);
	});
}

function __matrixHandlerOnClear() {
	if (this._lxMatrixBindId === undefined) return;

	var widgets = __matrixBinds[this._lxMatrixBindId].widgets;
	widgets.each((w)=>{
		this.first();
		let i = 0;
		while (this.current()) {
			__unbind(this.current(), w.getAll('r').at(i++));
			this.next();
		}
		w.del('r');
	});
}

function __matrixHandlerOnSet(i, obj) {
	if (this._lxMatrixBindId === undefined) return;
	var widgets = __matrixBinds[this._lxMatrixBindId].widgets,
		type = __matrixBinds[this._lxMatrixBindId].type;
	widgets.eachRevert((w)=>{
		__bind(this.at(i), w.getAll('r').at(i), type);
	});
}
