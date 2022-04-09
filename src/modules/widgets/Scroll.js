#lx:module lx.Scroll;

#lx:use lx.Box;

#lx:namespace lx;
class Scroll extends lx.Box {
    #lx:const
        DEFAULT_SIZE = '15px';

    getBasicCss() {
        return {
            main: 'lx-Scroll',
            back: 'lx-Scroll-back',
            handleBack: 'lx-Scroll-handle-back',
            handle: 'lx-Scroll-handle'
        };
    }
    
    static initCssAsset(css) {
        let scrollSize = parseInt(this.DEFAULT_SIZE, 10),
            trackPadding = Math.floor(scrollSize / 3),
            scrollBorderRadius = Math.round(scrollSize * 0.5) + 'px',
            scrollTrackPadding = trackPadding + 'px',
            scrollTrackBorderRadius = Math.round((scrollSize - trackPadding * 2) * 0.5) + 'px';
        css.addClass('lx-Scroll', {});
        css.addClass('lx-Scroll-back', {
            backgroundColor: css.preset.widgetIconColor,
            borderRadius: scrollBorderRadius,
            opacity: 0
        });
        css.addStyle('.lx-Scroll:hover .lx-Scroll-back', {
            opacity: 0.2,
            transition: 'opacity 0.3s linear'
        });
        css.addClass('lx-Scroll-handle-back', {
            padding: scrollTrackPadding
        });
        css.addClass('lx-Scroll-handle', {
            width: '100%',
            height: '100%',
            borderRadius: scrollTrackBorderRadius,
            backgroundColor: css.preset.widgetIconColor,
            opacity: 0.3
        });
        css.addStyle('.lx-Scroll-handle-back:hover .lx-Scroll-handle', {
            opacity: 0.6,
            transition: 'opacity 0.3s linear'
        });
    }

    modifyConfigBeforeApply(config) {
        if (config.parent === undefined && config.target)
            config.parent = config.target;
        if (config.target === undefined && config.parent)
            config.target = config.parent;

        config.type = config.type || lx.VERTICAL;
        if (config.type == lx.VERTICAL) {
            config.geom = [null, 0, self::DEFAULT_SIZE, null, 0, self::DEFAULT_SIZE];
        } else {
            config.geom = [0, null, null, self::DEFAULT_SIZE, self::DEFAULT_SIZE, 0];
        }

        return config;
    }

    build(config) {
        if (!config.target || config.target === config.target.getContainer())
            throw 'Unavailable target for the Scroll widget';

        this.type = config.type || lx.VERTICAL;
        this.target = config.target;
        this.target.overflow('hidden');
        this.target.getContainer().overflow('hidden');
        this.add(lx.Box, {key: 'back', geom: true, css: this.basicCss.back});
        let handle = this.add(lx.Box, {
            key: 'handle',
            geom: [0, 0, this.type==lx.VERTICAL?'100%':'50%', this.type==lx.VERTICAL?'50%':'100%'],
            css: this.basicCss.handleBack
        });
        handle.add(lx.Box, {css: this.basicCss.handle});
    }

    #lx:client clientBuild(config) {
        super.clientBuild(config);

        __actualizeHandleSize(this);
        this->handle.move();

        this->handle.on('move', function() {
            if (this.parent.isVertical()) {
                let shift = this.top('px') / (this.parent.height('px') - this.height('px'));
                this.parent.target.scrollTo({yShift: shift});
            } else {
                let shift = this.left('px') / (this.parent.width('px') - this.width('px'));
                this.parent.target.scrollTo({xShift: shift});
            }
        });

        if (this.isVertical()) {
            this.target.on('wheel', (e)=>{
                let pos = this.target.getScrollPos();
                this.target.scrollTo({y: pos.y + e.deltaY});
                __actualizeHandlePos(this);
            });
        }

        this->back.on('mousedown', (e)=>{
            if (e.target !== this->back.getDomElem()) return;
            if (this.isVertical()) {
                let h = this->handle.height('px'),
                    h05 = Math.round(h * 0.5),
                    top = e.offsetY - h05;
                if (top < 0) top = 0;
                else if (top + h > this.height('px')) top = this.height('px') - h;
                this->handle.top(top + 'px');
            } else {
                let w = this->handle.width('px'),
                    w05 = Math.round(w * 0.5),
                    left = e.offsetX - w05;
                if (left < 0) left = 0;
                else if (left + w > this.width('px')) left = this.width('px') - w;
                this->handle.left(left + 'px');
            }
            __actualizeByHandle(this);
        });

        __handler_onResize(this);
        this.target.getContainer().on('contentResize', ()=>__handler_onResize(this));
        this.target.getContainer().on('resize', ()=>__handler_onResize(this));
    }

    #lx:server beforePack() {
        this.target = this.target.renderIndex;
    }

    #lx:client restoreLinks(loader) {
        this.target = loader.getWidget(this.target);
    }

    isVertical() {
        return this.type == lx.VERTICAL;
    }

    #lx:client moveTo(pos) {
        if (this.isVertical())
            this.target.scrollTo({y: pos});
        else
            this.target.scrollTo({x: pos});
        __actualizeHandlePos(this);
    }
}

function __actualizeHandle(self) {
    __actualizeHandleSize(self);
    __actualizeHandlePos(self);
}

function __actualizeHandleSize(self) {
    let c = self.target.getContainer(),
        scrollSize = self.target.getScrollSize();
    if (self.isVertical()) {
        let h = Math.floor((c.height('px') * self.height('px')) / scrollSize.height);
        self->handle.height(h + 'px');
    } else {
        let w = Math.floor((c.width('px') * self.width('px')) / scrollSize.width);
        self->handle.width(w + 'px');
    }
}

function __actualizeHandlePos(self) {
    let scrollSize = self.target.getScrollSize(),
        scrollPos = self.target.getScrollPos();
    if (self.isVertical()) {
        let t = Math.floor((self.height('px') * scrollPos.y) / scrollSize.height);
        self->handle.top(t + 'px');
    } else {
        let w = Math.floor((self.width('px') * scrollPos.x) / scrollSize.width);
        self->handle.left(w + 'px');
    }
}

function __actualizeByHandle(self) {
    let scrollSize = self.target.getScrollSize();
    if (self.isVertical())
        self.target.scrollTo({y: Math.round((self->handle.top('px') * scrollSize.height) / self.height('px'))});
    else
        self.target.scrollTo({x: Math.round((self->handle.left('px') * scrollSize.width) / self.width('px'))});
}

function __handler_onResize(self) {
    let show = self.target.hasOverflow(self.type);
    self.visibility(show);
    if (show) __actualizeHandle(self);
}
