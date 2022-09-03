const map = {};

#lx:namespace lx;
class SnippetMap extends lx.AppComponent {
	registerSnippetMaker(name, func) {
		map[name] = func;
	}

	getSnippetMaker(name) {
		return map[name] || null;
	}
}
