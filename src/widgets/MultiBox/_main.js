#lx:use lx.Box as Box;

class MultiBox extends Box #lx:namespace lx {
	/**
	 * config = {
	 *	// стандартные для Box,
	 *	
	 *	marks: [],
	 *	markHeight: px|%|int,
	 *	animation: bool
	 * }
	 * */
	build(config) {
		super.build(config);

		this.activeSheetNum = null;

		var marks = config.marks || [],
			markH = config.markHeight || '25px',
			amt = marks.length;
		if (!amt) return;
		var markW = 100 / amt;

		//todo - на php умеет (тоже недоделано) расширенные конфиги понимать, надо симметриировать
		Box.construct(amt, {
			parent: this,
			key: 'mark',
			geom: [0, 0, markW, markH],
			css: 'lx-MultiBox-mark',
		}, {
			postBuild: function(a, i) { a.left(markW * i).text(marks[i]); }
		});

		Box.construct(amt, {parent: this, key: 'sheet', top: markH}).call('hide');

		this.select(0);
		if (config.animation) this.setTimer( (config.animation instanceof Function) ? config.animation : null );
	}

	postBuild(config) {
		super.postBuild(config);
		var marks = this.marks();
		if (marks) marks.each((a)=> {
			if (a.condition) a.condition = a.unpackFunction(a.condition);
			a.click(self::clickMark);
			a.on('mousedown', lx.Event.preventDefault);
			a.align(lx.CENTER, lx.MIDDLE);
		});
	}

	postUnpack() {
		var timer = this.lxExtract('__timer');
		if (timer) {
			var action = (timer === true) ? null : a.unpackFunction(timer);
			this.setTimer(action);
		}
	}

	select(num) {
		if (num == this.activeSheetNum) return;

		if (this.timer)
			this.timer.on(this.activeSheetNum, num);
		else
			this.setActiveSheet(num);
	}

	mark(num) {
		if (!this.children.mark || num >= this.children.mark.len) return null;
		return this.children.mark[num];
	}

	marks() {
		if (!this.children.mark) return null;
		return new lx.Collection(this.children.mark);
	}

	sheet(num) {
		if (!this.children.sheet || num >= this.children.sheet.len) return null;
		return this.children.sheet[num];
	}

	sheets() {
		if (!this.children.sheet) return null;
		return new lx.Collection(this.children.sheet);
	}

	activeMark() {
		if (this.activeSheetNum === null) return null;
		return this.mark(this.activeSheetNum);
	}

	activeSheet() {
		if (this.activeSheetNum === null) return null;
		return this.sheet(this.activeSheetNum);
	}

	setActiveSheet(num) {
		var oldIndex = this.activeSheetNum;
		if (this.activeSheetNum !== null) {
			this.activeMark().removeClass('lx-MultiBox-active');
			this.activeSheet().hide();
		}
		this.activeSheetNum = num;
		this.activeMark().addClass('lx-MultiBox-active');
		this.activeSheet().show();
		this.trigger('selectionChange', null, num, oldIndex);
	}

	setCondition(num, func) {
		this.mark(num).condition = func;
	}

	static clickMark(event) {
		if (this.condition && !this.condition()) return;

		event = event || window.event;
		lx.Event.preventDefault(event);
		var p = this.parent;
		if (p.activeSheetNum == this.index) return;

		var oldIndex = p.activeSheetNum;
		p.select(this.index);		
		p.trigger('selectionChange', event, this.index, oldIndex);
	}

	setTimer(action) {
		var timer = new lx.Timer(300);
		timer.owner = this;

		timer.on = function(oldNum, newNum) {
			if (this.inAction) return;
			this.oldNum = oldNum;
			this.newNum = newNum;
			this.owner.sheet(newNum).show();
			this.start();
		};

		timer.action = action || function() {
			var k = this.shift();
			this.owner.sheet(this.newNum).opacity(k);
			this.owner.sheet(this.oldNum).opacity(1 - k);
			if (this.periodEnds()) {
				this.stop();
				this.owner.setActiveSheet(this.newNum);
			}
		};

		this.timer = timer;
	}
}