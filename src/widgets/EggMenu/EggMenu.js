#lx:module lx.EggMenu;

#lx:use lx.Rect;
#lx:use lx.Box;

class EggMenu extends lx.Box #lx:namespace lx {
	modifyConfigBeforeApply(config={}) {
		config.size = ['40px', '50px'];
		return config;
	}

	build(config) {
		super.build(config);

		this.style('positioning', 'fixed');
		this.style('overflow', 'visible');
		this.roundCorners(20);
		this.border();
		this.add(lx.Rect, {
			height:'25px',
			style:{fill:'white'}
		}).roundCorners({value:20, side:'t'})
			.move({parentMove: true});
		this.add(lx.Rect, {
			key: 'switcher',
			top:'25px',
			height:'25px',
			style:{fill:'green'}
		}).roundCorners({value:20, side:'b'});

		var pult = {};
		if (config.pultBox) pult.widget = config.pultBox;
		if (config.pultRender) pult.construction = config.pultRender;
		this.buildPult(pult);
	}

	buildPult(pultInfo) {
		if (pultInfo.lxEmpty) return;

		var widget = pultInfo.widget || lx.Box,
			config = {},
			construction = pultInfo.construction;
		if (widget.isArray) {
			config = widget[1];
			widget = widget[0];
		}
		config.parent = this;
		config.key = 'pult';

		var pult = new widget(config);

		construction(pult);
		pult.hide();
	}

	#lx:client {
		postBuild(config) {
			super.postBuild(config);
			this.on('move', ()=>this.holdPultVisibility());
			this->switcher.click(self::switchOpened);
		}

		open() {
			var pult = this->pult;
			if (!pult) return;
			pult.show();
			pult.left(this.width('px') + 'px');
			this.holdPultVisibility();
		}

		close() {
			var pult = this->pult;
			if (!pult) return;
			pult.hide();
		}

		holdPultVisibility() {
			var pult = this->pult;
			if (!pult) return;
			var out = pult.isOutOfVisibility(this.parent);
			pult.geomPriorityH(lx.WIDTH);
			if (out.left) pult.left(this.width('px') + 'px');
			if (out.right) pult.right(this.width('px') + 'px');
			pult.geomPriorityV(lx.HEIGHT);
			if (out.top) pult.top('0px');
			if (out.bottom) pult.bottom('0px');
		}

		static switchOpened() {
			var menu = this.parent,
				pult = menu->pult;
			if (!pult) return;

			if (pult.visibility()) menu.close();
			else menu.open();
		}
	}
}
