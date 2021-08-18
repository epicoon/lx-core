#lx:module lx.JointMover;

#lx:use lx.Rect;

//TODO не тестировался при создании на сервере
class JointMover extends lx.Rect #lx:namespace lx {
    #lx:const DEFAULT_SIZE = '6px';

    modifyConfigBeforeApply(config) {
        if (config.top) {
            config.geom = [
                0,
                config.top,
                100,
                config.size || config.height || self::DEFAULT_SIZE
            ];
            config.direction = lx.VERTICAL;
            delete config.top;
        } else if (config.left) {
            config.geom = [
                config.left,
                0,
                100,
                config.size || config.width || self::DEFAULT_SIZE
            ];
            config.direction = lx.HORIZONTAL;
            delete config.left;
        }
        return config;
    }

    build(config) {
        super.build(config);

        this.direction = config.direction || null;
        this.limit = config.limit || 20;
        if (this.direction) {
            if (this.direction == lx.VERTICAL) this.style('cursor', 'ns-resize');
            else this.style('cursor', 'ew-resize');
        }
        this.move();
        this.on('move', function() {
            var prevLim = this.getPrevLimit(),
                nextLim = this.getNextLimit();
            if (this.getDirection() == lx.VERTICAL) {
                if (this.top('px') < prevLim)
                    this.top(prevLim + 'px');
                if (this.top('px') + this.height('px') > nextLim)
                    this.top(nextLim - this.height('px') + 'px');
            } else {
                if (this.left('px') < prevLim)
                    this.left(prevLim + 'px');
                if (this.left('px') + this.width('px') > nextLim)
                    this.left(nextLim - this.width('px') + 'px');
            }
            this.actualize();
        });

        var context = this;
        this.parent.on('afterAddChild', function(child) {
            context.check(child);
        });
    }

    clientBuild(config) {
        super.clientBuild(config);
        this.actualize();

        this.parent.map('%');
        this.parent.on('resize', ()=>this.actualize());
        this.displayOnce(()=>{
            var next = this.nextSibling();
            if (!next) return;
            var context = this;
            next.displayOnce(()=>context.actualize());
        });
    }

    getDirection() {
        if (this.direction) return this.direction;
        if (!this.width('px') || !this.height('px')) return;

        this.direction = this.width('px') > this.height('px')
            ? lx.VERTICAL
            : lx.HORIZONTAL;
        if (this.direction == lx.VERTICAL) this.style('cursor', 'ns-resize');
        else this.style('cursor', 'ew-resize');
        return this.direction;
    }

    check(elem) {
        if (this.width('px') === null) return;
        if (elem === this.prevSibling())
            this.actualizePrev(elem);
        else if (elem === this.nextSibling())
            this.actualizeNext(elem);
    }

    actualize() {
        if (this.width('px') === null) return;
        this.actualizePrev(this.prevSibling());
        this.actualizeNext(this.nextSibling());
    }

    actualizePrev(elem = null) {
        if (this.width('px') === null) return;
        if (elem === null) elem = this.prevSibling();
        if (elem === undefined) return;

        if (this.getDirection() == lx.VERTICAL) {
            elem.setGeomPriority(lx.TOP, lx.HEIGHT);
            elem.setGeom([0, 0, null, this.top('px') - elem.top('px') + 'px', 0]);
        } else {
            elem.setGeomPriority(lx.LEFT, lx.WIDTH);
            elem.setGeom([0, 0, this.left('px') - elem.left('px') + 'px', null, null, 0]);
        }
    }

    actualizeNext(elem = null) {
        if (this.width('px') === null) return;
        if (elem === null) elem = this.nextSibling();
        if (elem === undefined) return;

        if (this.getDirection() == lx.VERTICAL) {
            var newH = elem.height('px') + elem.top('px') - (this.top('px') + this.height('px')) + 'px';
            elem.setGeomPriority(lx.TOP, lx.HEIGHT);
            elem.setGeom([0, this.top('px') + this.height('px') + 'px', null, newH, 0]);
        } else {
            var newW = elem.width('px') + elem.left('px') - (this.left('px') + this.width('px')) + 'px';
            elem.setGeomPriority(lx.LEFT, lx.WIDTH);
            elem.setGeom([this.left('px') + this.width('px') + 'px', 0, newW, null, null, 0]);
        }
    }

    getPrevLimit() {
        var match = false,
            prev = this.prevSibling();
        while (prev && !match) {
            if (prev)
                if (prev.is(lx.JointMover)) match = true;
                else prev = prev.prevSibling();
        }

        if (prev)
            return this.getDirection() == lx.VERTICAL
                ? prev.top('px') + prev.height('px') + this.limit
                : prev.left('px') + prev.width('px') + this.limit;

        return this.limit;
    }

    getNextLimit() {
        var match = false,
            next = this.nextSibling();
        while (next && !match) {
            if (next)
                if (next.is(lx.JointMover)) match = true;
                else next = next.nextSibling();
        }

        if (next)
            return this.getDirection() == lx.VERTICAL
                ? next.top('px') - this.limit
                : next.left('px') - this.limit;

        return this.getDirection() == lx.VERTICAL
            ? this.parent.height('px') - this.limit
            : this.parent.width('px') - this.limit;
    }
}
