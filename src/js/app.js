#require app/js_extends;
#require app/lx;
#require app/lx_function;
#require app/lx_start;
#require app/lx_keyboard;
#require app/lx_movement;
#require app/lx_animation;
#require app/lx_widgets;
#require app/lx_tost;


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


#require helpers/;
#require components/;
#require tools/;
