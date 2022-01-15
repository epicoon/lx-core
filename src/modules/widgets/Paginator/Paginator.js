#lx:module lx.Paginator;
#lx:module-data {
    i18n: i18n.yaml
};

#lx:use lx.MainCssContext;
#lx:use lx.CssColorSchema;
#lx:use lx.Box;

class Paginator extends lx.Box #lx:namespace lx {
	#lx:const
		DEFAULT_SLOTS_COUNT = 7,
		DEFAULT_ELEMENTS_PER_PAGE = 10;

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

    static initCssAsset(css) {
        css.useContext(lx.MainCssContext.instance);
        css.addClass('lx-Paginator', {
            gridTemplateRows: '100% !important',
            overflow: 'hidden',
            whiteSpace: 'nowrap',
            textOverflow: 'ellipsis',
            border: 'solid 1px ' + lx.CssColorSchema.widgetBorderColor,
            borderRadius: lx.MainCssContext.borderRadius
        });
        css.addClass('lx-Paginator-middle', {
            width: 'auto'
        });
        css.addClass('lx-Paginator-page', {
            cursor: 'pointer'
        });
        css.addAbstractClass('Paginator-button', {
            background: lx.CssColorSchema.widgetGradient,
            color: lx.CssColorSchema.widgetIconColor,
            cursor: 'pointer'
        });
        css.inheritClass(
            'lx-Paginator-active',
            'Paginator-button',
            { borderRadius: lx.MainCssContext.borderRadius }
        );
        css.inheritClasses({
            'lx-Paginator-to-finish': { '@icon': ['\\00BB', {paddingBottom:'10px'}] },
            'lx-Paginator-to-start' : { '@icon': ['\\00AB', {paddingBottom:'10px'}] },
            'lx-Paginator-to-left'  : { '@icon': ['\\2039', {paddingBottom:'10px'}] },
            'lx-Paginator-to-right' : { '@icon': ['\\203A', {paddingBottom:'10px'}] }
        }, 'Paginator-button');
    }

    /* config = {
     *	// стандартные для Box,
     *
     * slotsCount
     * elementsPerPage
     * elementsCount
     * activePage
     * }
     * */
	build(config) {
		super.build(config);

		this.firstSlotIndex = 0;

        this.elementsCount = lx.getFirstDefined(config.elementsCount, 0);
        this.elementsPerPage = config.elementsPerPage || self::DEFAULT_ELEMENTS_PER_PAGE;
        this.pagesCount = Math.ceil(this.elementsCount / this.elementsPerPage);

		this.slotsCount = lx.getFirstDefined(config.slotsCount, self::DEFAULT_SLOTS_COUNT);
        if (this.slotsCount <= 4) this.slotsCount = 1;
        this.slotsCountBase = this.slotsCount;
		__normalizeSlotsCount(this);

        this.runBuild();
        this.selectPage(lx.getFirstDefined(config.activePage, 0));
	}

	#lx:client {
        clientBuild(config) {
            super.clientBuild(config);
            this->toStart.click(self::toFirstPage);
            this->toLeft.click(self::toPrevPage);
            this->toRight.click(self::toNextPage);
            this->toFinish.click(self::toLastPage);

            var middle = this->middle;
            if (middle.childrenCount() > 1) {
                middle.getChildren().forEach((a, i)=>{
                    if (a->text.value() !== '...') a.click(self::onSlotClick);
                });
            }
        }

        static onSlotClick(event) {
            this.ancestor({is:lx.Paginator}).selectPage(+(this->text.value()) - 1);
        }

        static toPrevPage(e) {
            var p = this.parent;
            p.selectPage(p.activePage - 1);
            p.trigger('change', e, p.activePage);
        }

        static toNextPage(e) {
            var p = this.parent;
            p.selectPage(p.activePage + 1);
            p.trigger('change', e, p.activePage);
        }

        static toFirstPage(e) {
            var p = this.parent;
            p.selectPage(0);
            p.trigger('change', e, p.activePage);
        }

        static toLastPage(e) {
            var p = this.parent;
            p.selectPage(p.pagesCount - 1);
            p.trigger('change', e, p.activePage);
        }
    }

    setElementsCount(count) {
        this.elementsCount = count;
        this.pagesCount = Math.ceil(this.elementsCount / this.elementsPerPage);
        __normalizeSlotsCount(this);

        var number = this.activePage;
        this.activePage = -1;
        this.selectPage(number);
    }

    runBuild() {
	    this.streamProportional({direction: lx.HORIZONTAL, width: null});
	    this.begin();
	        //TODO 40px по задумке должны брыться из CSS, но не работает!
            new lx.Box({key: 'toStart', width:'40px', css: this.basicCss.toStart});
            new lx.Box({key: 'toLeft', width:'40px', css: this.basicCss.toLeft});
            new lx.Box({key: 'middle'});
            new lx.Box({key: 'toRight', width:'40px', css: this.basicCss.toRight});
            new lx.Box({key: 'toFinish', width:'40px', css: this.basicCss.toFinish});
        this.end();

        var middle = this->middle;
        middle.stream({
            direction: lx.HORIZONTAL,
            indent: '5px',
            width: null,
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

function __normalizeSlotsCount(self) {
    self.slotsCount = self.slotsCountBase;
    if (self.slotsCount == 1 || self.pagesCount >= 7) return;

    if (self.pagesCount == 6) {
        self.slotsCount = 6;
        return;
    }

    if (self.pagesCount == 5) {
        self.slotsCount = 5;
        return;
    }

    if (self.pagesCount < 5)
        self.slotsCount = 1;
}

function __validatePageNumber(self, number) {
    if (number < 0) number = 0;
    if (number > this.pagesCount - 1) number = self.pagesCount - 1;

    self->toStart.disabled(false);
    self->toLeft.disabled(false);
    self->toRight.disabled(false);
    self->toFinish.disabled(false);

    if (number == 0) {
        self->toStart.disabled(true);
        self->toLeft.disabled(true);
    }

    if (number == self.pagesCount - 1) {
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
        + (self.activePage + 1) + ' '
        + #lx:i18n(lx.Paginator.of) + ' '
        + self.pagesCount
    );
}

function __fillMiddleMin(self) {
    __rebuildMiddle(self);
    __applyMiddleSequence(
        self,
        __calcSequence(self.activePage + 1, self.pagesCount, self.slotsCount)
    );
}

function __fillMiddleMax(self) {
    __rebuildMiddle(self);
    var seq = __calcSequence(self.activePage, self.pagesCount - 2, self.slotsCount - 2);
    seq.forEach((a, i)=>{
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
        c.forEach(a=>{
            a.align(lx.CENTER, lx.MIDDLE);
            a.addClass(self.basicCss.page);
        });
    }    
}

function __applyMiddleSequence(self, seq) {
    var middle = self->middle;
    middle.getChildren().forEach((a, i)=>{
        a.text(seq[i] === null ? '...' : seq[i]);
        a.toggleClassOnCondition(seq[i] - 1 == self.activePage, self.basicCss.active);
        #lx:client {
            if (seq[i] === null) a.off('click');
            else a.click(lx.Paginator.onSlotClick);
        }
    });
}

function __calcSequence(pageNumber, pagesCount, slotsCount) {
    var result = new Array(slotsCount);

    if (pageNumber <= Math.ceil(slotsCount * 0.5)) {
        for (var i=0; i<slotsCount-1; i++) result[i] = i + 1;
        result[slotsCount - 1] = null;
        return result;
    }

    if ((pagesCount - pageNumber) < (slotsCount - 2)) {
        result[0] = null;
        for (var i=1; i<slotsCount; i++) result[i] = pagesCount - slotsCount + i + 1;
        return result;
    }

    result[0] = null;
    result[slotsCount - 1] = null;
    var activeIndex = Math.ceil((slotsCount - 2) * 0.5),
        firstPage = pageNumber - activeIndex;
    for (var i=1; i<slotsCount-1; i++) result[i] = firstPage + i;
    return result;
}
