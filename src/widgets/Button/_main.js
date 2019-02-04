class Button extends lx.Box #in lx {
	postBuild(config) {
		super.postBuild(config);
		this.align(lx.CENTER, lx.MIDDLE);
		this.on('mousedown', lx.Event.preventDefault);
	}
}
