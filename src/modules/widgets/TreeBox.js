#lx:module lx.TreeBox;

#lx:use lx.Box;
#lx:use lx.Input;

/**
 * @widget lx.TreeBox
 * @content-disallowed
 * 
 * @events [
 *     leafOpen,
 *     leafClose,
 *     beforeAdd,
 *     afterAdd,
 *     beforeDel,
 *     afterDel
 * ]
 */
#lx:namespace lx;
class TreeBox extends lx.Box {
	getBasicCss() {
		return {
			main: 'lx-TreeBox',
			button: 'lx-TW-Button',
			buttonClosed: 'lx-TW-Button-closed',
			buttonOpened: 'lx-TW-Button-opened',
			buttonEmpty: 'lx-TW-Button-empty',
			buttonAdd: 'lx-TW-Button-add',
			buttonDel: 'lx-TW-Button-del',
			label: 'lx-TW-Label'
		};
	}

	static initCss(css) {
		css.addClass('lx-TreeBox', {
			borderRadius: '10px'
		});
		css.inheritAbstractClass('lx-TW-Button', 'ActiveButton', {
			color: css.preset.widgetIconColor,
			backgroundColor: css.preset.checkedMainColor
		});
		css.inheritClasses({
			'lx-TW-Button-closed':
				{ '@icon': ['\\25BA', {fontSize:10, paddingBottom:'4px', paddingLeft:'2px'}] },
			'lx-TW-Button-opened':
				{ '@icon': ['\\25BC', {fontSize:10, paddingBottom:'2px'}] },
			'lx-TW-Button-add'   :
				{ '@icon': ['\\271A', {fontSize:10, paddingBottom:'0px'}] },
			'lx-TW-Button-del'   :
				{ '@icon': ['\\2716', {fontSize:10, paddingBottom:'0px'}] },
		}, 'lx-TW-Button');
		css.inheritClass('lx-TW-Button-empty', 'Button', {
			backgroundColor: css.preset.checkedMainColor,
			cursor: 'default'
		});
		css.addClass('lx-TW-Label', {
			overflow: 'hidden',
			whiteSpace: 'nowrap',
			textOverflow: 'ellipsis',
			backgroundColor: css.preset.textBackgroundColor,
			borderRadius: css.preset.borderRadius
		});
	}

	/**
	 * @widget-init
	 *
	 * @param [config] {Object: {
	 *     #merge(lx.Rect::constructor::config),
	 *     [tree] {lx.Tree},
	 *     [indent = 10] {Number},
	 *     [step = 5] {Number},
	 *     [leafHeight = 18] {Number},
	 *     [labelWidth = 250] {Number},
	 *     [addAllowed = false] {Boolean},
	 *     [rootAddAllowed = false] {Boolean},
	 *     [leaf] {Function} (: argument - leaf {TreeLeaf} :),
	 *     [befroeAddLeaf] {Function} (: argument - node {lx.Tree} :)
	 * }}
	 */
	build(config) {
		super.build(config);

		this.style('overflow', 'auto');

		//todo - сейчас только в пикселях, переделать несложно + надо значения вынести в константы класса
		this.indent = config.indent || 10;
		this.step   = config.step   || 5;
		this.leafHeight = config.leafHeight || 25;
		this.labelWidth = config.labelWidth || 250;
		this.addAllowed = config.addAllowed || false;
		this.rootAddAllowed = (config.rootAddAllowed === undefined)
			? this.addAllowed
			: config.rootAddAllowed;

		this.beforeAddLeafHandler = config.befroeAddLeaf || null;
		this.leafRenderer = config.leaf || null;
		this.tree = config.tree || new lx.Tree();
		this.onAddHold = false;

		var w = this.step * 2 + this.leafHeight + this.labelWidth;
		var el = new lx.Box({
			parent: this,
			key: 'work',
			width: w + 'px'
		});

		new lx.Rect({
			parent: this,
			key: 'move',
			left: w + 'px',
			width: this.step + 'px',
			style: {cursor: 'ew-resize'}
		});
	}

