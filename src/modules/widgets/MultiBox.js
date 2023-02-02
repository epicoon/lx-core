#lx:module lx.MultiBox;

#lx:use lx.Box;
#lx:use lx.Marks;
#lx:use lx.JointMover;

/**
 * @widget lx.MultiBox
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
class MultiBox extends lx.Box {
	#lx:const
		STYLE_JUSTIFY = 1,
		STYLE_STREAM = 2,
		MARKS_HEIGHT = 60,
		MARKS_WIDTH = 200;

	getBasicCss() {
		return {
			main: 'lx-MultiBox',
		};
	}

	static initCss(css) {
		css.inheritClass('lx-MultiBox', 'AbstractBox');
	}

	/**
	 * @widget-init
	 *
	 * @param [config] {Object: {
	 *     #merge(lx.Rect::constructor::config),
	 *     [marksStyle = lx.MultiBox.STYLE_JUSTIFY] {Number&Enum(
	 *         lx.MultiBox.STYLE_JUSTIFY,
	 *         lx.MultiBox.STYLE_STREAM
	 *     )},
	 *     [marks] {Array<String>|Object: #schema(lx.Marks::build::config)},
	 *     [marksPosition = lx.TOP] {Number&Enum(
	 *         lx.TOP,
	 *         lx.BOTTOM,
	 *         lx.LEFT,
	 *         lx.RIGHT
	 *     )},
	 *     [marksWidth = lx.MultiBox.MARKS_WIDTH] {Number} (: for marks position lx.LEFT and lx.RIGHT :),
	 *     [marksHeight = lx.MultiBox.MARKS_HEIGHT] {Number} (: for marks position lx.TOP and lx.BOTTOM :),
	 *     [appendAllowed = false] {Boolean} (: Opportunity to add single marks :),
	 *     [dropAllowed = false] {Boolean} (: Opportunity to remove single marks :),
	 *     [animation = false] {Boolean|Number} (: if Number is milliseconds :)
	 *     [joint = false] {Boolean} (: add lx.JointMover between :)
	 * }}
	 */
	build(config) {
		super.build(config);

		let marksConfig = config.marks || [];
		if (lx.isArray(marksConfig)) marksConfig = {marks:marksConfig};
		if (config.appendAllowed) marksConfig.appendAllowed = config.appendAllowed;
		if (config.dropAllowed) marksConfig.dropAllowed = config.dropAllowed;
		if (config.animation) marksConfig.animation = config.animation;

		this.marksWidth = config.marksWidth || lx.MultiBox.MARKS_WIDTH + 'px';
		this.marksHeight = config.marksHeight || lx.MultiBox.MARKS_HEIGHT + 'px';

		let marksBox = this.add(lx.Box, {key:'marksBox'});
		marksConfig.key = 'marks';
		marksConfig.geom = true;
		marksConfig.autopositioning = false;

		let marks = marksBox.add(lx.Marks, marksConfig);
		this.add(lx.Box, {key:'sheets'});
		marks.setSheets(this->sheets);

		this.marksPosition = undefined;
		this.marksStyle = undefined;
		this.joint = undefined;
		this.setMarksPosition(config);
		this.inMove = false;

		if (marks.mode == lx.Marks.MODE_UNI_SHEET) marks.open(0);
	}

	#lx:client clientBuild(config) {
		super.clientBuild(config);

		const marks = this->marksBox->marks;
		let events = [
			'selected',
			'unselected',
			'sheetOpened',
			'sheetClosed',
			'selectionChange',
			'markAppended',
			'beforeDropMark',
			'markDropped'
		];
		events.forEach(eName=>marks.on(eName, e=>this.trigger(eName, e)));

		const marksBox = this->marksBox;
		marksBox.overflow('hidden');
		__checkSize(this);
		marks.on('resize', ()=>__checkSize(this));
	}

	setMarksPosition(config = {}) {
		let side = lx.getFirstDefined(config.marksPosition, this.marksStyle, lx.TOP);
		let marksStyle = lx.getFirstDefined(config.marksStyle, this.marksStyle, self::STYLE_JUSTIFY);
		let joint = lx.getFirstDefined(config.joint, this.joint, false);
		__setInnerStructure(this, side, joint);
		__setMarksPositioning(this, side, marksStyle);
	}

	#lx:client appendMark(markText) {
		return this->marksBox->marks.appendMark(markText);
	}

	#lx:client dropMark(num) {
		this->marksBox->marks.dropMark(num, true);
	}

	mark(num) {
		return this->marksBox->marks.mark(num);
	}

	sheet(num) {
		return this->marksBox->marks.sheet(num);
	}

	getActiveIndex() {
		return this->marksBox->marks.getActiveIndex();
	}

	select(num) {
		this->marksBox->marks.select(num);
	}

	unselect(num = null) {
		this->marksBox->marks.unselect(num);
	}

	setCondition(num, func) {
		this->marksBox->marks.setCondition(num, func);
	}
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * PRIVATE
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function __setInnerStructure(self, side, joint) {
	if (self.marksPosition == side && self.joint == joint) return;

	let sheets = self->sheets;
	let marksBox = self->marksBox;
	let jointConfig = {key: 'joint'};
	self.dropPositioning();

	if (side == lx.TOP) {
		jointConfig.before = sheets;
		jointConfig.top = self.marksHeight;
		if (sheets.nextSibling() == marksBox)
			marksBox.setParent({before: sheets});
	} else if (side == lx.BOTTOM) {
		jointConfig.after = sheets;
		jointConfig.bottom = self.marksHeight;
		if (marksBox.nextSibling() == sheets)
			sheets.setParent({before: marksBox});
	}
	if (side == lx.TOP || side == lx.BOTTOM) {
		if (joint) {
			sheets.style('position', 'absolute');
			marksBox.style('position', 'absolute');
			marksBox.height(self.marksHeight);
			sheets.setGeom([0, 0, 100, 100]);
			new lx.JointMover(jointConfig);
		} else {
			self.streamProportional({direction: lx.VERTICAL});
			marksBox.style('position', 'relative');
			marksBox.height(self.marksHeight);
			sheets.setGeom([0, 0, null, null, 0, 0]);
			sheets.setGeom([null, null, null, null, null, null]);
			sheets.style('position', 'relative');
			sheets.height(1);
		}
	}

	if (side == lx.LEFT) {
		jointConfig.before = sheets;
		jointConfig.left = self.marksWidth;
		if (sheets.nextSibling() == marksBox)
			marksBox.setParent({before: sheets});
	} else if (side == lx.RIGHT) {
		jointConfig.after = sheets;
		jointConfig.right = self.marksWidth;
		if (marksBox.nextSibling() == sheets)
			sheets.setParent({before: marksBox});
	}
	if (side == lx.LEFT || side == lx.RIGHT) {
		if (joint) {
			sheets.style('position', 'absolute');
			marksBox.style('position', 'absolute');
			marksBox.width(self.marksWidth);
			sheets.setGeom([0, 0, 100, 100]);
			new lx.JointMover(jointConfig);
		} else {
			self.streamProportional({direction: lx.HORIZONTAL});
			marksBox.style('position', 'relative');
			marksBox.width(self.marksWidth);
			sheets.setGeom([0, 0, null, null, 0, 0]);
			sheets.setGeom([null, null, null, null, null, null]);
			sheets.style('position', 'relative');
			sheets.width(1);
		}
	}

	self.marksPosition = side;
	self.joint = joint;
}

