#lx:module lx.EggMenu;

#lx:use lx.Rect;
#lx:use lx.Box;

class EggMenu extends lx.Box #lx:namespace lx {
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

	getZShift() {
		return 200;
	}

	build(config) {
		super.build(config);

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

		var menu = {};
		if (config.menuWidget) menu.widget = config.menuWidget;
		if (config.menuRenderer) menu.renderer = config.menuRenderer;
		this.buildMenu(menu);
	}

	buildMenu(menuInfo) {
		if (menuInfo.lxEmpty) return;

		var widget = menuInfo.widget || lx.Box,
			config = {},
			menuRenderer = menuInfo.menuRenderer;
		if (widget.isArray) {
			config = widget[1];
			widget = widget[0];
		}
		config.parent = this;
		config.key = 'menuBox';
		config.geom = true;

		var menu = new widget(config);

		if (menuRenderer) menuRenderer(menu);
		menu.hide();
	}

	#lx:client {
		postBuild(config) {
			super.postBuild(config);

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