	#lx:server beforePack() {
		if (this.tree) this.tree = (new lx.TreeConverter).treeToJson(this.tree);
		if (this.leafRenderer)
			this.leafRenderer = this.packFunction(this.leafRenderer);
	}

	#lx:client {
		clientBuild(config) {
			super.clientBuild(config);

			let work = this->work,
				move = this->move;
			work.stream({
				padding: this.indent+'px',
				step: this.step+'px',
				paddingRight: '0px',
				minHeight: this.leafHeight + 'px'
			});
			work.style('overflow', 'visible');
			move.move({ yMove: false });
			move.on('move', function() { work.width(this.left('px') + 'px'); });
			this.prepareRoot();
		}

		postUnpack(config) {
			super.postUnpack(config);

			if (this.tree && lx.isString(this.tree))
				this.tree = (new lx.TreeConverter).jsonToTree(this.tree);

			if (this.leafRenderer && lx.isString(this.leafRenderer))
				this.leafRenderer = this.unpackFunction(this.leafRenderer);
		}

		leafs() {
			if (!this->work->leaf) return new lx.Collection();
			return new lx.Collection(this->work->leaf);
		}

		leaf(i) {
			const c = this->work.getAll('leaf');
			return c.at(i);
		}

		leafByNode(node) {
			var match = null;
			this.leafs().forEach(function(a) {
				if (a.node === node) {
					match = a;
					this.stop();
				}
			});
			return match;
		}

		setTree(tree, forse = false) {
			this.tree = tree;
			this.renew(forse);
			return this;
		}

		dropTree() {
			this.tree = new lx.Tree();
			this->work.clear();
		}

		/**
		 * Чтобы открытые ветви дерева не забывались при перезагрузке страницы
		 * */
		setStateMemoryKey(key) {
			// На открытие ветки
			this.on('leafOpen', function(e) {
				var opened = this.getOpenedInfo();
				opened.push(e.leaf.index);
				lx.app.cookie.set(key, opened.join(','));
			});

			// На закрытие ветки
			this.on('leafClose', function(e) {
				var opened = this.getOpenedInfo();
				lx.app.cookie.set(key, opened.join(','));
			});

			// Проверить состояние куки прямо сейчас
			let treeState = lx.app.cookie.get(key);
			if (treeState) {
				this.useOpenedInfo(treeState.split(','));
			}
		}

		renew(forse = false) {
			if (!forse && !this.isDisplay()) return;

			var scrollTop = this.domElem.param('scrollTop');
			var opened = [];
			this.leafs().forEach(a=>{
				if (a->open.opened)
					opened.push(a.node);
			});

			this->work.clear();
			this.prepareRoot();
			for (var i in opened) {
				// todo - неэффективно
				var leaf = this.leafByNode(opened[i]);
				if (!leaf) continue;
				this.openBranch(leaf);
			}
			this.domElem.param('scrollTop', scrollTop);
			return this;
		}

		/**
		 * Выдает массив индексов открытых листьев
		 * */
		getOpenedInfo() {
			var opened = [];
			this.leafs().forEach((a, i)=> {
				if (a->open.opened)
					opened.push(i);
			});
			return opened;
		}

		/**
		 * Раскрывает листья согласно массиву, сформированному в .getOpenedInfo()
		 * */
		useOpenedInfo(info) {
			for (var i=0, l=info.len; i<l; i++) {
				let leaf = this.leaf(info[i]);
				if (leaf) this.openBranch(leaf);
			}
		}

		prepareRoot() {
			this.createLeafs(this.tree);
			var work = this->work;

			if (this.rootAddAllowed && !work.contains('submenu')) {
				var menu = new lx.Box({parent: work, key: 'submenu', height: this.leafHeight+'px'});
				new lx.Rect({
					key: 'add',
					parent: menu,
					width: this.leafHeight+'px',
					height: '100%',
					css: this.basicCss.buttonAdd,
					click: __handlerAddNode
				});
			}
		}

		createLeafs(tree, before) {
			if (!tree || !(tree instanceof lx.Tree)) return;

			var config = {
				parent: this->work,
				key: 'leaf',
				height: this.leafHeight + 'px'
			};
			if (before) config.before = before;

			var result = TreeLeaf.construct(tree.count(), config, {
				preBuild:(config,i)=>{
					config.node = tree.getNth(i);
					return config;
				},
				postBuild:elem=>{
					elem.overflow('visible');
				}
			});

			return result;
		}

		openBranch(leaf, event) {
			event = event || this.newEvent();
			event.leaf = leaf;
			var node = leaf.node;

			if ( node.fill !== undefined && node.fill ) {
				//TODO точка для расширения логики - данных на фронте ещё нет, но узел знает, что не пуст
				// здесь должно отработать что-то вроде коллбэка на дозагрузку данных
			} else this.trigger('leafOpen', event);

			if (!node.keys.len) return;

			this.useRenderCache();
			var leafs = this.createLeafs(node, leaf.nextSibling());
			leafs.forEach(a=>{
				var shift = this.step + (this.step + this.leafHeight) * (node.deep());
				a->open.left(shift + 'px');
				a->label.left(shift + this.step + this.leafHeight + 'px');
			});
			this.applyRenderCache();

			var b = leaf->open;
			b.opened = true;
			b.removeClass(this.basicCss.buttonClosed);
			b.addClass(this.basicCss.buttonOpened);
		}

		closeBranch(leaf, event) {
			event = event || this.newEvent();
			event.leaf = leaf;
			var i = leaf.index,
				deep = leaf.node.deep(),
				next = this.leaf(++i);
			while (next && next.node.deep() > deep) next = this.leaf(++i);

			var count = next ? next.index - leaf.index - 1 : Infinity;

			leaf.parent.del('leaf', leaf.index + 1, count);
			var b = leaf->open;
			b.opened = false;
			b.removeClass(this.basicCss.buttonOpened);
			b.addClass(this.basicCss.buttonClosed);
			this.trigger('leafClose', event);
		}

		holdAdding() {
			this.onAddHold = true;
		}

		breakAdding() {
			this.onAddHold = false;
		}

		resumeAdding(data = {}) {
			const node = this.onAddHold;
			this.onAddHold = false;
			return this.addProcess(node, data);
		}

		/*
		 * Непосредственно создание нового узла
		 */
		addProcess(parentNode, data = {}) {
			// поля, которые будут добавлены новому узлу
			if (!data.newNodeAttributes) data.newNodeAttributes = {};
			data.parentNode = parentNode;
			const e = this.newEvent(data);
			if ( this.trigger('beforeAdd', e) === false ) return null;
			const newNode = this.add(parentNode, data.newNodeAttributes);
			e.newNode = newNode;
			this.trigger('afterAdd', e);
			return newNode;
		}

		/**
		 * @param {lx.Tree} parentNode
		 * @param {Object} newNodeAttributes
		 */
		add(parentNode, newNodeAttributes) {
			if (parentNode.root) this.leafByNode(parentNode)->open.opened = true;
			var key = newNodeAttributes.key || parentNode.genKey(),
				node = parentNode.add(key);
			for (var f in newNodeAttributes)
				if (f == 'data' || !(f in node)) node[f] = newNodeAttributes[f];

			this.renew();
			return node;
		}
	}

	beforeAddLeaf(func) {
		this.beforeAddLeafHandler = func;
	}

	setLeafRenderer(func) {
		this.leafRenderer = func;
	}

	setLeafsRight(val) {
		let work = this->work,
			move = this->move,
			w = this.width('px') - val;
		work.width(w + 'px');
		move.left(w + 'px');
	}
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * PRIVATE
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

