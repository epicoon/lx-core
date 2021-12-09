class TreeConverter #lx:namespace lx {
	treeToJson(tree) {
		var arr = [];
		for (var i=0, l=tree.keys.len; i<l; i++) {
			var key = tree.keys[i];
			__collectJSON(tree.nodes[key], key, -1, arr);
		}

		if (arr.lxEmpty()) return '';
		return JSON.stringify(arr);
	}

	jsonToTree(str) {
		const tree = new lx.Tree();
		if (str == '') return tree;

		var arr = JSON.parse( str ),
			temp = [ tree ];
		for (var i=0, l=arr.length; i<l; i++) {
			var info = arr[i],
				br = temp[ info.root+1 ].add( info.key );

			br.data = info.data;
			br.key = info.key;
			if (info.comment) br.comment = info.comment;
			if (info.fill != undefined) br.fill = info.fill;
			temp.push(br);
		}

		return tree;
	}
}

function __collectJSON(tree, key, root, arr) {
	var index = arr.length;
	var temp = {
		root,
		data: tree.data
	};
	if (tree.comment) temp.comment = tree.comment;
	if (tree.fill) temp.fill = +tree.fill;
	if (key !== '') temp.key = key;

	arr.push(temp);
	for (var i=0, l=tree.keys.len; i<l; i++) {
		var key = tree.keys[i];
		__collectJSON(tree.nodes[key], key, index, arr);
	}
}
