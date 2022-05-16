#lx:module lx.MultiBox;

#lx:use lx.Box;

#lx:namespace lx;
class MultiBox extends lx.Box {
	#lx:const
		MODE_UNI_SHEET = 1,
		MODE_MULTI_SHEET = 2,
		MARK_HEIGHT = 40,
		MARK_WIDTH = 200,
		INDENT = 10;

	getBasicCss() {
		return {
			main: 'lx-MultiBox',
			mark: 'lx-MultiBox-mark',
			active: 'lx-MultiBox-active'
		};
	}

	static initCssAsset(css) {
		css.inheritClass('lx-MultiBox', 'AbstractBox');
		css.inheritClass('lx-MultiBox-mark', 'ActiveButton');
		css.addClass('lx-MultiBox-active', {
			backgroundColor: css.preset.checkedDarkColor,
			color: css.preset.checkedSoftColor
		});
	}

	/**
	 * config = {
	 *	// стандартные для Box,
	 *	
	 *  mode: self::MODE_UNI_SHEET | self::MODE_MULTI_SHEET
	 *  markWidth: int
	 *  markHeight: int
	 *  indent: int
	 *	marks: lx.Collection | array,
	 *	template: object,
	 *	sheets: lx.Collection | array | lx.Box | 'auto'
	 *  animation: bool | int
	 * }
	 * */
	build(config) {
		this.mode = config.mode || self::MODE_UNI_SHEET;

		var marks = config.marks;
		if (!marks) return;

		var template = __defineTemplate(config);
		var configArr = __defineMarksConfig(this, config, template);

		var marksBox = new lx.Box(configArr.marksBoxCofig);
		var step = config.indent || self::INDENT;
		marksBox.gridProportional({
			cols: template.cols,
			rows: template.rows,
			indent: step + 'px'
		});
		this.marks = lx.Box.construct(marks.len, {parent:marksBox, key:'mark'}, {postBuild:(a, i)=>a.text(marks[i])});
		this.marks.forEach(mark=>{
			mark.addClass(this.basicCss.mark);
			mark.checked = false;
		});

		if (config.sheets) {
			if (config.sheets instanceof lx.Rect)
				this.sheets = lx.Box.construct(marks.len, {parent: config.sheets, key: 'sheet', geom: true});
			else if (config.sheets instanceof lx.Collection)
				this.sheets = config.sheets;
		}

		if (!this.sheets) {
			var sheetsBox = new lx.Box({parent: this, key: 'sheets', geom: configArr.sheetsGeom});
			this.sheets = lx.Box.construct(marks.len, {parent: sheetsBox, key: 'sheet', geom: true});
		}

		this.sheets.forEach(child=>child.hide());

		if (this.mode == self::MODE_UNI_SHEET) this.select(0);
		if (config.animation) this.animation = config.animation;
	}

	#lx:client clientBuild(config) {
		super.clientBuild(config);

		if (this.animation) {
			var duration = lx.isNumber(this.animation) ? this.animation : 300;
			this.setAnimationOnOpen(duration, _handler_defaultAnimationOnOpen);
			this.setAnimationOnClose(duration, _handler_defaultAnimationOnClose);
			delete this.animation;
		}

		if (this.marks) this.marks.forEach(mark=>{
			mark.align(lx.CENTER, lx.MIDDLE);
			mark.on('mousedown', lx.Event.preventDefault);
			mark.click(_handler_clickMark);
		});
	}

	#lx:server beforePack() {
		var marks = [];
		if (this.marks) this.marks.forEach(mark=>{
			let data = {i: mark.renderIndex};			
			if (mark.condition) data.condition = mark.packFunction(mark.condition);
			marks.push(data);
		});

		var sheets = [];
		if (this.sheets) this.sheets.forEach(sheet=>sheets.push(sheet.renderIndex));

		this.marks = marks;
		this.sheets = sheets;
	}

	#lx:client restoreLinks(loader) {
		this.marks = loader.getCollection(this.marks, {
			index: 'i',
			fields: {
				condition: {
					type: 'function',
					name: 'condition'
				}
			}
		});
		this.sheets = loader.getCollection(this.sheets);
	}

	mark(num) {
		return this.marks.at(num);
	}

	sheet(num) {
		return this.sheets.at(num);
	}

	getActiveIndex() {
		let index = null;
		this.marks.forEach(function (mark) {
			if (mark.checked) {
				index = mark.index;
				this.stop();
			}
		});
		return index;
	}

	select(num) {
		var mark = this.mark(num);
		mark.checked = true;
		mark.addClass(this.basicCss.active);

		var sheet = this.sheet(num);
		if (this.animationOnOpen) {
			var timer = __initTimerOnOpen(this);
			timer.on(sheet);
		} else sheet.show();

		#lx:client {
			this.trigger('selected', this.newEvent({mark, sheet}));
		}
	}

	unselect(num = null) {
		if (num === null) num = this.getActiveIndex();
		var mark = this.mark(num);
		mark.checked = false;
		mark.removeClass(this.basicCss.active);

		var sheet = this.sheet(num);
		if (this.animationOnClose) {
			var timer = __initTimerOnClose(this);
			timer.on(sheet);
		} else sheet.hide();

		#lx:client {
			this.trigger('unselected', this.newEvent({mark, sheet}));
		}
	}

	setCondition(num, func) {
		this.mark(num).condition = func;
	}

	#lx:client {
		setAnimationOnOpen(duration, callback) {
			callback = callback || _handler_defaultAnimationOnOpen;
			this.animationOnOpen = {duration, callback};
		}

		setAnimationOnClose(duration, callback) {
			callback = callback || _handler_defaultAnimationOnClose;
			this.animationOnClose = {duration, callback};
		}
	}
}