#lx:client {
	class TreeLeaf extends lx.Box {
		constructor(config) {
			super(config);
			this.node = config.node;
			this.box = this.ancestor({is: lx.TreeBox});
			this.create();
		}

		create() {
			var tw = this.box,
				but = new lx.Rect({
					parent: this,
					key: 'open',
					geom: [0, 0, tw.leafHeight+'px', tw.leafHeight+'px'],
					click: __handlerToggleOpened
				}).addClass(
					(this.node.keys.length || (this.node.fill !== undefined && this.node.fill))
						? tw.basicCss.buttonClosed : tw.basicCss.buttonEmpty
				);
			but.opened = false;

			var lbl = new lx.Box({
				parent: this,
				key: 'label',
				left: tw.leafHeight + tw.step + 'px',
				css: tw.basicCss.label
			});

			if ( tw.leafRenderer ) tw.leafRenderer(this);
		}

		createChild(config={}) {
			var tw = this.box;
			if (config.width === undefined) config.width = tw.leafHeight + 'px';
			if (config.height === undefined) config.height = tw.leafHeight + 'px';

			config.parent = this;
			config.right = -(tw.step + tw.leafHeight) * (this.childrenCount() - 1) + 'px';

			var type = config.widget || lx.Box;

			return new type(config);
		}

		createButton(config={}) {
			if (config instanceof Function) config = {click: config};

			if (config.key === undefined) config.key = 'button';
			if (config.widget === undefined) config.widget = lx.Box;
			return this.createChild(config);
		}

		createAddButton(config) {
			var b = this.createButton(config);
			b.click(__handlerAddNode);
			b.addClass(this.box.basicCss.buttonAdd);
			return b;
		}

		createDelButton(config) {
			var b = this.createButton(config);
			b.click(__handlerDelNode);
			b.addClass(this.box.basicCss.buttonDel);
			return b;
		}
	}
}


function __handlerToggleOpened(event) {
	var tw = this.ancestor({is: lx.TreeBox}),
		l = this.parent;
	if (this.opened) tw.closeBranch(l, event);
	else tw.openBranch(l, event);
}

function __handlerAddNode() {
	const tw = this.ancestor({is: lx.TreeBox}),
		isRootAdding = (this.key == 'add'),
		pNode = isRootAdding ? tw.tree : this.parent.node;

	let obj = null;
	if (tw.beforeAddLeafHandler)
		obj = tw.beforeAddLeafHandler(pNode);
	if (tw.onAddHold) {
		tw.onAddHold = pNode;
		return;
	}

	tw.addProcess(pNode, obj || {});
}

function __handlerDelNode() {
	var leaf = this.parent,
		node = leaf.node,
		tw = leaf.box;

	if (tw.trigger('beforeDel', tw.newEvent({leaf, node})) === false) return;

	node.del();
	tw.trigger('afterDel');
	tw.renew();
}
