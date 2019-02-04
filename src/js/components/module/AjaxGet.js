// Для активных GET-запросов - сами синхронизирутся с url-строкой
class AjaxGet {
	constructor(module) {
		this.module = module;

		this.activeUrl = {};
		this.urlDelimiter = '||';
	}

	registerActiveUrl(url, handlers, useServer=true) {
		this.activeUrl[url] = {
			state: false,
			useServer: useServer,
			handlers: handlers
		}

		checkUrlInAction(this, url);
	}

	request(url, data={}) {
		if (!(url in this.activeUrl)) return;
		requestProcess(this, url, data);
		renewLocationHash(this);
	}
}


function requestProcess(ctx, url, data={}) {
	var activeUrl = ctx.activeUrl[url];
	activeUrl.state = data;

	if (activeUrl.useServer) {
		var request = new lx.Request(url, data);
		request.setMethod('get');
		request.setModule(ctx.module);
		request.setHandlers(activeUrl.handlers);
		request.send();
	} else {
		activeUrl.handlers(data);
	}
}

function renewLocationHash(ctx) {
	var arr = [];

	for (let url in ctx.activeUrl) {
		let activeUrl = ctx.activeUrl[url];
		if (activeUrl.state === false) continue;

		let params = lx.Dialog.requestParamsToString(activeUrl.state);
		let fullUrl = params == '' ? url : url + '?' + params;
		arr.push(fullUrl);
	}

	var hash = arr.join(ctx.urlDelimiter);
	if (hash != '') window.location.hash = hash;
}

function checkUrlInAction(ctx, url) {
	var hash = window.location.hash;
	if (hash == '') return;

	hash = hash.substr(1);
	var fullUrls = hash.split(ctx.urlDelimiter);

	for (var i=0, l=fullUrls.len; i<l; i++) {
		var fullUrl = fullUrls[i],
			urlInfo = fullUrl.split('?'),
			currentUrl = urlInfo[0];

		if (currentUrl != url) continue;

		var data = lx.Dialog.requestParamsFromString(urlInfo[1]);
		requestProcess(ctx, url, data);
	}
}
