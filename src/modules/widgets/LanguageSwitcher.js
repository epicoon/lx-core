#lx:module lx.LanguageSwitcher;

#lx:use lx.Dropbox;

/**
 * Переключатель языка на основе lx.Dropbox, который хранит настройку языка в куках
 */
#lx:namespace lx;
class LanguageSwitcher extends lx.Dropbox {
	build(config) {
		super.build(config);
		this.options(#lx:php(\lx::$app->language->list));
	}

	#lx:client clientBuild(config) {
		super.clientBuild(config);
		__actualizeLang(this);
		this.on('change', _handler_onChange);
	}
}

#lx:client {
	function __actualizeLang(self) {
		var lang = lx.Cookie.get('lang');
		if (lang) {
			self.value(lang);
			return;
		}

		if (self.value() === null) self.select(0);
		lx.Cookie.set('lang', self.value());
	}

	function _handler_onChange() {
		if (lx.Cookie.get('lang') == this.value()) return;
		lx.Cookie.set('lang', this.value());
		location.reload();
	}
}
