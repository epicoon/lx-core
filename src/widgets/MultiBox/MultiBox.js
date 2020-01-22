#lx:module lx.MultiBox;

#lx:use lx.Box;

class MultiBox extends lx.Box #lx:namespace lx {
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

		lx.Box.construct(amt, {
			parent: this,
			key: 'mark',
			geom: [0, 0, markW, markH],
			css: this.basicCss.mark,
		}, {
			postBuild: function(a, i) { a.left(markW * i + '%').text(marks[i]); }
		});

		lx.Box.construct(amt, {parent: this, key: 'sheet', top: markH}).call('hide');

		this.select(0);
		if (config.animation) this.animation = config.animation;
	}

	#lx:server beforePack() {
		if (this.animation && this.animation.isFunction)
			this.animation = this.packFunction(this.animation);

		var marks = this.marks();
		if (marks) marks.each((a)=> {
			if (a.condition) a.condition = a.packFunction(a.condition);
		});
	}

	#lx:client {
		postBuild(config) {
			super.postBuild(config);

			if (this.animation) {
				if (this.animation.isString) this.animation = this.unpackFunction(this.animation);
				this.setTimer( (this.animation instanceof Function) ? this.animation : null );
				delete this.animation;
			}

			var marks = this.marks();
			if (marks) marks.each((a)=> {
				if (a.condition) a.condition = a.unpackFunction(a.condition);
				a.click(self::clickMark);
				a.on('mousedown', lx.Event.preventDefault);
				a.align(lx.CENTER, lx.MIDDLE);
			});
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

			timer.whileCycle(action || function() {
				var k = this.shift();
				this.owner.sheet(this.newNum).opacity(k);
				this.owner.sheet(this.oldNum).opacity(1 - k);
				if (this.periodEnds()) {
					this.stop();
					this.owner.setActiveSheet(this.newNum);
				}
			});

			this.timer = timer;
		}
	}

	getBasicCss() {
		return {
			main: 'lx-MultiBox',
			mark: 'lx-MultiBox-mark',
			active: 'lx-MultiBox-active'
		};
	}

	select(num) {
		if (num == this.activeSheetNum) return;

		if (this.timer)
			this.timer.on(this.activeSheetNum, num);
		else
			this.setActiveSheet(num);
	}

	mark(num) {
		if (!this->mark || num >= this->mark.len) return null;
		return this->mark[num];
	}

	marks() {
		if (!this->mark) return null;
		return new lx.Collection(this->mark);
	}

	sheet(num) {
		if (!this->sheet || num >= this->sheet.len) return null;
		return this->sheet[num];
	}

	sheets() {
		if (!this->sheet) return null;
		return new lx.Collection(this->sheet);
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
			this.activeMark().removeClass(this.basicCss.active);
			this.activeSheet().hide();
		}
		this.activeSheetNum = num;
		this.activeMark().addClass(this.basicCss.active);
		this.activeSheet().show();
		#lx:client{ this.trigger('selectionChange', null, num, oldIndex); }
	}

	setCondition(num, func) {
		this.mark(num).condition = func;
	}
}
