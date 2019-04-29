#lx:require app/js_extends;
#lx:require app/lx;
#lx:require app/lx_function;
#lx:require app/lx_start;
#lx:require app/lx_storage;
#lx:require app/lx_keyboard;
#lx:require app/lx_movement;
#lx:require app/lx_animation;
#lx:require app/lx_widgets;
#lx:require app/lx_alerts;
#lx:require app/lx_tost;


lx.entryElement = null;

lx.LEFT = 1;
lx.CENTER = 2;
lx.WIDTH = 2;
lx.RIGHT = 3;
lx.JUSTIFY = 4;
lx.TOP = 5;
lx.MIDDLE = 6;
lx.HEIGHT = 6;
lx.BOTTOM = 7;
lx.VERTICAL = 1;
lx.HORIZONTAL = 2;

lx.POSTUNPACK_TYPE_IMMEDIATLY = 1;
lx.POSTUNPACK_TYPE_FIRST_DISPLAY = 2;
lx.POSTUNPACK_TYPE_ALL_DISPLAY = 3;
lx.unpackType = lx.POSTUNPACK_TYPE_FIRST_DISPLAY;


/**
 * Правильная последовательность \r\n, коды соответственно 13 и 10
 * */
Object.defineProperty(lx, "EOL", {
	get: function() { return String.fromCharCode(13) + String.fromCharCode(10); }
});

lx.on = function(eventName, func) {
	this.Event.add( document, eventName, func );
};

lx.off = function(eventName, func) {
	this.Event.remove( document, eventName, func );
};

lx.getModule = function(name) {
	for (let key in this.modules) {
		if (this.modules[key].name == name) return this.modules[key];
	}
	return null;
};

lx.createNamespace = function(namespace) {
	var arr = namespace.split(/[.\\]/),
		temp = window;
	for (var i=0, l=arr.length; i<l; i++) {
		if (temp[arr[i]] === undefined) temp[arr[i]] = {};
		temp = temp[arr[i]];
	}
};

lx.getNamespace = function(namespace) {
	var arr = namespace.split(/[.\\]/),
		temp = window;
	for (var i=0, l=arr.length; i<l; i++) {
		if (temp[arr[i]] === undefined) return null;
		temp = temp[arr[i]];
	}
	return temp;
};

lx.createObject = function(fullClassName, args = null) {
	var arr = fullClassName.split(/[.\\]/),
		temp = window;
	for (var i=0, l=arr.length; i<l; i++) {
		if (temp[arr[i]] === undefined) return null;

		if (i == l - 1) {
			if (args) {
				return new (Function.prototype.bind.apply(
					temp[arr[i]],
					[null].lxMerge(args)
				));
			} else return new temp[arr[i]]();
		} else temp = temp[arr[i]];
	}

	return null;
};


#lx:require helpers/;
#lx:require components/;
#lx:require -R classes/;
#lx:require tools/;

lx.modules = new lx.Dict();
