const map = {};

#lx:namespace lx;
class SnippetMap {
	static registerSnippetMaker(name, func) {
		map[name] = func;
	}

	static getSnippetMaker(name) {
		return map[name] || null;
	}
}
