#lx:private;

let tosts = null;
function initTosts() {
	tosts = lx.Box.rise(document.getElementById('lx-tosts'));
	tosts.key = 'tosts';
	tosts.align({
		indent: '10px',
		subject: 'lx_tost',
		vertical: lx.TOP,
		horizontal: lx.LEFT,
		direction: lx.VERTICAL
	});
}

lx.Tost = function(config, typeArg) {
	if (!config) return;

	if (!tosts) initTosts();

	var message,
		lifetime,
		type;

	if (config.isString || config.isArray) {
		message = config;
		lifetime = lx.Tost.lifetime;
		type = typeArg || lx.Tost._type;
	} else if (config.isObject) {
		message = config.message;
		lifetime = config.lifetime !== undefined ? config.lifetime : lx.Tost.lifetime;
		type = config.type || typeArg || lx.Tost._type;
	} else return;

	if (message && message.isArray) message = message.join(' ');

	if (!message || !message.isString
		|| (type != lx.Tost.TYPE_MESSAGE && type != lx.Tost.TYPE_WARNING && type != lx.Tost.TYPE_ERROR)
	) return;

	var el = new lx.Box({
		parent: tosts,
		key: 'lx_tost',
		text: message
	});

	//todo - перевести на стили
	var color, borderColor;
	switch (type) {
		case lx.Tost.TYPE_MESSAGE:
			color = 'lightgreen';
			borderColor = 'green';
			break;
		case lx.Tost.TYPE_WARNING:
			color = 'orange';
			borderColor = 'lightcoral';
			break;
		case lx.Tost.TYPE_ERROR:
			color = 'lightcoral';
			borderColor = 'red';
			break;
	}
	el.roundCorners('8px');
	el.border({color: borderColor});
	el.fill(color);
	el.style('zIndex', 1000);

	el.width(lx.Tost.widthLimit);
	el.width( el->text.width('px') + 20 + 'px' );
	el.height( el->text.height('px') + 20 + 'px' );
	el.align(lx.CENTER, lx.MIDDLE);
	if (lifetime) setTimeout(function(el) { el.del(); }, lifetime, el);
};

lx.Tost.message = function(msg) { lx.Tost(msg, lx.Tost.TYPE_MESSAGE); };
lx.Tost.warning = function(msg) { lx.Tost(msg, lx.Tost.TYPE_WARNING); };
lx.Tost.error = function(msg)   { lx.Tost(msg, lx.Tost.TYPE_ERROR);   };

lx.Tost.setType = function(type) {
	//todo всякие проверки, может даже тип сеттером обернуть
	this._type = type;
};

lx.Tost.align = function(h, v) {
	var config = h.isObject
		? h
		: {horizontal: h, vertical: v};
	config.subject = 'lx_tost';
	if (config.direction == undefined) config.direction = lx.VERTICAL;
	if (config.indent == undefined) config.indent = '10px';
	if (!tosts) initTosts();
	tosts.align(config);
};

lx.Tost.TYPE_MESSAGE = 1;
lx.Tost.TYPE_WARNING = 2;
lx.Tost.TYPE_ERROR = 3;
lx.Tost.lifetime = 3000;
lx.Tost._type = lx.Tost.TYPE_MESSAGE;
lx.Tost.widthLimit = '40%';
