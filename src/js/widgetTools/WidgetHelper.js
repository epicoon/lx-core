class WidgetHelper {
	#lx:const
		LXID_ALERTS = lx\WidgetHelper::LXID_ALERTS,
		LXID_TOSTS = lx\WidgetHelper::LXID_TOSTS,
		LXID_BODY = lx\WidgetHelper::LXID_BODY;

	getBodyElement() {
		return __getElementByLxId(self::LXID_BODY);
	}

	getAlertsElement() {
		return __getElementByLxId(self::LXID_ALERTS);
	}

	getTostsElement() {
		return __getElementByLxId(self::LXID_TOSTS);
	}
}

function __getElementByLxId(id, parent = null) {
	var elem = parent ? parent.getDomElem() : null;
	if (elem) return elem.querySelector("[lxid^='" + id + "']");
	return document.querySelector("[lxid^='" + id + "']");
}

lx.WidgetHelper = new WidgetHelper();
