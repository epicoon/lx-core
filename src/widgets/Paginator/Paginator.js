#lx:use lx.Box as Box;

class Paginator extends Box #lx:namespace lx {
	#lx:const
		DEFAULT_SLOTS_AMOUNT = 7,
		DEFAULT_ELEMENTS_PER_PAGE = 10,
		DISPLAY_TYPE_NUMBERS = 1,
		DISPLAY_TYPE_RANGES = 2;

	postBuild(config) {
		super.postBuild(config);

		this.firstSlotIndex = 0;

		this.slotsAmount = [config.slotsAmount, self::DEFAULT_SLOTS_AMOUNT].lxGetFirstDefined();
		this.displayType = [config.displayType, self::DISPLAY_TYPE_NUMBERS].lxGetFirstDefined();
		this.elementsPerPage = config.elementsPerPage || self::DEFAULT_ELEMENTS_PER_PAGE;

		this.elementsAmount = [config.elementsAmount, 0].lxGetFirstDefined();
		this.pagesAmount = Math.ceil(this.elementsAmount / this.elementsPerPage);

		this.__builder = config.builder || null;
		this.runBuilder();
		this.selectPage([config.activePageNumber, 0].lxGetFirstDefined());
	}

	selectPage(number, event) {
		if (number == this.activePageNumber) {
			return;
		}

		if (number >= this.pagesAmount) {
			number = this.pagesAmount - 1;
		}

		this.activeSlotNumber = null;
		this.activePageNumber = number;
		this.slotMap = new Array(this.slotsAmount);
		if (this.slotsAmount == 1) {
			// Только многоточие
			this.slotMap[0] = null;
		} else if (number > this.pagesAmount - this.slotsAmount) {
			// Многоточие спереди, потом хвост
			this.slotMap[0] = null;
			for (var i=1; i<this.slotsAmount; i++) {
				this.slotMap[i] = this.pagesAmount - this.slotsAmount + i;
			}
		} else {
			// Зависит от количества слотов
			var forLeft;
			if (this.slotsAmount == 2) {
				forLeft = 1;
				this.slotMap[this.slotMap.len - 1] = null;
			} else {
				forLeft = this.slotsAmount - 2;
				this.slotMap[this.slotsAmount - 2] = null;
				this.slotMap[this.slotsAmount - 1] = this.pagesAmount - 1;
			}

			var counter = 1,
				forward = true,
				lims = [number, number];
			while (counter < forLeft) {
				if (forward) {
					lims[1]++;
					counter++;
				} else if (!forward && lims[0]) {
					lims[0]--;
					counter++;
				}
				forward = !forward;
			}

			for (var i=0, l=forLeft; i<l; i++) {
				this.slotMap[i] = i + lims[0];
			}
		}

		this.slotMap.each((val, i)=> {
			var slot = this.child(i + this.firstSlotIndex);
			slot.off('click', self::onSlotClick);
			slot.fill('lightgray');
			if (val === null) {
				slot.text('...');
			} else {
				slot.text(val + 1);
				if (val == number) {
					this.child(i + this.firstSlotIndex).fill('lightgreen');
					this.activeSlotNumber = i;
				}
				slot.click(self::onSlotClick);
			}
			slot.align(lx.CENTER, lx.MIDDLE);
		});

		this.trigger('change', event, number, this.elementsPerPage);
	}

	static setBuilder(builder) {
		this.__builder = builder;
	}

	delaultBuilder() {
		this.firstSlotIndex = 1;
		var slots = Math.min(this.pagesAmount, this.slotsAmount);
		this.slot({
			cols: slots + 2,
			rows: 1,
			indent: '5px',
			align: lx.RIGHT
		});
		this.getChildren().each((a)=>{
			a.style('cursor', 'pointer');
			a.on('mousedown', lx.Event.preventDefault);
		});

		var prev = this.child(0);
		prev.fill('lightgray');
		prev.text('<');
		prev.align(lx.CENTER, lx.MIDDLE);
		prev.click(self::toPrevPage);

		var next = this.child(slots + 1);
		next.fill('lightgray');
		next.text('>');
		next.align(lx.CENTER, lx.MIDDLE);
		next.click(self::toNextPage);
	}

	runBuilder() {
		var builder = this.__builder || self::__builder || this.delaultBuilder;
		builder.call(this);
	}

	setElementsAmount(amount) {
		this.elementsAmount = amount;
		this.pagesAmount = Math.ceil(this.elementsAmount / this.elementsPerPage);
		var number = this.activePageNumber;
		this.activePageNumber = -1;
		this.selectPage(number);
	}

	static onSlotClick(event) {
		this.parent.selectPage(this.parent.slotMap[this.index - this.parent.firstSlotIndex], event);
	}

	static toPrevPage() {
		var p = this.parent;
		if (p.activePageNumber) p.selectPage(p.activePageNumber - 1);
	}

	static toNextPage() {
		var p = this.parent;
		if (p.activePageNumber < p.pagesAmount - 1) p.selectPage(p.activePageNumber + 1);
	}
}
