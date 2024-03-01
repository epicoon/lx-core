#lx:module lx.StreamItemRelocator;

#lx:use lx.Box;

/**
 * @widget lx.StreamItemRelocator
 * @content-disallowed
 *
 * @events [
 *     beforeStreamItemRelocation,
 *     afterStreamItemRelocation
 * ]
 */
#lx:namespace lx;
class StreamItemRelocator extends lx.Box {
    clientRender(config) {
        this.item = null;
        this.__env = {
            width: null,
            height: null,
            next: null,
            parent: null,
            holder: null,
            depthCluster: null,
            baseIndex: null,
            list: []
        };
        this.on('mousedown', ()=>__onMouseDown(this));
    }
}

#lx:client {
    function __onMouseDown(self) {
        let item = __getItem(self);
        if (!item) return;

        self.trigger('beforeStreamItemRelocation');
        item.parent.trigger('beforeStreamItemRelocation');
        if (item !== self)
            item.trigger('beforeStreamItemRelocation');

        let config = {
            key: '__holder',
            width: item.width('px') + 'px',
            height: item.height('px') + 'px'
        };
        self.__env.zIndex = item.style('z-index');
        self.__env.baseIndex = item.index;
        self.__env.width = item.width();
        self.__env.height = item.height();
        self.__env.next = item.nextSibling();
        self.__env.parent = item.parent;
        if (self.__env.next)
            config.before = self.__env.next;
        else config.parent = item.parent;

        let geom = item.getGlobalRect();
        item.setParent(lx.body);
        item.style('position', 'absolute');
        item.depthCluster = lx.DepthClusterMap.CLUSTER_URGENT;

        item.left(geom.left + 'px');
        item.top(geom.top + 'px');
        item.width(geom.width + 'px');
        item.height(geom.height + 'px');
        item.move();
        item.__sir = self;
        item.on('moveEnd', ()=>__onMoveEnd(self));
        item.on('move', ()=>__onMove(self));

        self.__env.holder = new lx.Box(config);
        __calcList(self);
    }

    function __onMove(self) {
        let item = __getItem(self);
        if (!item) return;

        let top = item.top('px'),
            height = item.height('px'),
            middle = height * 0.5 + top,
            boxData = null;

        for (let i=0, l=self.__env.list.len; i<l; i++) {
            let data = self.__env.list[i];
            if (middle >= data.top && middle <= data.bottom) {
                boxData = data;
                break;
            }
        }

        if (!boxData || boxData.elem.key == '__holder') return;

        let direction = (top > self.__env.holder.top('px'))
            ? lx.BOTTOM
            : lx.TOP;

        let next = null, prev = null;
        if (direction == lx.BOTTOM) prev = boxData.elem;
        else next = boxData.elem;

        let config = next
            ? { before: next }
            : { after: prev };

        self.__env.holder.setParent(config);
        self.__env.holder.width(item.width('px') + 'px');
        self.__env.holder.height(item.height('px') + 'px');
        self.__env.next = self.__env.holder.nextSibling();
        __calcList(self);
    }

    function __onMoveEnd(self) {
        let item = __getItem(self);
        if (!item) return;

        item.style('position', 'relative');
        let config = self.__env.next
            ? { before: self.__env.next }
            : { parent: self.__env.parent };
        item.left(null);
        item.top(null);
        item.setParent(config);
        item.width(self.__env.width);
        item.height(self.__env.height);
        item.setDepthCluster(self.__env.depthCluster);

        let needTrigger = (self.__env.baseIndex != item.index);
        self.__env.holder.del();
        self.__env = {
            width: null,
            height: null,
            next: null,
            parent: null,
            holder: null,
            depthCluster: null,
            baseIndex: null,
            list: []
        };

        item.off('moveEnd');
        item.off('move');
        item.move(false);
        if (needTrigger) {
            self.trigger('afterStreamItemRelocation');
            item.parent.trigger('afterStreamItemRelocation');
            if (item !== self)
                item.trigger('afterStreamItemRelocation');
        }
    }

    function __getItem(self) {
        if (self.item) return self.item;

        let parent = self;
        while (parent) {
            if (parent.parent && parent.parent.positioning().lxClassName() == 'StreamPositioningStrategy') {
                self.item = parent;
                return self.item;
            }

            parent = parent.parent;
        }
        return null;
    }

    function __calcList(self) {
        let stream = self.__env.parent;
        self.__env.list = [];
        stream.getChildren().forEach(e=>{
            if (e.isDisplay()) {
                e.getGlobalRect();
                let rect = e.getGlobalRect();
                //TODO VERTICAL only, to do for horizontal stream
                self.__env.list.push({
                    top: rect.top,
                    bottom: rect.top + rect.height,
                    point: rect.top + rect.height * 0.33,
                    elem: e
                });
            }
        });
    }
}
