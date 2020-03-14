#lx:require lx.js;

#lx:require app/lx;
#lx:require app/lx_start;
#lx:require app/lx_queue;
#lx:require app/lx_storage;
#lx:require app/lx_keyboard;
#lx:require app/lx_movement;
#lx:require app/lx_animation;
#lx:require app/lx_dependencies;
#lx:require app/lx_alerts;
#lx:require app/lx_tost;


lx.entryElement = null;

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

lx.getPlugin = function(name) {
	for (let key in this.plugins) {
		if (this.plugins[key].name == name) return this.plugins[key];
	}
	return null;
};


#lx:require helpers/;
#lx:require components/;
#lx:require -R classes/;
#lx:require tools/;

lx.plugins = new lx.Dict();
