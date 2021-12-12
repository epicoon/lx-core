const map = {};

class SnippetMap #lx:namespace lx {
	static registerSnippetMaker(name, func) {
		map[name] = func;
	}

	static getSnippetMaker(name) {
		return map[name] || null;
	}
}
