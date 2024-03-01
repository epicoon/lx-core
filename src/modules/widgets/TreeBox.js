#lx:module lx.TreeBox;

#lx:use lx.Box;
#lx:use lx.Input;

/**
 * @widget lx.TreeBox
 * @content-disallowed
 * 
 * @events [
 *     leafOpening,
 *     leafOpened,
 *     leafClosed,
 *     beforeAddLeaf,
 *     afterAddLeaf,
 *     beforeDropLeaf,
 *     afterDropLeaf
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
				{
					backgroundColor: css.preset.hotMainColor,
					'@icon': ['\\2716', {fontSize:10, paddingBottom:'0px'}] 
				},
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
	 *     [tree] {lx.Tree|lx.RecursiveTree},
	 *     [indent = 10] {Number},
	 *     [step = 5] {Number},
	 *     [leafHeight = 18] {Number},
	 *     [labelWidth = 250] {Number},
	 *     [addAllowed = false] {Boolean},
	 *     [rootAddAllowed = false] {Boolean},
	 *     [leaf] {Function} (: argument - leaf {TreeLeaf} :),
	 *     [beforeAddLeaf] {Function} (: argument - node {lx.Tree|lx.RecursiveTree} :),
	 *     [beforeDropLeaf] {Function} (: argument - leaf {TreeLeaf} :)
	 * }}
	 */
	render(config) {
		super.render(config);

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

		this.beforeAddLeafHandler = config.beforeAddLeaf || null;
		this.beforeDropLeafHandler = config.beforeDropLeaf || null;
		this.leafRenderer = config.leaf || null;
		this.tree = config.tree || new lx.Tree();
		this.onAddHold = false;
		this.onDelHold = false;
		this.addBreaked = false;

		let w = this.step * 2 + this.leafHeight + this.labelWidth,
			el = new lx.Box({
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
		//TODO lx.RecursiveTree
		if (this.tree) this.tree = (new lx.TreeConverter).treeToJson(this.tree);
		if (this.leafRenderer)
			this.leafRenderer = this.packFunction(this.leafRenderer);
	}

	#lx:client {
		clientRender(config) {
			super.clientRender(config);

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
			move.left( this.width('px') - this.indent - this.step + 'px' );
			move.trigger('move');
			this.prepareRoot();
		}

		postUnpack(config) {
			super.postUnpack(config);

			//TODO lx.RecursiveTree
			if (this.tree && lx.isString(this.tree))
				this.tree = (new lx.TreeConverter).jsonToTree(this.tree);

			if (this.leafRenderer && lx.isString(this.leafRenderer))
				this.leafRenderer = this.unpackFunction(this.leafRenderer);
		}

		leafs() {
			if (!this->work->leaf) return new lx.Collection();
			return this->work.getAll('leaf');
		}

		leaf(i) {
			return this.leafs().at(i);
		}

		leafByNode(node) {
			let match = null;
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
		 */
		setStateMemoryKey(key) {
			// На открытие ветки
			this.on('leafOpening', function(e) {
				let opened = this.getOpenedInfo();
				opened.push(e.leaf.index);
				lx.app.cookie.set(key, opened.join(','));
			});

			// На закрытие ветки
			this.on('leafClosed', function(e) {
				let opened = this.getOpenedInfo();
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

			let scrollTop = this.domElem.param('scrollTop'),
				opened = [];
			this.leafs().forEach(a=>{
				if (a->open.opened)
					opened.push(a.node);
			});

			this->work.clear();
			this.prepareRoot();
			for (let i in opened) {
				// todo - неэффективно
				let leaf = this.leafByNode(opened[i]);
				if (!leaf) continue;
				this.openBranch(leaf);
			}
			this.domElem.param('scrollTop', scrollTop);
			return this;
		}

		/**
		 * Выдает массив индексов открытых листьев
		 */
		getOpenedInfo() {
			let opened = [];
			this.leafs().forEach((a, i)=> {
				if (a->open.opened)
					opened.push(i);
			});
			return opened;
		}

		/**
		 * Раскрывает листья согласно массиву, сформированному в .getOpenedInfo()
		 */
		useOpenedInfo(info) {
			for (let i=0, l=info.len; i<l; i++) {
				let leaf = this.leaf(info[i]);
				if (leaf) this.openBranch(leaf);
			}
		}

		prepareRoot() {
			this.createLeafs(this.tree, 0);
			let work = this->work;

			if (this.rootAddAllowed && !work.contains('submenu')) {
				let menu = new lx.Box({parent: work, key: 'submenu', height: this.leafHeight+'px'});
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

		createLeafs(tree, shift, before = null) {
			if (!tree || !(tree instanceof lx.Tree || tree instanceof lx.RecursiveTree)) return;

			let config = {
				parent: this->work,
				key: 'leaf',
				height: this.leafHeight + 'px'
			};
			if (before) config.before = before;

			let result = TreeLeaf.construct(tree.count(), config, {
				preBuild: (config, i) => {
					config.node = tree.getNth(i);
					return config;
				},
				postBuild: elem => {
					elem.overflow('visible');
					elem._shift = shift;
				}
			});

			return result;
		}

		openNode(node) {
			let nodes = [],
				temp = node,
				leaf = this.leafByNode(temp);

			while (!leaf) {
				temp = temp.root;
				nodes.push(temp);
				leaf = this.leafByNode(temp);
			}

			for (let i = nodes.length - 1; i >= 0; i--) {
				let node = nodes[i],
					leaf = this.leafByNode(node);
				this.openBranch(leaf);
			}
		}

		openBranch(leaf, event) {
			event = event || this.newEvent();
			event.leaf = leaf;
			let node = leaf.node;

			if (node.filled) {
				//TODO точка для расширения логики - если данных на фронте ещё нет, но узел знает, что не пуст
				// здесь может отработать запрос на дозагрузку данных
			} else this.trigger('leafOpening', event);

			if (!node.count()) return;

			this.useRenderCache();
			let leafs = this.createLeafs(node, leaf._shift + 1, leaf.nextSibling());
			leafs.forEach(a=>{
				let shift = this.step + (this.step + this.leafHeight) * (a._shift);
				a->open.left(shift + 'px');
				a->label.left(shift + this.step + this.leafHeight + 'px');
			});
			this.applyRenderCache();

			let b = leaf->open;
			b.opened = true;
			b.removeClass(this.basicCss.buttonClosed);
			b.addClass(this.basicCss.buttonOpened);
			this.trigger('leafOpened', event);
		}

		closeBranch(leaf, event) {
			event = event || this.newEvent();
			event.leaf = leaf;
			let i = leaf.index,
				shift = leaf._shift,
				next = this.leaf(++i);
			while (next && next._shift > shift) next = this.leaf(++i);

			let count = next ? next.index - leaf.index - 1 : Infinity;

			leaf.parent.del('leaf', leaf.index + 1, count);
			let b = leaf->open;
			b.opened = false;
			b.removeClass(this.basicCss.buttonOpened);
			b.addClass(this.basicCss.buttonClosed);
			this.trigger('leafClosed', event);
		}

		holdAdding() {
			this.onAddHold = true;
		}

		breakAdding() {
			this.onAddHold = false;
			this.addBreaked = true;
		}

		resumeAdding(newNodeAttributes = {}) {
			const node = this.onAddHold;
			this.onAddHold = false;
			return __addProcess(this, node, newNodeAttributes);
		}

		/**
		 * @param {lx.Tree} parentNode
		 * @param {Object} newNodeAttributes
		 */
		add(parentNode, newNodeAttributes) {
			if (parentNode.root) this.leafByNode(parentNode)->open.opened = true;
			let key = newNodeAttributes.key || parentNode.genKey(),
				node = parentNode.add(key);
			for (let f in newNodeAttributes)
				if (f == 'data' || !(f in node)) node[f] = newNodeAttributes[f];

			this.renew();
			return node;
		}

		holdDropping() {
			this.onDelHold = true;
		}

		breakDropping() {
			this.onDelHold = false;
		}

		resumeDropping() {
			const leaf = this.onDelHold;
			this.onDelHold = false;
			__delProcess(this, leaf);
		}
		
		drop(leaf) {
			let node = leaf.node;
			node.del();
			this.renew();
		}
	}

	beforeAddLeaf(func) {
		this.beforeAddLeafHandler = func;
	}

	beforeDropLeaf(func) {
		this.beforeDropLeafHandler = func;
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

	setLeafsRightForButtons(count) {
		this.setLeafsRight(this.step * (count + 1) + this.leafHeight * count);
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
			let tw = this.box,
				but = new lx.Rect({
					parent: this,
					key: 'open',
					geom: [0, 0, tw.leafHeight+'px', tw.leafHeight+'px'],
					click: __handlerToggleOpened
				}).addClass(
					(this.node.count() || (this.node.filled))
						? tw.basicCss.buttonClosed : tw.basicCss.buttonEmpty
				);
			but.opened = false;

			let lbl = new lx.Box({
				parent: this,
				key: 'label',
				left: tw.leafHeight + tw.step + 'px',
				css: tw.basicCss.label
			});

			if ( tw.leafRenderer ) tw.leafRenderer(this);
		}

		createChild(config={}) {
			let tw = this.box;
			if (config.width === undefined) config.width = tw.leafHeight + 'px';
			if (config.height === undefined) config.height = tw.leafHeight + 'px';

			config.parent = this;
			config.right = -(tw.step + tw.leafHeight) * (this.childrenCount() - 1) + 'px';

			let type = config.widget || lx.Box;

			return new type(config);
		}

		createButton(config={}) {
			if (config instanceof Function) config = {click: config};

			if (config.key === undefined) config.key = 'button';
			if (config.widget === undefined) config.widget = lx.Box;
			return this.createChild(config);
		}

		createAddButton(config = {}) {
			let b = this.createButton(config);
			b.click(__handlerAddNode);
			if (!config.css)
				b.addClass(this.box.basicCss.buttonAdd);
			return b;
		}

		createDropButton(config = {}) {
			let b = this.createButton(config);
			b.click(__handlerDelNode);
			if (!config.css)
				b.addClass(this.box.basicCss.buttonDel);
			return b;
		}
	}
}


function __handlerToggleOpened(event) {
	let tw = this.ancestor({is: lx.TreeBox}),
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
		obj = tw.beforeAddLeafHandler.call(this, pNode);
	if (tw.addBreaked) {
		tw.addBreaked = false;
		return;
	}
	if (tw.onAddHold) {
		tw.onAddHold = pNode;
		return;
	}

	__addProcess(tw, pNode, obj || {});
}

function __handlerDelNode() {
	let leaf = this.parent,
		tw = leaf.box;

	if (tw.beforeDropLeafHandler)
		tw.beforeDropLeafHandler.call(this, leaf);
	if (tw.onDelHold) {
		tw.onDelHold = leaf;
		return;
	}

	__delProcess(tw, leaf);
}

/*
 * Непосредственно создание нового узла
 */
function __addProcess(self, parentNode, newNodeAttributes = {}) {
	const e = self.newEvent({parentNode, newNodeAttributes});

	if (self.trigger(
		'beforeAddLeaf',
		e) === false
	) return null;
	
	const newNode = self.add(parentNode, newNodeAttributes);
	e.newNode = newNode;
	self.trigger('afterAddLeaf', e);
	return newNode;
}

function __delProcess(self, leaf) {
	let node = leaf.node;

	if (self.trigger(
		'beforeDropLeaf',
		self.newEvent({leaf, node})
	) === false) return;

	self.drop(leaf);

	self.trigger('afterDropLeaf');
}
