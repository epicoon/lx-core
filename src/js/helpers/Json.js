lx.Json = {
	// Чтобы можно было парсить многострочники и табы
	parse: function(str) {
		var caret = String.fromCharCode(92) + String.fromCharCode(110),
			tab = String.fromCharCode(92) + String.fromCharCode(116);
		str = str.replace(/\n/g, caret);
		str = str.replace(/\t/g, tab);
		return JSON.parse(str);
	}
};
