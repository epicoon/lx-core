#lx:module lx.LanguageSwitcher;

#lx:use lx.Dropbox;

/**
 * Language switcher based on lx.Dropbox, keeps language option in Cookies
 *
 * @widget lx.LanguageSwitcher
 * @content-disallowed
 */
#lx:namespace lx;
class LanguageSwitcher extends lx.Dropbox {
	render(config) {
		super.render(config);
		this.options(#lx:php(\lx::$app->language->list));
	}

	#lx:client clientRender(config) {
		super.clientRender(config);
		__actualizeLang(this);
		this.on('change', _handler_onChange);
	}
}

#lx:client {
	function __actualizeLang(self) {
		var lang = lx.app.cookie.get('lang');
		if (lang) {
			self.value(lang);
			return;
		}

		if (self.value() === null) self.select(0);
		lx.app.cookie.set('lang', self.value());
	}

	function _handler_onChange() {
		if (lx.app.cookie.get('lang') == this.value()) return;
		lx.app.cookie.set('lang', this.value());
		location.reload();
	}
}
