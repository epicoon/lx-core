#lx:require ../src/loader/;

#lx:namespace lx;
class Loader extends lx.AppComponent {
	loadPlugin(info, el, parent, clientCallback) {
		new lx.Task('loadPlugin', function() {
			var loadContext = new LoadContext();
			loadContext.parseInfo(info);
			loadContext.run(el, parent, ()=>{
				this.setCompleted();
				if (clientCallback) clientCallback();
			});
		});
	}

	loadModules(config) {
		let list = config.modules || [],
			callback = config.callback || null,
			immediately = lx.getFirstDefined(config.immediately, true),
			need = lx.app.dependencies.defineNecessaryModules(list);

		if (need.lxEmpty()) {
			if (immediately) lx.app.dependencies.depend({modules: need});
			if (callback) callback();
		} else {
			let modulesRequest = new lx.ServiceRequest('get-modules', {
				have: lx.app.dependencies.getCurrentModules(),
				need
			});
			let onLoad = function (res) {
				if (!res.success) {
					console.error(res.data);
					return;
				}

				for (let i in res.data.css) {
					var tagRequest = new lx.TagResourceRequest(res.data.css[i], {name: 'module_asset'});
					tagRequest.send();
				}

				lx.app.functionHelper.createAndCallFunction('', res.data.code);
				if (immediately) lx.app.dependencies.depend({modules: need});
				if (callback) callback();
			};

			if (immediately) modulesRequest.send().then(onLoad);
			else {
				modulesRequest.onLoad(onLoad);
				return modulesRequest;
			}
		}

		return null;
	}
}
