//todo lx.treeSeparator это пздц какой-то

class Tree #lx:namespace lx {
	constructor(...args) {
		this.key = '';
		this.root = null;
		this.nodes = [];
		this.keys = [];
		this.data = {};

		if (!args.length) return;
		for (var i=0; i<args.length; i++) this.add( args[i] );
	}

	static create(treeData, itemsFiled) {
		var counter = 0;
		function re(node, obj) {
			var key = 'k' + (counter++);
			var newNode = node.add(key);
			newNode.data = {};
			for (var i in obj) {
				if (i == itemsFiled) continue;
				newNode.data[i] = obj[i];
			}
			if (obj.url) {
				for (var i in obj) {
					if (i in newNode.data) continue;
					newNode.data[i] = obj[i];
				}
				return;
			}
			if (!obj[itemsFiled]) return;
			obj[itemsFiled].each((a)=> re(newNode, a));
		}

		var result = new this();
		for (var i in treeData) re(result, treeData[i]);
		return result;
	}

	empty() {
		return this.keys.length == 0;
	}

	genKey() {
		return 'node' + this.keys.length;
	}

	homeRoot() {
		if (!this.root) return this;
		var root = this;
		while (root.root) {
			root = root.root;
		}
		return root;
	}

	nthRoot(num) {
		if (num === 0) return this.homeRoot();
		if (!this.root) return null;

		var arr = [],
			root = this;
		while (root.root) {
			arr.push(root);
			root = root.root;
		}

		if (num > arr.length) return null;

		return arr[ arr.length - num ];
	}

	eachNode(func) {
		for (var i=0, l=this.keys.len; i<l; i++) {
			let node = this.getNth(i);
			func(node, i);
		}
	}

	eachNodeRecursive(func) {
		for (var i=0, l=this.keys.len; i<l; i++) {
			let node = this.getNth(i);
			func(node);
			node.eachNodeRecursive(func);
		}
	}

	next() {
		var root = this.root;
		if (!root) return null;

		var index = root.keys.indexOf(this.key);
		if (index == root.keys.len - 1) return null;

		return root.getNth(index + 1);
	}

	prev() {
		var root = this.root;
		if (!root) return null;

		var index = root.keys.indexOf(this.key);
		if (index == 0) return null;

		return root.getNth(index - 1);
	}

	path() {
		var arr = [],
			root = this.root;
		while (root && root.root) {
			arr.push( root.key );
			root = root.root;
		}
		if (!arr.length) return '';
		var path = arr[ arr.length - 1 ];
		for (var i=arr.length-2; i>=0; i--) {
			path += lx.treeSeparator + arr[i];
		}
		return path;
	}

	fullKey() {
		var path = this.path();
		if (path == '') return this.key;
		return path + lx.treeSeparator + this.key;
	}

	deep() {
		var deep = 0,
			root = this.root;
		while (root && root.root) {
			deep++;
			root = root.root;
		}
		return deep;
	}

	info() {
		var arr = [],
			deep = 0,
			root = this.root;
		while (root && root.root) {
			arr.push( root.key );
			deep++;
			root = root.root;
		}
		if (!arr.length) return '';
		var path = arr[ arr.length - 1 ];
		for (var i=arr.length-2; i>=0; i--) {
			path += lx.treeSeparator + arr[i];
		}
		var fullKey = (path == '') ? this.key : path + lx.treeSeparator + this.key;
		return {
			deep: deep,
			path: path,
			fullKey: fullKey
		};
	}

	parseJSON(str) {
		var arr = JSON.parse( str ),
			temp = [ this ];
		for (var i=0, l=arr.length; i<l; i++) {
			var info = arr[i],
				br = temp[ info.root+1 ].add( info.key );

			br.data = info.data;
			br.key = info.key;
			if (info.comment) br.comment = info.comment;
			if (info.fill != undefined) br.fill = info.fill;
			temp.push(br);
		}

		return this;
	}

	add() {
		var args = arguments,
			result = [];

		if (args[0].isArray) args = args[0];

		for (var i=0; i<args.length; i++) {
			var key = ''+args[i],
				arr = key.split(lx.treeSeparator),
				newId = arr.pop(),
				b = this.get( arr );
			if (b == undefined) return null;

			var newBr = new this.constructor();
			newBr.key = newId;
			b.nodes[newId] = newBr;
			b.keys.push(newId);
			newBr.root = b;
			newBr.genKey = b.genKey;
			result.push(newBr);
		}

		if (result.length == 1) return result[0];
		return result;
	}

	del(key) {
		if (key === undefined) {
			if (this.root === null) return this;
			this.root.del(this.key);
			return null;
		}

		var arr = (''+key).split(lx.treeSeparator),
			delId = arr.pop(),
			b = this.get( arr );
		if (b == undefined) return this;

		var index = b.keys.indexOf( delId );
		if (index == -1 ) return this;
		b.keys.splice(index, 1);
		delete b.nodes[delId];
		return this;
	}

	has(key) {
		if (key === '') return true;

		// if ( lx.isNumber(key) ) return this.nodes[ this.keys[key] ];

		var arr = (key.isArray) ? key : (''+key).split(lx.treeSeparator);
		if (!arr.length) return this;
		if ( !(arr[0] in this.nodes) ) return false;
		var b = this.nodes[ arr[0] ];
		for (var i=1; i<arr.length; i++) {
			if ( !(arr[i] in b.nodes) ) return false;
			b = b.nodes[ arr[i] ];
		}
		return true;
	}

	get(key) {
		if (key === '') return this;

		// if ( lx.isNumber(key) ) return this.nodes[ this.keys[key] ];

		var arr = (key.isArray) ? key : (''+key).split(lx.treeSeparator);
		if (!arr.length) return this;
		if ( !(arr[0] in this.nodes) ) return null;
		var b = this.nodes[ arr[0] ];
		for (var i=1; i<arr.length; i++) {
			if ( !(arr[i] in b.nodes) ) return null;
			b = b.nodes[ arr[i] ];
		}
		return b;
	}

	getNth(num) {
		return this.nodes[ this.keys[num] ];
	}

	clear() {
		this.nodes = [];
		this.keys = [];
		return this;
	}
}
