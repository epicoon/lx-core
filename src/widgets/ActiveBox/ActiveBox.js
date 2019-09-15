#lx:module lx.ActiveBox;

#lx:private;

class ActiveBox extends lx.Box #lx:namespace lx {
	getContainer() {
		return this->body;
	}

	/**
	 * config = {
	 * 	header: string
	 * 	headerHeight: 
	 * 	headerConfig: {}
	 * 	closeButton ???
	 * 	move: boolean
	 * 	resize: boolean
	 *	adhesive: boolean
	 * }
	 * */
	build(config) {
		this.setBuildMode(true);

		__setHeader(this, config);
		__setBody(this, config.body || lx.Box);

		if ([config.resize, self::DEFAULT_RESIZE].lxGetFirstDefined()) this.setResizer(config);
		this.adhesive = [config.adhesive, self::DEFAULT_ADHESIVE].lxGetFirstDefined();

		this.setBuildMode(false);

		super.build(config);
	}

	#lx:client postBuild(config) {
		super.postBuild(config);

		this.setBuildMode(true);

		if (this.width() === null) this.width(this.width('px')+'px');
		if (this.height() === null) this.height(this.height('px')+'px');

		if (this.adhesive) {
			ActiveBoxAdhesor.makeAdhesion(this);
		}

		if (this.contain('resizer') || this.contain('header')) {
			this.click(()=>lx.WidgetHelper.bringToFront(this));
			lx.WidgetHelper.bringToFront(this);
		}

		if (this.contain('header')) {
			this->header.on('dblclick', function() {
				if (!this.lxActiveBoxGeom) {
					this.lxActiveBoxGeom = this.parent.getGeomMask();
					this.parent.setGeom([0, 0, '100%', '100%']);
				} else {
					this.parent.copyGeom(this.lxActiveBoxGeom);
					delete this.lxActiveBoxGeom;
				}
			});
		}
		this.setBuildMode(false);
	}

	setResizer(config) {
		var resizer = new lx.Rect({
			parent: this,
			key: 'resizer',
			geom: [null, null, self::RESIZER_SIZE, self::RESIZER_SIZE, 0, 0]
		}).fill('Gray')
		.move({parentResize: true});

		if (config.adhesive) {
			resizer.moveParams.xMinMove = 5;
			resizer.moveParams.yMinMove = 5;
		}
	}
}

lx.ActiveBox.DEFAULT_MOVE = true;
lx.ActiveBox.DEFAULT_RESIZE = true;
lx.ActiveBox.DEFAULT_ADHESIVE = false;
lx.ActiveBox.ADHESION_DISTANCE = 15;
lx.ActiveBox.HEADER_HEIGHT = '25px';
lx.ActiveBox.RESIZER_SIZE = '12px';

//=============================================================================================================================
function __setHeader(self, config) {
	if (!config.header && !config.headerHeight && !config.headerConfig) return;

	var headerConfig = config.headerConfig || {};
	if (config.headerHeight) headerConfig.height = config.headerHeight;
	if (headerConfig.height === undefined) headerConfig.height = lx.ActiveBox.HEADER_HEIGHT;
	headerConfig.parent = self;
	headerConfig.key = 'header';

	var text = headerConfig.text || config.header || '';
	delete headerConfig.text;

	var header = new lx.Box(headerConfig);
	header.fill('lightgray');

	if (text != '') {
		var wrapper = header.add(lx.Box, {key: 'textWrapper'});
		wrapper.text(text);
		wrapper.align(lx.CENTER, lx.MIDDLE);
	}

	if ([config.move, lx.ActiveBox.DEFAULT_MOVE].lxGetFirstDefined())
		header.move({parentMove: true});

	if (config.closeButton) {
		let butConfig = config.closeButton.isObject ? config.closeButton : {};
		if (!butConfig.geom) butConfig.geom = [null, '2px', '20px', '20px', '2px'];
		if (!butConfig.style) butConfig.style = {fill:'red', cursor:'pointer'};
		if (!butConfig.click) butConfig.click = function() {
			self.parent.parent.hide();
		};
		butConfig.parent = header;
		let className = butConfig.widget ? butConfig.widget : lx.Box;
		new className(butConfig);
	}
}

function __setBody(self, constructor) {
	var config = {};
	if (constructor.isArray) {
		config = constructor[1];
		constructor = constructor[0];
	}
	config.parent = self;
	config.key = 'body';
	config.top = self.contain('header') ? lx.ActiveBox.HEADER_HEIGHT : 0;
	new constructor(config);
}

