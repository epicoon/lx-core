#lx:module lx.TreeBox;

#lx:use lx.Box;
#lx:use lx.Input;

/* 
 * Special events:
 * - leafOpen
 * - leafClose
 * - beforeAdd
 * - afterAdd
 * - beforeDel
 * - afterDel
 */
#lx:namespace lx;
class TreeBox extends lx.Box {
	#lx:const
		FORBIDDEN_ADDING = 0,
		ALLOWED_ADDING = 1,
		ALLOWED_ADDING_BY_TEXT = 2;

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

	static initCssAsset(css) {
		css.addClass('lx-TreeBox', {
			backgroundColor: css.preset.altBodyBackgroundColor,
			borderRadius: '10px'
		});
		css.inheritAbstractClass('lx-TW-Button', 'ActiveButton', {
			color: css.preset.widgetIconColor,
			backgroundColor: css.preset.checkedMainColor
		});
		css.inheritClasses({
			'lx-TW-Button-closed':
				{ '@icon': ['\\25BA', {fontSize:10, paddingBottom:'3px', paddingLeft:'2px'}] },
			'lx-TW-Button-opened':
				{ '@icon': ['\\25BC', {fontSize:10, paddingBottom:'2px'}] },
			'lx-TW-Button-add'   :
				{ '@icon': ['\\002B', {fontSize:12, paddingBottom:'3px', fontWeight: 700}] },
			'lx-TW-Button-del'   :
				{ '@icon': ['\\002D', {fontSize:12, paddingBottom:'3px', fontWeight: 700}] }
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

	/* config = {
	 *	// стандартные для Box,
	 *	
	 *	data: lx.Tree
	 *	indent: 10,
	 *	step: 5,
	 *	leafHeight: 18,
	 *	labelWidth: 250,
	 *	add: 0|1|2,  // 0 - без возможности добавления, 1 - простое добавление с автоключом, 2 - добавление, требующее введения ключа
	 *	leaf: function(TreeLeaf)  // описывает поведение листа при его создании - как и что в нем дополнительно отображать
	 * }
	 * */
	build(config) {
		super.build(config);

		this.style('overflow', 'auto');

		//todo - сейчас только в пикселях, переделать несложно + надо значения вынести в константы класса
		this.indent = config.indent || 10;
		this.step   = config.step   || 5;
		this.leafHeight = config.leafHeight || 25;
		this.labelWidth = config.labelWidth || 250;
		this.addMode = config.add || self::FORBIDDEN_ADDING;
		this.rootAdding = (config.rootAdding === undefined)
			? !!this.addMode
			: config.rootAdding;

		if (config.leaf) this.leafConstructor = config.leaf;
		this.data = config.data || new lx.Tree();

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
		if (this.data) this.data = (new lx.TreeConverter).treeToJson(this.data);
		if (this.leafConstructor)
			this.leafConstructor = this.packFunction(this.leafConstructor);
	}

	#lx:client {
		clientBuild(config) {
			super.clientBuild(config);

			var work = this->work,
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

			if (this.data && lx.isString(this.data))
				this.data = (new lx.TreeConverter).jsonToTree(this.data);

			if (this.leafConstructor && lx.isString(this.leafConstructor))
				this.leafConstructor = this.unpackFunction(this.leafConstructor);
		}

		leafs() {
			if (!this->work->leaf) return new lx.Collection();
			return new lx.Collection(this->work->leaf);
		}

		leaf(i) {
			if (!(i in this->work->leaf)) return null;
			return this->work->leaf[i];
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
			this.on('leafOpen', function(e) {
				var opened = this.getOpenedInfo();
				opened.push(e.leaf.index);
				lx.Cookie.set(key, opened.join(','));
			});

			// На закрытие ветки
			this.on('leafClose', function(e) {
				var opened = this.getOpenedInfo();
				lx.Cookie.set(key, opened.join(','));
			});

			// Проверить состояние куки прямо сейчас
			let treeState = lx.Cookie.get(key);
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
				this.openBranch(this.leaf(info[i]));
			}
		}

		prepareRoot() {
			this.createLeafs(this.data);
			var work = this->work;

			//todo с условием стало напутано - подменю не обязано быть логически связанным с кнопкой добавления. Подумать нужно ли оно вообще
			if (this.addMode && this.rootAdding && !work.contains('submenu')) {
				var menu = new lx.Box({parent: work, key: 'submenu', height: this.leafHeight+'px'});
				new lx.Rect({
					key: 'add',
					parent: menu,
					width: this.leafHeight+'px',
					height: '100%',
					css: this.basicCss.buttonAdd,
					click: self::addNode
				});
			}
		}

		createLeafs(data, before) {
			if (!data || !(data instanceof lx.Tree)) return;

			var config = {
				parent: this->work,
				key: 'leaf',
				height: this.leafHeight + 'px'
			};
			if (before) config.before = before;

			var result = TreeLeaf.construct(data.count(), config, {
				preBuild:(config,i)=>{
					config.node = data.getNth(i);
					return config;
				},
				postBuild:elem=>{
					elem.overflow('visible');
				}
			});

			return result;
		}


		static toggleOpened(event) {
			var tw = this.ancestor({is: lx.TreeBox}),
				l = this.parent;
			if (this.opened) tw.closeBranch(l, event);
			else tw.openBranch(l, event);
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
		 * text - этот введенный текст, может быть обработан событием beforeAdd,
		 * без явного кода как этот текст использовать, он никуда не пойдет
		 */
		addProcess(parentNode, text = '') {
			// поля, которые будут добавлены новому узлу
			const newNodeAttributes = {};
			const e = this.newEvent({
				parentNode,
				newNodeAttributes,
				newNodeLabelText: {text}
			});
			if ( this.trigger('beforeAdd', e) === false ) return;
			const newNode = this.add(parentNode, newNodeAttributes);
			e.newNode = newNode;
			this.trigger('afterAdd', e);
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

		createInput(but) {
			this.deleteInput();

			var inp = new lx.Input({
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
			if (!this.contains('inp')) return;

			var inp = this->inp;
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
				text = tw->inp.value();

			tw.deleteInput();

			if (text != '') tw.addProcess(node, text);
		}

		static watchForInput(e) {
			var widget = e.target.__lx;
			if (!widget.hasTrigger('click', lx.TreeBox.applyAddNode))
				this.deleteInput();
		}

		/*
		 * Для кнопки, удаляющей узел
		 * */
		static delNode() {
			var leaf = this.parent,
				node = leaf.node,
				tw = leaf.box;

			if (tw.trigger('beforeDel', tw.newEvent({leaf, node})) === false) return;

			node.del();
			tw.trigger('afterDel');
			tw.renew();
		};
	}

	setLeafConstructor(leafConstructor) {
		this.leafConstructor = leafConstructor;
	}
}

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
					click: lx.TreeBox.toggleOpened
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

			if ( tw.leafConstructor ) tw.leafConstructor(this);
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
			b.click(lx.TreeBox.addNode);
			b.addClass(this.box.basicCss.buttonAdd);
			return b;
		}

		createDelButton(config) {
			var b = this.createButton(config);
			b.click(lx.TreeBox.delNode);
			b.addClass(this.box.basicCss.buttonDel);
			return b;
		}
	}
}
