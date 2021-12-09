let _map = {};

class DepthClusterMap #lx:namespace lx {
	#lx:const
		CLUSTER_DEEP = 0,
		CLUSTER_PRE_MIDDLE = 1,
		CLUSTER_MIDDLE = 2,
		CLUSTER_PRE_FRONT = 3,
		CLUSTER_FRONT = 4,
		CLUSTER_PRE_OVER = 5,
		CLUSTER_OVER = 6,
		CLUSTER_URGENT = 7;

	static calculateZIndex(cluster) {
		return cluster * this.getClusterSize();
	}

	static getClusterSize() {
		return 1000;
	}

	#lx:client {
		static bringToFront(el) {
			var zShift = __zIndex(el);
			var key = 's' + zShift;
			if (_map[key] === undefined) _map[key] = [];

			if (el.__frontIndex !== undefined) {
				if (el.__frontIndex == _map[key].len - 1) return;
				__removeFromFrontMap(el);
			}

			var map = _map[key];

			if (el.getDomElem() && el.getDomElem().offsetParent) {
				el.__frontIndex = map.len;
				el.style('z-index', el.__frontIndex + zShift);
				map.push(el);
			}
		}

		static checkFrontMap() {
			var shown = 0,
				newFrontMap = {};
			for (var key in _map) {
				var map = _map[key];
				var newMap = [];
				for (var i = 0, l = map.len; i < l; i++) {
					if (map[i].getDomElem() && map[i].getDomElem().offsetParent) {
						var elem = map[i];
						elem.__frontIndex = shown;
						elem.style('z-index', map[i].__frontIndex + __zIndex(elem));
						newMap.push(elem);
						shown++;
					}
				}
				newFrontMap[key] = newMap;
			}

			_map = newFrontMap;
		}	
	}
}

#lx:client {
	function __removeFromFrontMap(el) {
		if (el.__frontIndex === undefined) return;

		var zShift = __zIndex(el);
		var key = 's' + zShift;
		if (_map[key] === undefined) _map[key] = [];
		var map = _map[key];

		for (var i = el.__frontIndex + 1, l = map.len; i < l; i++) {
			map[i].__frontIndex = i - 1;
			map[i].style('z-index', i - 1 + zShift);
		}
		map.splice(el.__frontIndex, 1);
	}

	function __zIndex(el) {
		return lx.DepthClusterMap.calculateZIndex(el.getDepthCluster());		
	}
}
