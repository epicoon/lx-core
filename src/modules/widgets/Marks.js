#lx:module lx.Marks;

#lx:use lx.Box;

/**
 * @widget lx.Marks
 * @content-disallowed
 * 
 * @events [
 *     selected,
 *     unselected,
 *     sheetOpened,
 *     sheetClosed,
 *     selectionChange,
 *     markAppended,
 *     beforeDropMark,
 *     markDropped
 * ]
 */
#lx:namespace lx;
class Marks extends lx.Box {
	#lx:const
		MODE_UNI_SHEET = 1,
		MODE_MULTI_SHEET = 2;

	getBasicCss() {
		return {
			mark: 'lx-Marks-mark',
			active: 'lx-Marks-active',
			close: 'lx-Marks-close',
			hint: 'lx-Marks-hint',
			text: 'lx-Marks-text',
		};
	}

	static initCss(css) {
		css.inheritClass('lx-Marks-mark', 'ActiveButton');
		css.addClass('lx-Marks-active', {
			backgroundColor: css.preset.checkedDarkColor,
			color: css.preset.checkedSoftColor
		});
		css.addClass('lx-Marks-close', {
			color: css.preset.widgetIconColor,
			'@icon': ['\\2715', {fontSize:14}]
		});
		css.inheritClass('lx-Marks-hint', 'AbstractBox', {
			padding: '10px'
		});
		css.addClass('lx-Marks-text', {
			'@ellipsis': true
		});
	}

	/**
	 * @widget-init
	 *
	 * @param [config] {Object: {
	 *     #merge(lx.Rect::constructor::config),
	 *     [mode = lx.Marks.MODE_UNI_SHEET] {Number&Enum(
	 *         lx.Marks.MODE_UNI_SHEET,
	 *         lx.Marks.MODE_MULTI_SHEET
	 *     )} (:
	 *         MODE_UNI_SHEET - printing for only one sheet at the time
	 *         MODE_MULTI_SHEET - probability for printing several sheets at the same time
	 *     :),
	 *     [appendAllowed = false] {Boolean} (: Opportunity to add single marks :),
	 *     [dropAllowed = false] {Boolean} (: Opportunity to remove single marks :),
	 *     [animation = false] {Boolean|Number} (: if Number is milliseconds :),
	 *     [marks] {Array<String>},
	 *     [sheets] {lx.Rect|lx.Collection} (:
	 *         if lx.Rect is the parent for the sheets,
	 *         if lx.Collection is the sheets
	 *     :)
	 *     [autopositioning = true] {Boolean}
	 * }}
	 */
	build(config) {
		super.build(config);
		this.mode = config.mode || self::MODE_UNI_SHEET;
		this.animation = lx.getFirstDefined(config.animation, false);
		this.appendAllowed = lx.getFirstDefined(config.appendAllowed, false);
		this.dropAllowed = lx.getFirstDefined(config.dropAllowed, false);

		let autopositioning = lx.getFirstDefined(config.autopositioning, true);
		if (autopositioning && this.positioning().lxFullClassName() == 'lx.PositioningStrategy') {
			this.streamProportional({
				direction: lx.HORIZONTAL,
				indent: '10px'
			});
		}

		if (config.marks) this.setMarks(config.marks);
		else this.marks = new lx.Collection();
		this.sheetsBox = null;
		this.sheets = null;
		if (config.sheets) this.setSheets(config.sheets);

		if (this.mode == self::MODE_UNI_SHEET)
			this.open(0);
	}

	#lx:client clientBuild(config) {
		super.clientBuild(config);

		if (this.animation) {
			let duration = lx.isNumber(this.animation) ? this.animation : 300;
			this.setAnimationOnOpen(duration, _handler_defaultAnimationOnOpen);
			this.setAnimationOnClose(duration, _handler_defaultAnimationOnClose);
			delete this.animation;
		}

