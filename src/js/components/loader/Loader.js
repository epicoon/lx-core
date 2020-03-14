#lx:private;

#lx:require SnippetLoader;
#lx:require SnippetJsNode;
#lx:require LoadContext;

lx.Loader = {
	run: function(info, el, parent, clientCallback) {
		var task = new lx.Task();
		var loadContext = new LoadContext(task);
		task.setCallback(()=>{
			loadContext.parseInfo(info);
			loadContext.run(el, parent, clientCallback);
		});
		task.setQueue('loadPlugin');
	}
};
