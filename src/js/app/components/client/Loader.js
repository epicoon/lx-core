#lx:require ../src/loader/;

#lx:namespace lx;
class Loader extends lx.AppComponent {
	loadPlugin(info, el, parent, clientCallback) {
		new lx.Task('loadPlugin', function() {
			let loadContext = new LoadContext();
			loadContext.parseInfo(info);
			loadContext.run(el, parent, ()=>{
				this.setCompleted();
				if (clientCallback) clientCallback();
			});
		});
	}

	/**
	 * @param config {Object: {
	 *     modules {Array<String>},
	 *     [callback] {Function},
	 *     [immediately: true] {Boolean},
	 *     [host] {String}
	 * }}
	 * @return {lx.ServiceRequest|null}
	 */
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
			if (config.host) modulesRequest.host = config.host;
			let onLoad = function (res) {
				if (!res.success) {
					console.error(res.data);
					return;
				}

				let necessaryCss = lx.app.dependencies.defineNecessaryCss(res.data.css);
				for (let i in necessaryCss) {
					var tagRequest = new lx.TagResourceRequest(
						necessaryCss[i],
						{name: 'module_asset'},
						'head-top'
					);
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
