class PluginRequest extends lx.HttpRequest #lx:namespace lx {
	constructor(plugin, respondent, params=[]) {
		params = {
			attributes: plugin.attributes,
			data: params
		};

		super('', params);
		this.setHeader('lx-type', 'plugin');
		this.setHeader('lx-plugin', plugin.name + ((respondent=='') ? '' : (' ' + respondent)));
	}
}
