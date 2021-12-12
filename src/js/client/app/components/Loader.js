#lx:require loader/;

lx.Loader = {
	run: function(info, el, parent, clientCallback) {
		new lx.Task('loadPlugin', function() {
			var loadContext = new LoadContext();
			loadContext.parseInfo(info);
			loadContext.run(el, parent, ()=>{
				this.setCompleted();
				if (clientCallback) clientCallback();
			});
		});
	}
};
