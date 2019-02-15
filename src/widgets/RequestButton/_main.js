#lx:use lx.Button as Button;

class RequestButton extends Button #lx:namespace lx {
	build(config) {
		super.build(config);

		// имя скрипта на стороне сервера, которому отправляется запрос
		this.respondent = config.respondent;

		// функция на ответ, или массив: [контекст, функция]
		this.onResponse = config.onResponse || null;

		// массив наименований полей формы, которые будут отправлены этой кнопкой
		this.fields = config.fields || null;
	}

	postBuild(config) {
		super.postBuild(config);
		this.click(self::onclick);
	}

	postUnpack(config) {
		super.postUnpack(config);
		if (this.onResponse) this.onResponse = this.unpackFunction(this.onResponse);
	}

	static onclick(e) {

		console.log(  this.getFormContent()  );
		return;

		this.getModule().request(
			this.respondent,
			this.getFormContent(),
			this.onResponse
		);
	}

	getFormContent() {
		var form = this.ancestor({is: Form});
		if (!form) return [];
		return form.content(this.fields);
	}
}