		this.marks.forEach(mark=>__clientBuildMark(this, mark));
	}

	#lx:server beforePack() {
		if (this.sheetsBox) this.sheetsBox = this.sheetsBox.renderIndex;

		let marks = [];
		if (this.marks) this.marks.forEach(mark=>{
			let data = {i: mark.renderIndex};
			if (mark.condition) data.condition = mark.packFunction(mark.condition);
			marks.push(data);
		});
		this.marks = marks;

		if (this.sheets) {
			let sheets = [];
			this.sheets.forEach(sheet=>sheets.push(sheet.renderIndex));
			this.sheets = sheets;
		}
	}

	#lx:client restoreLinks(loader) {
		if (this.sheetsBox) this.sheetsBox = loader.getWidget(this.sheetsBox);
		this.marks = loader.getCollection(this.marks, {
			index: 'i',
			fields: {
				condition: {
					type: 'function',
					name: 'condition'
				}
			}
		});
		if (this.sheets) this.sheets = loader.getCollection(this.sheets);
	}

	setMarks(marks) {
		this.del('mark');
		this.useRenderCache();
		this.marks = this.add(lxMark, marks.len, {key:'mark'});
		if (this.marks instanceof lx.Rect)
			this.marks = new lx.Collection(this.marks);
		this.marks.forEach(mark=>__buildMark(this, mark, marks[mark.index]));
		this.applyRenderCache();
	}

	setSheets(sheets) {
		if (sheets instanceof lx.Rect) {
			this.sheetsBox = sheets;
			this.sheets = lx.Box.construct(
				this.marks.len,
				{parent: sheets, key: 'sheet', geom: true}
			);
		}
		else if (sheets instanceof lx.Collection)
			this.sheets = sheets;
		this.sheets.forEach(s=>s.hide());
	}

	#lx:client appendMark(markText, sheet = null) {
		if (!this.appendAllowed) return null;
		if (!this.sheets) return null;

		if (!sheet) {
			if (!this.sheetsBox) {
				console.error('The Marks has external sheets collection');
				return null;
			}
			sheet = new lx.Box({parent: this.sheetsBox, key: 'sheet', geom: true});
			this.sheets.add(sheet);
		}
		sheet.hide();

		let mark = this.add(lxMark, {key:'mark'});
		__buildMark(this, mark, markText);
		__clientBuildMark(this, mark);
		mark.checked = false;

		this.marks.add(mark);
		this.trigger('markAppended', this.newEvent({mark, sheet}));
		return mark;
	}

	#lx:client dropMark(num, withSheet = false) {
		if (!this.dropAllowed) return;
		if (lx.isNumber(num) && num >= this.marks.len) return;

		let mark;
		if (lx.isNumber(num)) mark = this.mark(num);
		else {
			mark = num;
			num = mark.index;
		}
		this.marks.remove(mark);
		mark.del();

		let sheet = this.sheet(num);
		if (sheet) {
			if (withSheet) sheet.del();
			this.sheets.remove(sheet);
		}

		this.trigger('markDropped', this.newEvent({mark, sheet}));
	}

	mark(num) {
		return this.marks.at(num);
	}

	sheet(num) {
		if (!this.sheets) return null;
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

	open(num) {
		let mark = this.mark(num),
			sheet = this.sheet(num);
		if (!mark || !sheet) return;
		mark.checked = true;
		mark->but.addClass(this.basicCss.active);
		sheet.show();
	}

	close(num) {
		let mark = this.mark(num),
			sheet = this.sheet(num);
		if (!mark || !sheet) return;
		mark.checked = false;
		mark->but.removeClass(this.basicCss.active);
		sheet.hide();
	}

	select(num) {
		let mark = this.mark(num),
			sheet = this.sheet(num);
		if (!mark || mark.checked || !sheet) return;

		if (this.mode == self::MODE_UNI_SHEET)
			this.unselect();

		mark.checked = true;
		mark->but.addClass(this.basicCss.active);

		if (this.animationOnOpen) {
			let timer = __initTimerOnOpen(this);
			timer.on(sheet);
		} else sheet.show();

		#lx:client {
			this.trigger('selected', this.newEvent({mark, sheet}));
		}
	}

	unselect(num = null) {
		if (num === null) num = this.getActiveIndex();
		let mark = this.mark(num),
			sheet = this.sheet(num);
		if (!mark || !sheet) return;

		mark.checked = false;
		mark->but.removeClass(this.basicCss.active);

		if (this.animationOnClose) {
			let timer = __initTimerOnClose(this);
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


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * PRIVATE
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function __buildMark(self, mark, text) {
	let but = mark.add(lx.Box, {
		key: 'but',
		size: [100, 100],
		css: self.basicCss.mark,
	});
	if (self.dropAllowed) {
		but.streamProportional({ direction: lx.HORIZONTAL, minWidth: '20px' });

		let t = but.add(lx.Box, {key:'label', css:self.basicCss.text});
		t.text(text);
		t.align(lx.CENTER, lx.MIDDLE);

		let b = but.add(lx.Box);
		b.width('30px');
		b.add(lx.Box, {
			key: 'delBut',
			size: ['20px', '20px'],
			css: self.basicCss.close
		});
		b.align(lx.LEFT, lx.MIDDLE);
	} else {
		but.text(text);
		but.align(lx.CENTER, lx.MIDDLE);
	}
}

#lx:client {
	function __clientBuildMark(self, mark) {
		mark.on('mousedown', lx.preventDefault);
		mark.click(_handler_clickMark);
		mark.setEllipsisHint({css: self.basicCss.hint});
		if (self.dropAllowed) {
			mark->>delBut.click(function (e) {
				e.stopPropagation();
				const marks = this.ancestor({is:lx.Marks});
				const event = marks.newEvent({mark});
				marks.trigger('beforeDropMark', event);
				if (event.isPrevented()) return;
				marks.dropMark(mark.index, true);
			});
		}
	}

	function _handler_clickMark(event) {
		if (this.condition && !this.condition()) return;

		event = event || window.event;
		lx.preventDefault(event);

		let p = this.ancestor({is:lx.Marks});
		if (!p.sheets || !p.sheets.len) return;

		if (p.mode == lx.Marks.MODE_UNI_SHEET) {
			if (this.checked) return;

			let oldActive = p.getActiveIndex();
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
		let timer = new lx.Timer();
		timer.on = function(sheet) {
			if (this.inAction) return;
			this.sheet = sheet;
			this.sheet.show();
			this.start();
		};
		timer.whileCycle(function() {
			let k = this.shift();
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
		let timer = new lx.Timer();
		timer.on = function(sheet) {
			if (this.inAction) return;
			this.sheet = sheet;
			this.start();
		};
		timer.whileCycle(function() {
			let k = this.shift();
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

class lxMark extends lx.Box {
	removeDelButton() {
		this->>delBut.parent.del();
	}

	setLabel(label) {
		this->>label.text(label);
	}
}
