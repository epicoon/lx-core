#use lx.Rect as Rect;
#use lx.Box as Box;

class EggMenu extends Box #in lx {
	constructor(config={}) {
		config.size = ['40px', '50px'];
		super(config);

		var pult = {};
		if (config.pultBox) pult.widget = config.pultBox;
		if (config.pultRender) pult.construction = config.pultRender;

		this.build();
		this._pultConstruction = pult.lxEmpty ? null : pult;
		this._pult = null;
	}

	build() {
		this.style('positioning', 'fixed');
		this.style('overflow', 'visible');
		this.roundCorners(20);
		this.border();
		this.add(Rect, {
			height:'25px', style:{fill:'white'}
		}).roundCorners({value:20, side:'t'})
			.move({parentMove: true});
		this.add(Rect, {
			top:'25px', height:'25px', style:{fill:'green'}, click:self::switchOpened
		}).roundCorners({value:20, side:'b'});

		this.on('move', ()=>this.holdPultVisibility());
	}

	buildPult() {
		if (this._pultConstruction === null) return;

		var widget = this._pultConstruction.widget || Box,
			config = {},
			construction = this._pultConstruction.construction;
		if (widget.isArray) {
			config = widget[1];
			widget = widget[0];
		}
		config.parent = this;
		config.key = 'pult';
		config.left = this.width('px') + 'px';

		this._pult = new widget(config);

		construction(this._pult);
		this._pult.hide();
	}

	// setPultConstruction(callback) {
	// 	this._pultConstruction = callback;
	// }

	getPult() {
		if (this._pult === null) this.buildPult();
		return this._pult;
	}

	holdPultVisibility() {
		if (!this._pult) return;
		var out = this._pult.isOutOfVisibility(this.parent);
		this._pult.geomPriorityH(lx.WIDTH);
		if (out.left) this._pult.left(this.width('px') + 'px');
		if (out.right) this._pult.right(this.width('px') + 'px');
		this._pult.geomPriorityV(lx.HEIGHT);
		if (out.top) this._pult.top('0px');
		if (out.bottom) this._pult.bottom('0px');
	}

	open() {
		this.getPult().show();
	}

	close() {
		this.getPult().hide();
	}

	static switchOpened() {
		var menu = this.parent,
			pult = menu.getPult();
		if (!pult) return;

		pult.visibility(!pult.visibility());
	}
}