#lx:client {
	class ActiveBoxAdhesor {
		static makeAdhesion(el) {
			el.adhesiveBonds = this.getAdhesiveBondsBlank();
			this.check(el);

			el.on('move', this.checkOnMove);
			el.on('resize', this.checkOnResize);
		}

		static checkOnMove() {
			ActiveBoxAdhesor.check(this, 'move');
		}

		static checkOnResize() {
			ActiveBoxAdhesor.check(this, 'resize');
		}

		static check(ctx, action) {
			var env = ctx.parent.getChildren();
			env.each((a) => {
				if (a === ctx) return;
				let lims = ctx.rect('px'),
					aLims = a.rect('px'),
					lDist = lims.left - aLims.right,
					rDist = lims.right - aLims.left,
					tDist = lims.top - aLims.bottom,
					bDist = lims.bottom - aLims.top,
					valid = this.getValid(lDist, rDist, tDist, bDist, lims, aLims);

				if (!valid.ok() && ctx.adhesiveBonds && ctx.adhesiveBonds.contain(a)) {
					ctx.adhesiveBonds.remove(a);
					if (a.adhesiveBonds) {
						a.adhesiveBonds.remove(ctx);
						this.actualizeSizeShare(a);
					}
				}

				if (valid.ok()) {
					// Сразу нацепим прилипание во время движения и ресайза
					if (action == 'resize') {
						if (valid.x()) {
							var xDist = Math.abs(lDist) < Math.abs(rDist) ? lDist : rDist;
							ctx.width(lims.width - xDist + 'px');
						}
						if (valid.y()) {
							var yDist = Math.abs(tDist) < Math.abs(bDist) ? tDist : bDist;
							ctx.height(lims.height - yDist + 'px');
						}
					} else if (action == 'move') {
						if (valid.l) ctx.left(aLims.right + 'px');
						if (valid.r) ctx.left(aLims.left - lims.width + 'px');
						if (valid.t) ctx.top(aLims.bottom + 'px');
						if (valid.b) ctx.top(aLims.top - lims.height + 'px');
					}

					// Запишем кто с кем связался
					ctx.adhesiveBonds[valid.side()][a.lxid] = a;

					// Если соседи оба адгезивные - они умеют делить размер
					if (a.adhesiveBonds) {
						a.adhesiveBonds[valid.contrSide()][ctx.lxid] = ctx;
						this.actualizeSizeShare(a);
					}
				}

			});
			this.actualizeSizeShare(ctx);
		}

		static getValid(lDist, rDist, tDist, bDist, lims0, lims1) {
			return {
				l: Math.abs(lDist) < lx.ActiveBox.ADHESION_DISTANCE && lims0.top < lims1.bottom,
				r: Math.abs(rDist) < lx.ActiveBox.ADHESION_DISTANCE && lims0.top < lims1.bottom,
				t: Math.abs(tDist) < lx.ActiveBox.ADHESION_DISTANCE && lims0.left < lims1.right,
				b: Math.abs(bDist) < lx.ActiveBox.ADHESION_DISTANCE && lims0.left < lims1.right,
				x: function () {
					return this.l || this.r
				},
				y: function () {
					return this.t || this.b
				},
				ok: function () {
					return this.x() || this.y()
				},
				side: function () {
					if (this.l) return 'l';
					if (this.r) return 'r';
					if (this.t) return 't';
					if (this.b) return 'b';
				},
				contrSide: function () {
					if (this.l) return 'r';
					if (this.r) return 'l';
					if (this.t) return 'b';
					if (this.b) return 't';
				}
			};
		}

		static getAdhesiveBondsBlank() {
			return {
				l: {}, r: {}, t: {}, b: {},
				contain: function (el) {
					var key = el.lxid;
					if (key in this.l) return true;
					if (key in this.r) return true;
					if (key in this.t) return true;
					if (key in this.b) return true;
					return false;
				},
				remove: function (el) {
					var key = el.lxid;
					if (key in this.l) delete this.l[key];
					else if (key in this.r) delete this.r[key];
					else if (key in this.t) delete this.t[key];
					else if (key in this.b) delete this.b[key];
				}
			};
		}

		static actualizeSizeShare(el) {
			var size = Math.round(lx.ActiveBox.ADHESION_DISTANCE * 0.75),
				seams = [];

			el.setBuildMode(true);
			if (el.adhesiveBonds.r.lxEmpty) {
				el.del('r_size_share');
			} else if (!el.contain('r_size_share')) {
				seams.push(el.add(lx.Rect, {
					key: 'r_size_share',
					width: size + 'px',
					right: 0,
					style: {cursor: 'ew-resize'}
				}).move({parentResize: true, yMove: false}));
			}

			if (el.adhesiveBonds.b.lxEmpty) {
				el.del('b_size_share');
			} else if (!el.contain('b_size_share')) {
				seams.push(el.add(lx.Rect, {
					key: 'b_size_share',
					ignoreHeaderHeight: true,
					bottom: 0,
					height: size + 'px',
					style: {cursor: 'ns-resize'}
				}).move({parentResize: true, xMove: false}));
			}
			el.setBuildMode(false);

			seams.each((a) => {
				// a.on('moveBegin', function() { el.off('resize', ActiveBoxAdhesor.checkOnResize); });
				// a.on('moveEnd', function() { el.on('resize', ActiveBoxAdhesor.checkOnResize); });
				a.on('move', function () {
					ActiveBoxAdhesor[this.key[0] + 'SeamMove'](this);
				});
			});
		}

		static rSeamMove(seam) {
			var ab = seam.parent,
				bonds = ab.adhesiveBonds,
				els = bonds.r,
				r = ab.left('px') + ab.width('px'),
				delta = null;
			for (var i in els) {
				var el = els[i];
				if (delta === null) delta = el.left('px') - r;
				el.width(el.width('px') + delta + 'px');
				el.left(r + 'px');
				el.trigger('resize');
			}
		}

		static bSeamMove(seam) {
			var ab = seam.parent,
				bonds = ab.adhesiveBonds,
				els = bonds.b,
				b = ab.top('px') + ab.height('px'),
				delta = null;
			for (var i in els) {
				var el = els[i];
				if (delta === null) delta = el.top('px') - b;
				el.height(el.height('px') + delta + 'px');
				el.top(b + 'px');
				el.trigger('resize');
			}
		}
	}
}