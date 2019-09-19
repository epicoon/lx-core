class AlignPositioningStrategy extends lx.PositioningStrategy #lx:namespace lx {
	init(hor, vert) {
		var config;
		if (vert !== undefined) config = {
			horizontal: hor,
			vertical: vert
		};
		else if (hor !== undefined && hor.isObject) config = hor;
		else config = {};

		this.direction = config.direction || lx.HORIZONTAL;
		this.horizontal = config.horizontal || lx.CENTER;
		this.vertical = config.vertical || lx.MIDDLE;
		var indents = this.setIndents(config);

		this.owner.style('display', 'flex');
		this.owner.style('flex-direction', this.direction == lx.HORIZONTAL ? 'row' : 'column');
		var vAlign, hAlign;
		switch (this.horizontal) {
			case lx.LEFT:
				this.owner.style('text-align', 'left');
				hAlign = 'flex-start';
				break;
			case lx.CENTER:
				this.owner.style('text-align', 'center');
				hAlign = 'center';
				break;
			case lx.JUSTIFY:
				this.owner.style('text-align', 'justify');
				hAlign = 'center';
				break;
			case lx.RIGHT:
				this.owner.style('text-align', 'right');
				hAlign = 'flex-end';
				break;
		}
		switch (this.vertical) {
			case lx.TOP: vAlign = 'flex-start'; break;
			case lx.MIDDLE: vAlign = 'center'; break;
			case lx.BOTTOM: vAlign = 'flex-end'; break;
		}
		this.owner.style('align-items', this.direction == lx.HORIZONTAL ? vAlign : hAlign);
		this.owner.style('justify-content', this.direction == lx.HORIZONTAL ? hAlign : vAlign);

		this.owner.getChildren().each((el)=>{
			el.removeClass('lx-abspos');
		});
		this.actualizeIndents(indents);
	}

	#lx:server pack() {
		return super.pack()
			+ ';d:' + this.direction
			+ ';h:' + this.horizontal
			+ ';v:' + this.vertical;
	}

	#lx:client unpackProcess(config) {
		this.direction = +config.d;
		this.horizontal = +config.h;
		this.vertical = +config.v;
	}

	allocate(elem, config) {
		var geom = this.geomFromConfig(config);
		elem.style('width', geom.w || 0);
		elem.style('height', geom.h || 0);
		this.actualizeIndents();
	}

	setIndents(config) {
		super.setIndents(config);
		//TODO false - рефакторинговый костыль. Использование этого флага в перспективе должно быть упразднено
		var indents = this.getIndents(false);

		//TODO - актуально и для грида
		if (indents.paddingTop) this.owner.style('padding-top', indents.paddingTop);
		if (indents.paddingBottom) this.owner.style('padding-bottom', indents.paddingBottom);
		if (indents.paddingLeft) this.owner.style('padding-left', indents.paddingLeft);
		if (indents.paddingRight) this.owner.style('padding-right', indents.paddingRight);

		return indents;
	}

	actualizeIndents(indents) {
		//TODO false - рефакторинговый костыль. Использование этого флага в перспективе должно быть упразднено
		if (!indents) indents = this.getIndents(false);

		if (this.direction == lx.HORIZONTAL && indents.stepX) {
			var lastIndex = this.owner.childrenCount() - 1;
			this.owner.getChildren().each((el, i)=>{
				el.style('margin-bottom', null);
				if (i == lastIndex) el.style('margin-right', null);
				else el.style('margin-right', indents.stepX);
			});
		} else if (this.direction == lx.VERTICAL && indents.stepY) {
			var lastIndex = this.owner.childrenCount() - 1;
			this.owner.getChildren().each((el, i)=>{
				el.style('margin-right', null);
				if (i == lastIndex) el.style('margin-bottom', null);
				else el.style('margin-bottom', indents.stepX);
			});
		}
	}
}