/***********************************************************************************************************************
 * PRIVATE
 **********************************************************************************************************************/

function __defineTemplate(config) {
	var template = config.template || {};
	if (!template.position) template.position = lx.TOP;
	if (!template.cols && !template.rows) template.rows = 1;
	if (template.rows && !template.cols) {
		template.cols = Math.floor((config.marks.len - 1) / template.rows) + 1;
	} else if (!template.rows && template.cols) {
		template.rows = Math.floor((config.marks.len - 1) / template.cols) + 1;
	}
	return template;
}

function __defineMarksConfig(self, config, template) {	
	var marksBoxCofig = {parent:self};
	var sheetsGeom = [];
	var markWidth = config.markWidth || lx.MultiBox.MARK_WIDTH;
	var markHeight = config.markHeight || lx.MultiBox.MARK_HEIGHT;
	var step = config.indent || lx.MultiBox.INDENT;
	if (config.sheets) {
		marksBoxCofig.geom = true;
		return {marksBoxCofig, sheetsGeom};
	}

	var sheetsStep = step + 'px';
	switch (template.position) {
		case lx.TOP:
			marksBoxCofig.height = markHeight * template.rows + (step*2) + 'px';
			sheetsGeom = [sheetsStep, marksBoxCofig.height, null, null, sheetsStep, sheetsStep];
			break;
		case lx.BOTTOM:
			marksBoxCofig.height = (markHeight + step) * template.rows + step + 'px';
			marksBoxCofig.bottom = 0;
			sheetsGeom = [sheetsStep, sheetsStep, null, null, sheetsStep, marksBoxCofig.height];
			break;
		case lx.LEFT:
			marksBoxCofig.width = (markWidth + step) * template.cols + step + 'px';
			sheetsGeom = [marksBoxCofig.width, sheetsStep, null, null, sheetsStep, sheetsStep];
			break;
		case lx.RIGHT:
			marksBoxCofig.width = (markWidth + step) * template.cols + step + 'px';
			marksBoxCofig.height = 'auto';
			marksBoxCofig.right = 0;
			sheetsGeom = [sheetsStep, sheetsStep, null, null, marksBoxCofig.width, sheetsStep];
			break;
	}
	return {marksBoxCofig, sheetsGeom};
}

#lx:client {
	function _handler_clickMark(event) {
		if (this.condition && !this.condition()) return;

		event = event || window.event;
		lx.Event.preventDefault(event);

		var p = this.parent.parent;
		if (p.mode == lx.MultiBox.MODE_UNI_SHEET) {
			if (this.checked) return;

			var oldActive = null;
			p.marks.forEach(function(mark, i) {
				if (mark.checked) {
					oldActive = i;
					this.stop();
				}
			});

			if (oldActive !== null) p.unselect(oldActive);
			p.select(this.index);
			event.oldSheet = oldActive;
			event.newSheet = this.index;
			p.trigger('sheetOpened', event);
			p.trigger('sheetClosed', event);
			p.trigger('selectionChange', event);
			return;
		}

		if (this.checked) {
			event.oldSheet = this.index;
			p.unselect(this.index);
			p.trigger('sheetClosed', event);
		} else {
			event.newSheet = this.index;
			p.select(this.index);
			p.trigger('sheetOpened', event);
		}
	}

	function _handler_defaultAnimationOnOpen(timeShift, sheet) {
		sheet.opacity(timeShift);
	}

	function _handler_defaultAnimationOnClose(timeShift, sheet) {
		sheet.opacity(1 - timeShift);
	}

	function __initTimerOnOpen(self) {
		if (self.timerOnOpen) return;
		var timer = new lx.Timer();
		timer.on = function(sheet) {
			if (this.inAction) return;
			this.sheet = sheet;
			this.sheet.show();
			this.start();
		};
		timer.whileCycle(function() {
			var k = this.shift();
			this.callback(k, this.sheet);
			if (this.periodEnds()) {
				this.stop();
				this.sheet = null;
			}
		});
		timer.periodDuration = self.animationOnOpen.duration;
		timer.callback = self.animationOnOpen.callback;
		return timer;
	}

	function __initTimerOnClose(self) {
		var timer = new lx.Timer();
		timer.on = function(sheet) {
			if (this.inAction) return;
			this.sheet = sheet;
			this.start();
		};
		timer.whileCycle(function() {
			var k = this.shift();
			this.callback(k, this.sheet);
			if (this.periodEnds()) {
				this.stop();
				this.sheet.hide();
				this.sheet = null;
			}
		});
		timer.periodDuration = self.animationOnClose.duration;
		timer.callback = self.animationOnClose.callback;
		return timer;
	}
}
