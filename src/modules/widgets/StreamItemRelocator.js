#lx:module lx.StreamItemRelocator;

#lx:use lx.Box;

/**
 * @widget lx.StreamItemRelocator
 * @content-disallowed
 */
#lx:namespace lx;
class StreamItemRelocator extends lx.Box {
    clientBuild(config) {
        this.item = null;
        this.__env = {
            width: null,
            height: null,
            next: null,
            parent: null,
            holder: null,
            depthCluster: null,
            list: []
        };
        this.on('mousedown', ()=>__onMouseDown(this));
    }
}

#lx:client {
    function __onMouseDown(self) {
        let item = __getItem(self);
        if (!item) return;

        item.parent.trigger('beforeStreamContentRelocation');
        item.trigger('beforeStreamItemRelocation');

        let config = {
            key: '__holder',
            width: item.width('px') + 'px',
            height: item.height('px') + 'px'
        };
        self.__env.zIndex = item.style('z-index');
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
            boxData = null;
        for (let i=0, l=self.__env.list.len; i<l; i++) {
            let data = self.__env.list[i];
            if (top >= data.top && top <= data.bottom) {
                boxData = data;
                break;
            }
        }

        if (!boxData || boxData.elem.key == '__holder') return;

        let next = null, prev = null;
        if (top <= boxData.point) {
            if (boxData.elem.prevSibling() && boxData.elem.prevSibling().key == '__holder') return;
            next = boxData.elem;
        } else {
            if (boxData.elem.nextSibling() && boxData.elem.nextSibling().key == '__holder') return;
            next = boxData.elem.nextSibling();
            prev = boxData.elem;
        }

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
        item.depthCluster = self.__env.depthCluster;

        self.__env.holder.del();
        self.__env = {
            width: null,
            height: null,
            next: null,
            parent: null,
            holder: null,
            depthCluster: null,
            list: []
        };

        item.off('moveEnd');
        item.off('move');
        item.move(false);
        item.trigger('afterStreamItemRelocation');
        item.parent.trigger('afterStreamContentRelocation');
    }

    function __getItem(self) {
        if (self.item) return self.item;

        let parent = self.parent;
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
