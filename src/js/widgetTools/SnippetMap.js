const map = {};

class SnippetMap {
	static registerSnippetMaker(name, func) {
		map[name] = func;
	}

	static getSnippetMaker(name) {
		return map[name] || null;
	}
}

lx.SnippetMap = SnippetMap;
