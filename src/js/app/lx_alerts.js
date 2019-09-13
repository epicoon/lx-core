#lx:private;

let alerts = null;
function initAlerts() {
	alerts = lx.Box.rise(lx.WidgetHelper.getAlertsElement());
	alerts.key = 'alerts';
}

lx.Alert = function(msg) {
	if (!alerts) initAlerts();

	if (lx.ActiveBox) __print(msg);
	else {
		request = new lx.Request('', {lx: ['ActiveBox']});
		request.setHeader('lx-type', 'service');
		request.setHeader('lx-service', 'get-widgets');
		request.success = function(result) {
			if (!result) return;
			lx.createAndCallFunction('', result);
			__print(msg);
		};
		request.send();
	}
};

function __print(msg) {
	var el = new lx.ActiveBox({
		parent: alerts,
		geom: [10, 5, 80, 80],
		key: 'lx_alert',
		header: 'Alert'
	});
	el.style('zIndex', 1000);
	el.fill('white');
	el.border();

	el->body.overflow('auto');
	el->body.html(msg);
	el->header.add(lx.Rect, {
		geom: [null, '2px', '20px', '20px', '2px'],
		style: {fill: 'red'},
		click: function() { this.parent.parent.del(); }
	});
}
