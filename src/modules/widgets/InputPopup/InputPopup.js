#lx:module lx.InputPopup;
#lx:module-data {
    i18n: i18n.yaml
};

#lx:use lx.Box;
#lx:use lx.Button;
#lx:use lx.Input;

#lx:client {
	function __getHolder() {
		let holder = {
			_confirmCallback: null,
			_rejectCallback: null,
		};
		holder.confirm = function(callback) {
			this._confirmCallback = callback;
			return this;
		}
		holder.reject = function(callback) {
			this._rejectCallback = callback;
			return this;
		}
		return holder;
	}

	let __instance = null,
		__active = null;
}

/**
 * @widget lx.InputPopup
 * @content-disallowed
 */
#lx:namespace lx;
class InputPopup extends lx.Box {
    modifyConfigBeforeApply(config) {
    	config.key = config.key || 'inputPopup';
		config.geom = config.geom || ['0%', '0%', '100%', '100%'];
		config.depthCluster = lx.DepthClusterMap.CLUSTER_OVER;

		//TODO ???
		// style: { position: 'fixed' }

        return config;
    }

	getBasicCss() {
		return {
			back: 'lx-InputPopup-back',
		}
	}

	static initCss(css) {
		css.addClass('lx-InputPopup-back', {
			backgroundColor: css.preset.bodyBackgroundColor,
			borderRadius: css.preset.borderRadius,
			border: 'solid 1px ' + css.preset.widgetBorderColor,
		});
	}

    #lx:client {
		clientRender(config) {
			this.holder = __getHolder();
			__render(this);
		}

		static open(captions, defaults = {}) {
			if (!__instance)
				__instance = new lx.InputPopup({parent: lx.body});
			return __instance.open(captions, defaults);
		}

		static close() {
			if (__instance)
				__close(__instance);
		}

	    open(captions, defaults = {}) {
			if (!lx.isArray(captions)) captions = [captions];

			let buttons = this->stream->buttons;

			this->stream.del('r');
			this.useRenderCache();
			captions.forEach(caption=>{
				var row = new lx.Box({
					key: 'r',
					before: buttons
				});
				row.gridProportional({ step: '10px', cols: 2 });

				var textBox = row.add(lx.Box, {
					text : caption,
					width: 1
				});
				textBox.align(lx.CENTER, lx.MIDDLE);
				var input = row.add(lx.Input, {
					key: 'input',
					width: 1
				});
				if (defaults[caption] !== undefined) input.value(defaults[caption]);

				row.height( textBox->text.height('px') + 10 + 'px' );
			});
			this.applyRenderCache();

			var top = (this.height('px') - this->stream.height('px')) * 0.5;
			if (top < 0) top = 0;
			this->stream.top(top + 'px');

			__active = this;
			this.show();

			lx.app.keyboard.onKeydown(13, __onConfirm);
			lx.app.keyboard.onKeydown(27, __onReject);

			var rows = this->stream->r;
			if (lx.isArray(rows)) rows[0]->input.focus();
			else rows->input.focus();

			return this.holder;
	    }

	    close() {
	    	__close(this);
	    }
    }
}

#lx:client {
	function __render(self) {
		self.overflow('auto');
		self.useRenderCache();
		self.begin();
		__renderContent(self);
		self.end();
		self.applyRenderCache();
		self.hide();
	}

	function __renderContent(self) {
		(new lx.Rect({geom:true})).fill('black').opacity(0.5);

		var inputPopupStream = new lx.Box({key:'stream', geom:['30%', '40%', '40%', '0%'], css:self.basicCss.back});
		inputPopupStream.stream({indent:'10px'});

		inputPopupStream.begin();
			var buttons = new lx.Box({key:'buttons', height:'35px'});
			buttons.grid({step:'10px',cols:2});
			new lx.Button({parent:buttons, key:'confirm', width:1, text:#lx:i18n(OK)});
			new lx.Button({parent:buttons, key:'reject', width:1, text:#lx:i18n(Close)});
		inputPopupStream.end();

		inputPopupStream->>confirm.click(__onConfirm);
		inputPopupStream->>reject.click(__onReject);
	}

	function __onConfirm() {
		if (!__active) return;
		let callback = __active.holder._confirmCallback;
		if (callback) {
			let values = [];
			if (__active->stream.contains('r')) {
				let rows = __active->stream->r;
				if (rows) {
					if (!lx.isArray(rows)) rows = [rows];
					rows.forEach(a=>values.push(a->input.value()));
				}
			}
			if (values.len == 1) values = values[0];
			if (lx.isFunction(callback)) callback(values);
			else if (lx.isArray(callback))
				callback[1].call(callback[0], values);
		} 
		__close(__active);
	}

	function __onReject() {
		if (!__active) return;
		let callback = __active.holder._rejectCallback;
		if (callback) {
			if (lx.isFunction(callback)) callback();
			else if (lx.isArray(callback))
				callback[1].call(callback[0]);
		} 
		__close(__active);
	}

	function __close(popup) {
		popup.hide();
		popup.holder._confirmCallback = null;
		popup.holder._rejectCallback = null;
		lx.app.keyboard.offKeydown(13, __onConfirm);
		lx.app.keyboard.offKeydown(27, __onReject);
	}
}
