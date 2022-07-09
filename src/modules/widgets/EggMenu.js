#lx:module lx.EggMenu;

#lx:use lx.Box;

/**
 * @widget lx.EggMenu
 */
#lx:namespace lx;
class EggMenu extends lx.Box {
	_getContainer() {
		return this->menuBox._getContainer();
	}

	modifyConfigBeforeApply(config={}) {
		config.size = ['40px', '50px'];
		return config;
	}

	getBasicCss() {
		return {
			main: 'lx-EggMenu',
			top: 'lx-EggMenu-top',
			bottom: 'lx-EggMenu-bottom',
			onMove: 'lx-EggMenu-move',
		}
	}
	
	static initCssAsset(css) {
		let shadowSize = css.preset.shadowSize + 2,
			shadowShift = Math.floor(shadowSize * 0.5);
		css.addClass('lx-EggMenu', {
			overflow: 'visible',
			borderRadius: '25px',
			boxShadow: '0 '+shadowShift+'px '+shadowSize+'px rgba(0,0,0,0.5)'
		});
		css.addClass('lx-EggMenu-top', {
			backgroundColor: css.preset.bodyBackgroundColor,
			borderTopLeftRadius: '25px',
			borderTopRightRadius: '25px'
		});
		css.addClass('lx-EggMenu-bottom', {
			backgroundColor: css.preset.checkedSoftColor,
			borderBottomLeftRadius: '25px',
			borderBottomRightRadius: '25px'
		});
		css.addClass('lx-EggMenu-move', {
			marginTop: '-2px',
			boxShadow: '0 '+(Math.round(shadowShift*1.5))+'px '+(Math.round(shadowSize*1.5))+'px rgba(0,0,0,0.5)'
		});
	}

	getDefaultDepthCluster() {
		return lx.DepthClusterMap.CLUSTER_FRONT;
	}

	/**
	 * @widget-init
	 *
	 * @param [config] {Object: {
	 *     #merge(lx.Box::build::config),
	 *     [menuWidget = lx.Box] {lx.Box}
	 *     [menuConfig] {Object: {#schema(lx.Box::build::config)}}
	 *     [menuRenderer] {Function} (: argument - lx.Box :)
	 * }}
	 */
	build(config) {
		super.build(config);

		this.setBuildMode(true);
		this.style('positioning', 'fixed');
		this.add(lx.Rect, {
			key: 'top',
			height:'25px',
			css: this.basicCss.top
		}).move({parentMove: true});
		this.add(lx.Rect, {
			key: 'switcher',
			top:'25px',
			height:'25px',
			css: this.basicCss.bottom
		});
		this.setBuildMode(false);

		var menu = {};
		if (config.menuWidget) menu.widget = config.menuWidget;
		if (config.menuConfig) menu.config = config.menuConfig;
		if (config.menuRenderer) menu.renderer = config.menuRenderer;
		this.buildMenu(menu);
	}

	buildMenu(menuInfo) {
		this.setBuildMode(true);
		var widget = menuInfo.widget || lx.Box,
			config = menuInfo.config || {},
			menuRenderer = menuInfo.renderer;
		config.parent = this;
		config.key = 'menuBox';
		if (!config.geom) config.geom = true;

		var menu = new widget(config);
		menu.setGeomPriority(lx.WIDTH, lx.LEFT);
		menu.setGeomPriority(lx.HEIGHT, lx.TOP);

		//TODO некрасиво, но это частый класс для меню
		if (menu.lxFullClassName() == 'lx.ActiveBox')
	        menu->resizer.move({
	            parentResize: true,
	            xLimit: false,
	            yLimit: false
	        });

		if (menuRenderer) menuRenderer(menu);
		menu.hide();
		this.setBuildMode(false);
	}

	#lx:client {
		clientBuild(config) {
			super.clientBuild(config);

			if (this.basicCss.onMove) {
				this->top.on('moveBegin', ()=>{
					this.addClass(this.basicCss.onMove);
				});
				this->top.on('moveEnd', ()=>{
					this.removeClass(this.basicCss.onMove);
				});
			}

			this.on('move', ()=>this.holdPultVisibility());
			this->switcher.click(self::switchOpened);
		}

		open() {
			var menu = this->menuBox;
			if (!menu) return;
			menu.show();
			menu.left(this.width('px') + 'px');
			this.holdPultVisibility();
		}

		close() {
			var menu = this->menuBox;
			if (!menu) return;
			menu.hide();
		}

		holdPultVisibility() {
			var menu = this->menuBox;
			if (!menu) return;
			var out = menu.isOutOfVisibility(this.parent);

			if (out.left) {
				menu.right(null);
				menu.left(this.width('px') + 'px');
			}

			if (out.right) {
				menu.left(null);
				menu.right(this.width('px') + 'px');
			}

			if (out.top) {
				menu.bottom(null);
				menu.top('0px');
			}

			if (out.bottom) {
				menu.top(null);
				menu.bottom('0px');
			}
		}

		static switchOpened() {
			var menu = this.parent,
				menuBox = menu->menuBox;
			if (!menuBox) return;

			if (menuBox.visibility()) menu.close();
			else menu.open();
		}
	}
}
