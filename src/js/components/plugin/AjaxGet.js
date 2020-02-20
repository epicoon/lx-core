// Для активных GET-запросов - сами синхронизирутся с url-строкой
class AjaxGet {
	constructor(plugin) {
		this.plugin = plugin;

		this.activeUrl = {};
		this.urlDelimiter = '||';
	}

	registerActiveUrl(key, respondent, handlers, useServer=true) {
		this.activeUrl[key] = {
			state: false,
			useServer,
			respondent,
			handlers
		};

		if (this.plugin.isMainContext()) __checkUrlInAction(this, key);
	}

	request(key, data={}) {
		if (!(key in this.activeUrl)) return;
		__requestProcess(this, key, data);
		if (this.plugin.isMainContext()) __renewLocationHash(this);
	}
}


function __requestProcess(self, key, data={}) {
	var activeUrl = self.activeUrl[key];
	activeUrl.state = data;

	if (activeUrl.useServer) {
		var request = new lx.PluginRequest(self.plugin, activeUrl.respondent, data);
		request.setHandlers(activeUrl.handlers);
		request.send();
	} else {
		activeUrl.handlers(data);
	}
}

function __renewLocationHash(self) {
	var arr = [];

	for (let key in self.activeUrl) {
		let activeUrl = self.activeUrl[key];
		if (activeUrl.state === false) continue;
		let params = lx.Dialog.requestParamsToString(activeUrl.state);
		let fullUrl = params == '' ? key : key + '?' + params;
		arr.push(fullUrl);
	}

	var hash = arr.join(self.urlDelimiter);
	if (hash != '') window.location.hash = hash;
}

function __checkUrlInAction(self, key) {
	var hash = window.location.hash;
	if (hash == '') return;

	hash = hash.substr(1);
	var fullUrls = hash.split(self.urlDelimiter);

	for (var i=0, l=fullUrls.len; i<l; i++) {
		var fullUrl = fullUrls[i],
			urlInfo = fullUrl.split('?'),
			currentUrl = urlInfo[0];

		if (currentUrl != key) continue;

		var data = lx.Dialog.requestParamsFromString(urlInfo[1]);
		__requestProcess(self, key, data);
	}
}
