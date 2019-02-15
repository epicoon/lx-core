#lx:private;

#lx:use lx.Rect as Rect;
#lx:use lx.Box as Box;
#lx:use lx.Input as Input;

/* 
 * Special events:
 * - leafLoad(event, node)
 * - leafOpen(event, leaf, node)
 * - leafClose(event, leaf, node)
 * - beforeAdd(event, info)  // info == { parentNode, button, text, data: null, comment: '' } - в info можно записать данные узла по умолчанию, text - при варианте add==2, здесь окажется предварительно введенный текст
 * - afterAdd(event, newNode, button)
 * - beforeDel(event, node)
 * - afterDel(event)
 * */
class TreeBox extends Box #lx:namespace lx {
	/* config = {
	 *	// стандартные для Box,
	 *	
	 *	data: lx.Tree
	 *	indent: 10,
	 *	step: 5,
	 *	leafHeight: 18,
	 *	labelWidth: 250,
	 *	add: 0|1|2,  // 0 - без возможности добавления, 1 - простое добавление с автоключом, 2 - добавление, требующее введения ключа
	 *	leaf: function  // описывает поведение листа при его создании - как и что в нем дополнительно отображать
	 * }
	 * */
	build(config) {
		super.build(config);

		this.style('overflow', 'auto');

		//todo - сейчас только в пикселях, переделать несложно + надо значения вынести в константы класса
		this.indent = config.indent || 10;
		this.step   = config.step   || 5;
		this.leafHeight = config.leafHeight || 18;
		this.labelWidth = config.labelWidth || 250;
		this.addMode = config.add || self::FORBIDDEN_ADDING;
		this.rootAdding = config.rootAdding === undefined
			? !!this.addMode
			: config.rootAdding;

		if (config.leaf) this.leafConstructor = config.leaf;
		this.data = config.data || new lx.Tree();

		var w = this.step * 2 + this.leafHeight + this.labelWidth;
		var el = new Box({
			parent: this,
			key: 'work',
			width: w + 'px'
		});

		new Rect({
			parent: this,
			key: 'move',
			left: w + 'px',
			width: this.step + 'px',
			style: {cursor: 'ew-resize'}
		});
	}

	postBuild(config) {
		super.postBuild(config);

		var work = this.children.work,
			move = this.children.move;
		work.stream({
			padding: this.indent+'px',
			step: this.step+'px',
			paddingRight: '0px'
		});
		work.style('overflow', 'visible');
		move.move({ yMove: false });
		move.on('move', function() { work.width(this.left('px') + 'px'); });
		if (this.data) this.prepareRoot();
	}

	postUnpack(config) {
		super.postUnpack(config);

		if (config.data) {
			this.data = new lx.Tree();
			this.data.parseJSON(config.data);
		}
		if (config.leaf) this.leafConstructor = this.unpackFunction(config.leaf);
		this.children.work.clear();
		this.prepareRoot();
	}

	leafs() {
		if (!this.children.work.children.leaf) return new lx.Collection();
		return new lx.Collection(this.children.work.children.leaf);
	}

	leaf(i) {
		if (!(i in this.children.work.children.leaf)) return null;
		return this.children.work.children.leaf[i];
	}

	leafByNode(node) {
		var match = null;
		this.leafs().each(function(a) {
			if (a.node === node) {
				match = a;
				this.stop();
			}
		});
		return match;
	}

	setLeaf(func) {
		this.leafConstructor = func;
	}

	setData(data) {
		this.data = data;
		this.renew();
		return this;
	}

	/**
	 * Чтобы открытые ветви дерева не забывались при перезагрузке страницы
	 * */
	setStateMemoryKey(key) {
		// На открытие ветки
		this.on('leafOpen', function(e, leaf) {
			var opened = this.getOpenedInfo();
			opened.push(leaf.index);
			lx.Cookie.set(key, opened.join(','));
		});

		// На закрытие ветки
		this.on('leafClose', function(e, leaf) {
			var opened = this.getOpenedInfo();
			lx.Cookie.set(key, opened.join(','));
		});

		// Проверить состояние куки прямо сейчас
		let treeState = lx.Cookie.get(key);
		if (treeState) {
			this.useOpenedInfo(treeState.split(','));
		}
	}

	renew() {
		if (!this.isDisplay()) return;

		var scrollTop = this.DOMelem.scrollTop;
		var opened = [];
		this.leafs().each((a)=> {
			if (a.children.open.opened)
				opened.push(a.node);
		});


		this.children.work.clear();
		this.prepareRoot();
		for (var i in opened) {
			// todo - неэффективно
			var leaf = this.leafByNode(opened[i]);
			if (!leaf) continue;
			this.openBranch(leaf);
		}
		this.DOMelem.scrollTop = scrollTop;
		return this;
	}

	/**
	 * Выдает массив индексов открытых листьев
	 * */
	getOpenedInfo() {
		var opened = [];
		this.leafs().each((a, i)=> {
			if (a.children.open.opened)
				opened.push(i);
		});
		return opened;
	}

	/**
	 * Раскрывает листья согласно массиву, сформированному в .getOpenedInfo()
	 * */
	useOpenedInfo(info) {
		for (var i=0, l=info.len; i<l; i++) {
			this.openBranch(this.leaf(info[i]));
		}
	}

	prepareRoot() {
		this.createLeafs(this.data);
		var work = this.children.work;
		//todo с условием стало напутано - подменю не обязано быть логически связанным с кнопкой добавления. Подумать нужно ли оно вообще
		if (this.addMode && this.rootAdding && !work.contain('submenu')) {
			var menu = new Box({parent: work, key: 'submenu', height: this.leafHeight+'px'});
			new Rect({
				key: 'add',
				parent: menu,
				width: this.leafHeight+'px',
				css: ['lx-TW-Button', 'lx-TW-Button-add'],
				click: self::addNode
			});
		}
	}

	createLeafs(data, after) {
		if (!data) return;
		var config = {
			parent: this.children.work,
			key: 'leaf',
			height: this.leafHeight + 'px',
			style: {overflow: 'visible'}
		};
		if (after) config.after = after;

		return TreeLeaf.construct(data.keys.len, config, {
			preBuild:(config,i)=> {
				config.node = data.getNth(i);
				return config;
			}
		});
	}


	static toggleOpened(event) {
		var tw = this.ancestor({is: lx.TreeBox}),
			l = this.parent;
		if (this.opened) tw.closeBranch(l, event);
		else tw.openBranch(l, event);
	}

	openBranch(leaf, event) {
		var node = leaf.node;

		if ( node.fill !== undefined && node.fill ) {
			var _t = this;

		// 	// выпилить нахер отсюда непосредственную связь с бд, пусть реализуется как частный случай запроса на сервер
		// 	if (this.db) {
		// 		var key = id.split(lx.treeSeparator).pop();
		// 		JsHttpRequest.query('lx/php/lx/TreeWidgetBackend.php', {type: 'load', id: node.key, path: node.path(), table: _t.db},
		// 		function(res) {
		// 			node.parseJSON( res );
		// 			delete node.fill;
		// 			_t.openBranch(leaf, event);
		// 		});
		// 		return;
		// 	} else {
		// 		delete node.fill;
		// 		this.trigger('leafLoad', event, leaf /*node*/);
		// 		/*
		// 		todo
		// 		вот отличное место для того, чтобы замутить синхронайзер и оттестить
		// 		*/
		// 		// _t.openBranch(id, event);  // предполагается какая-то загрузка с сервера - вызывать надо в случае загрузки
		// 		return;
		// 	}
		} else this.trigger('leafOpen', event, leaf);

		if (!node.keys.len) return;

		var leafs = this.createLeafs(node, leaf);
		leafs.each((a)=> {
			var shift = this.step + (this.step + this.leafHeight) * (node.deep() + 1);
			a.children.open.left(shift + 'px');
			a.children.label.left(shift + this.step + this.leafHeight + 'px');
		});

		var b = leaf.children.open;
		b.opened = true;
		b.removeClass('lx-TW-Button-closed');
		b.addClass('lx-TW-Button-opened');
	}

	closeBranch(leaf, event) {
		var i = leaf.index,
			deep = leaf.node.deep(),
			next = this.leaf(++i);
		while (next && next.node.deep() > deep) next = this.leaf(++i);

		var count = next ? next.index - leaf.index - 1 : Infinity;

		leaf.parent.del('leaf', leaf.index + 1, count);
		var b = leaf.children.open;
		b.opened = false;
		b.removeClass('lx-TW-Button-opened');
		b.addClass('lx-TW-Button-closed');
		this.trigger('leafClose', event, leaf);
	}


	static addNode() {
		var tw = this.ancestor({is: lx.TreeBox}),
			node = (this.key == 'add') ? tw.data : this.parent.node;
		tw.addMode == lx.TreeBox.ALLOWED_ADDING
			? tw.addProcess(node)
			: tw.createInput(this);
	}

	/*
	 * Непосредственно создание нового узла
	 *
	 * text получает только когда в конфигурациях add==2 - принудительное введение текста при добавлении узла,
	 * text - этот введенный текст, далее перекидывается в boof и может быть обработан событием beforeAdd,
	 * без явного кода как этот текст использовать, он никуда не пойдет
	 * */
	addProcess(parentNode, text) {
		var boof = {
			node: {},  // поля, которые будут добавлены новому узлу
			parentNode,
			text
		};
		if ( this.trigger('beforeAdd', event, boof) === false ) return;

		// todo ВЫПИЛ!!!
		// if (this.db) {
		// 	// ключи == id в базе данных
		// 	var t = this;
		// 	JsHttpRequest.query('lx/php/lx/TreeWidgetBackend.php', {
		// 		type: 'add',
		// 		id: parentNode.key,
		// 		path: parentNode.path(),
		// 		data: boof.node,
		// 		// data: boof.data,
		// 		// comment: boof.comment,
		// 		table: this.db
		// 	}, function(res) {
		// 		if (parentNode.fill === undefined) {
		// 			var newBr = parentNode.add(res);
		// 			for (var f in boof.node)
		// 				if (!(f in newBr)) newBr[f] = boof.node[f];
		// 			// newBr.data = boof.data;
		// 			// newBr.comment = boof.comment;
		// 			t.trigger('afterAdd', event, newBr);
		// 		} else if (parentNode.fill === 0) parentNode.fill = 1;
		// 		t.renew();
		// 	}, true);

		// 	return;
		// }

		if (parentNode.root) this.leafByNode(parentNode).children.open.opened = true;

		var key = boof.node.key || parentNode.genKey(),
			newBr = parentNode.add(key);

		// Поле data как раз для прикрепленных данных, поэтому может быть переопределено в 'beforeAdd',
		// остальные поля, которые уже есть у узла, надо защитить от перезаписи
		for (var f in boof.node)
			if (f == 'data' || !(f in newBr)) newBr[f] = boof.node[f];
		this.trigger('afterAdd', event, newBr);
		this.renew();
	}

	createInput(but) {
		this.deleteInput();

		var inp = new Input({
			parent: this,
			key: 'inp',
			geom: [
				this.step * 2 + this.leafHeight + 'px',
				but.parent.top('px') + 'px',
				// (but.key=='add' ? but.top('px') : but.parent.top('px')) + 'px',
				this.labelWidth+'px',
				this.leafHeight+'px'
			]
		}).focus();

		but.off('click');
		but.click(self::applyAddNode);
		this.click(self::watchForInput);
		inp.but = but;
	}

	deleteInput() {
		if (!this.contain('inp')) return;

		var inp = this.children.inp;
		inp.but.off('click');
		inp.but.click(self::addNode);

		this.del(inp);
		this.off('click', self::watchForInput);
	}

	/*
	 * Подтверждение создания узла при дополнительном вводе текста
	 * */
	static applyAddNode(event) {
		var tw = this.ancestor({is: lx.TreeBox}),
			node = (this.key == 'add') ? tw.data : this.parent.node,
			text = tw.children.inp.value();

		tw.deleteInput();

		if (text != '') tw.addProcess(node, text);
	}

	static watchForInput(e) {
		if (!e.target.lx.hasTrigger('click', lx.TreeBox.applyAddNode))
			this.deleteInput();
	}

	/*
	 * Для кнопки, удаляющей узел
	 * */
	static delNode() {
		var leaf = this.parent,
			node = leaf.node,
			tw = leaf.box;

		if (tw.trigger('beforeDel', event, node) === false) return;

		// if (tw.db) {
		// 	JsHttpRequest.query('lx/php/lx/TreeWidgetBackend.php', {
		// 		type: 'del',
		// 		id: node.key,
		// 		table: tw.db
		// 	}, function(res) {
		// 		node.del();
		// 		tw.trigger('afterDel', event);
		// 		tw.renew();
		// 	}, true);
		// 	return;
		// }

		node.del();
		tw.trigger('afterDel', event);
		tw.renew();
	};
}

