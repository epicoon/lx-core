#lx:module lx.Paginator;
#lx:module-data {
    i18n: i18n.yaml
};

#lx:use lx.Box;

#lx:private;

class Paginator extends lx.Box #lx:namespace lx {
	#lx:const
		DEFAULT_SLOTS_COUNT = 7,
		DEFAULT_ELEMENTS_PER_PAGE = 10;

    /* config = {
     *	// стандартные для Box,
     *
     * slotsCount
     * elementsPerPage
     * elementsCount
     * activePage  // Активная страница считается из последовательности страниц, начиная с 1 (не с 0!)
     * }
     * */
	build(config) {
		super.build(config);

		this.firstSlotIndex = 0;

        this.elementsCount = [config.elementsCount, 0].lxGetFirstDefined();
        this.elementsPerPage = config.elementsPerPage || self::DEFAULT_ELEMENTS_PER_PAGE;
        this.pagesCount = Math.ceil(this.elementsCount / this.elementsPerPage);

		this.slotsCount = [config.slotsCount, self::DEFAULT_SLOTS_COUNT].lxGetFirstDefined();
		if (this.slotsCount <= 4) this.slotsCount = 1;

        this.runBuild();
        this.selectPage([config.activePage, 1].lxGetFirstDefined());
	}

    getBasicCss() {
        return {
            main: 'lx-Paginator',
            toStart: 'lx-Paginator-to-start',
            toFinish: 'lx-Paginator-to-finish',
            toLeft: 'lx-Paginator-to-left',
            toRight: 'lx-Paginator-to-right',
            middle: 'lx-Paginator-middle',
            page: 'lx-Paginator-page',
            active: 'lx-Paginator-active'
        };
    }

	#lx:client {
	    postBuild(config) {
            this->toStart.click(self::toFirstPage);
            this->toLeft.click(self::toPrevPage);
            this->toRight.click(self::toNextPage);
            this->toFinish.click(self::toLastPage);

            #lx:client {
                var middle = this->middle;
                if (middle.childrenCount() > 1) {
                    middle.getChildren().each((a, i)=>{
                        if (a->text.value() !== '...') a.click(self::onSlotClick);
                    });
                }
            }
        }

        static onSlotClick(event) {
            this.ancestor({is:lx.Paginator}).selectPage(+(this->text.value()));
        }

        static toPrevPage() {
            var p = this.parent;
            p.selectPage(p.activePage - 1);
        }

        static toNextPage() {
            var p = this.parent;
            p.selectPage(p.activePage + 1);
        }

        static toFirstPage() {
            var p = this.parent;
            p.selectPage(1);
        }

        static toLastPage() {
            var p = this.parent;
            p.selectPage(p.pagesCount);
        }
    }

    setElementsCount(count) {
        this.elementsCount = count;
        this.pagesCount = Math.ceil(this.elementsCount / this.elementsPerPage);
        var number = this.activePage;
        this.activePage = -1;
        this.selectPage(number);
    }

    runBuild() {
	    this.stream({direction: lx.HORIZONTAL, columnDefaultWidth: null});
	    this.begin();
            new lx.Box({key: 'toStart', css: this.basicCss.toStart});
            new lx.Box({key: 'toLeft', css: this.basicCss.toLeft});
            new lx.Box({key: 'middle'});
            new lx.Box({key: 'toRight', css: this.basicCss.toRight});
            new lx.Box({key: 'toFinish', css: this.basicCss.toFinish});
        this.end();

        var middle = this->middle;
        middle.stream({
            direction: lx.HORIZONTAL,
            indent: '5px',
            columnDefaultWidth: null,
            minWidth: 0
        });
	}

	selectPage(number) {
        this.activePage = __validatePageNumber(this, number);

	    if (this.slotsCount == 1) __fillMiddleSimple(this);
        else if (this.slotsCount == 5 || this.slotsCount == 6) __fillMiddleMin(this);
        else __fillMiddleMax(this);
    }
}

function __validatePageNumber(self, number) {
    if (number < 1) number = 1;
    if (number > this.pagesCount) number = self.pagesCount;

    self->toStart.disabled(false);
    self->toLeft.disabled(false);
    self->toRight.disabled(false);
    self->toFinish.disabled(false);

    if (number == 1) {
        self->toStart.disabled(true);
        self->toLeft.disabled(true);
    } else if (number == self.pagesCount) {
        self->toRight.disabled(true);
        self->toFinish.disabled(true);
    }

    return number;
}

function __fillMiddleSimple(self) {
    var middle = self->middle;
    if (middle.childrenCount() > 1) middle.clear();
    if (middle.childrenCount() == 0) middle.align(lx.CENTER, lx.MIDDLE);
    middle.text(
        #lx:i18n(lx.Paginator.Page) + ' '
        + self.activePage + ' '
        + #lx:i18n(lx.Paginator.of) + ' '
        + self.pagesCount
    );
}

function __fillMiddleMin(self) {
    __rebuildMiddle(self);
    __applyMiddleSequence(
        self,
        __calcSequence(self.activePage, self.pagesCount, self.slotsCount)
    );
}

function __fillMiddleMax(self) {
    __rebuildMiddle(self);
    var seq = __calcSequence(self.activePage - 1, self.pagesCount - 2, self.slotsCount - 2);
    seq.each((a, i)=>{
        if (a !== null) seq[i] = a + 1;
    });
    seq = [1].lxMerge(seq);
    seq.push(self.pagesCount);
    __applyMiddleSequence(self, seq);
}

function __rebuildMiddle(self) {
    var middle = self->middle;
    if (middle.childrenCount() != self.slotsCount) middle.clear();
    if (middle.childrenCount() == 0) {
        var c = middle.add(lx.Box, self.slotsCount, {width:'auto'});
        c.each((a)=>{
            a.align(lx.CENTER, lx.MIDDLE);
            a.addClass(self.basicCss.page);
        });
    }    
}

function __applyMiddleSequence(self, seq) {
    var middle = self->middle;
    middle.getChildren().each((a, i)=>{
        a.text(seq[i] === null ? '...' : seq[i]);
        a.toggleClassOnCondition(seq[i] == self.activePage, self.basicCss.active);
        #lx:client {
            if (seq[i] === null) a.off('click');
            else a.click(lx.Paginator.onSlotClick);
        }
    });
}

function __calcSequence(activePage, pagesCount, slotsCount) {
    var result = new Array(slotsCount);

    if (activePage <= Math.ceil(slotsCount * 0.5)) {
        for (var i=0; i<slotsCount-1; i++) result[i] = i+1;
        result[slotsCount - 1] = null;
        return result;
    }

    if ((pagesCount - activePage) < (slotsCount - 2)) {
        result[0] = null;
        for (var i=1; i<slotsCount; i++) result[i] = pagesCount - slotsCount + i + 1;
        return result;
    }

    result[0] = null;
    result[slotsCount - 1] = null;
    var activeIndex = Math.ceil((slotsCount - 2) * 0.5),
        firstPage = activePage - activeIndex;
    for (var i=1; i<slotsCount-1; i++) result[i] = firstPage + i;
    return result;
}
