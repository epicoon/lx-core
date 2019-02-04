#use lx.Box as Box;

class LogBox extends Box #in lx {
	build(config) {
		super.build(config);

		this.msgConfig = {};

		this.stream();
	}

	decorate(config) {
		this.msgConfig = config;
	}

	log(...args) {
		var config = this.msgConfig;
		config.text = args.join(' ');
		this.add(Box, config);
	}
}