function __setMarksPositioning(self, side, marksStyle) {
	let streamConfig = __getMarksStreamConfig(self, side, marksStyle);
	self->marksBox->marks[streamConfig.method](streamConfig.config);
	self.marksStyle = marksStyle;
}

function __getMarksStreamConfig(self, side, marksStyle) {
	if (side == lx.TOP || side == lx.BOTTOM) {
		if (marksStyle == lx.MultiBox.STYLE_JUSTIFY) {
			return {
				method: 'streamProportional',
				config: {
					direction: lx.HORIZONTAL,
					indent: '10px',
					minWidth: '100px',
					maxWidth: '1000px'
				}
			};
		}
		if (marksStyle == lx.MultiBox.STYLE_STREAM) {
			return {
				method: 'stream',
				config: {
					direction: lx.HORIZONTAL,
					indent: '10px',
					minWidth: '100px',
					maxWidth: '300px',
					width: 'auto'
				}
			}
		}
	}

	if (side == lx.LEFT || side == lx.RIGHT) {
		if (marksStyle == lx.MultiBox.STYLE_JUSTIFY) {
			return {
				method: 'streamProportional',
				config: {
					direction: lx.VERTICAL,
					indent: '10px',
					minWidth: '100px',
					maxWidth: '200px'
				}
			};
		}
		if (marksStyle == lx.MultiBox.STYLE_STREAM) {
			return {
				method: 'stream',
				config: {
					direction: lx.VERTICAL,
					indent: '10px',
					minWidth: '100px',
					maxWidth: '200px',
					width: 'auto'
				}
			}
		}
	}
}

#lx:client {
	function __checkSize(self) {
		const marks = self->marksBox->marks;
		const marksBox = self->marksBox;
		let needMove = (self.marksPosition == lx.TOP || self.marksPosition == lx.BOTTOM)
			? marks.width('px') > marksBox.width('px')
			: marks.height('px') > marksBox.height('px');
		if (needMove === self.inMove) return;

		if (needMove) {
			marks.move();
			self.inMove = true;
		} else {
			marks.move(false);
			marks.left(0);
			marks.top(0);
			self.inMove = false;
		}
	}
}