class TreeLeaf extends Box {
	constructor(config) {
		super(config);
		this.node = config.node;
	}

	postBuild() {
		this.box = this.ancestor({is: lx.TreeBox});
		this.create();
	}

	create() {
		var tw = this.box,
			but = new Rect({
			parent: this,
			key: 'open',
			size: [tw.leafHeight+'px', tw.leafHeight+'px'],
			css: 'lx-TW-Button',
			click: lx.TreeBox.toggleOpened
		}).addClass(
			(this.node.keys.length || (this.node.fill !== undefined && this.node.fill))
				? 'lx-TW-Button-closed' : 'lx-TW-Button-empty'
		);
		but.opened = false;

		var lbl = new Box({
			parent: this,
			key: 'label',
			left: tw.leafHeight + tw.step + 'px',
			css: 'lx-TW-Label'
		});

		if ( tw.leafConstructor ) tw.leafConstructor(this);
	}

	createChild(config={}) {
		var tw = this.box;
		if (config.width === undefined) config.width = tw.leafHeight + 'px';
		if (config.height === undefined) config.height = tw.leafHeight + 'px';

		config.parent = this;
		config.right = -(tw.step + tw.leafHeight) * (this.childrenCount() - 1) + 'px';

		var type = config.widget || Box;

		return new type(config);
	}

	createButton(config={}) {
		if (config instanceof Function) config = {click: config};

		config.key = 'button';
		config.css = config.css 
			? [config.css, 'lx-TW-Button']
			: 'lx-TW-Button';
		config.type = Rect;
		return this.createChild(config);
	}

	createAddButton(config) {
		var b = this.createButton(config);
		b.click(lx.TreeBox.addNode);
		b.addClass('lx-TW-Button-add');
		return b;
	}

	createDelButton(config) {
		var b = this.createButton(config);
		b.click(lx.TreeBox.delNode);
		b.addClass('lx-TW-Button-del');
		return b;
	}
}

lx.TreeBox.FORBIDDEN_ADDING = 0;
lx.TreeBox.ALLOWED_ADDING = 1;
lx.TreeBox.ALLOWED_ADDING_BY_TEXT = 2;
