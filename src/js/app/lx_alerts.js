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
		(new lx.ServiceRequest('get-modules', ['lx.ActiveBox'])).send().then((result)=>{
			if (!result) return;
			lx.createAndCallFunction('', result);
			__print(msg);
		});
	}
};

function __print(msg) {
	var el = new lx.ActiveBox({
		parent: alerts,
		geom: [10, 5, 80, 80],
		key: 'lx_alert',
		header: 'Alert',
		closeButton: {click: function(){this.parent.parent.del();}}
	});
	el.style('zIndex', 1000);
	el->body.overflow('auto');
	el->body.html('<pre>' + msg + '</pre>');
}
