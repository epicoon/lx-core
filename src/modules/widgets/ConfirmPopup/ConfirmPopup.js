#lx:module lx.ConfirmPopup;
#lx:module-data {
    i18n: i18n.yaml
};

#lx:use lx.Box;
#lx:use lx.Button;

#lx:client {
	function __getHolder(popup) {
		let holder = {
			_popup: popup,
			_confirmCallback: null,
			_rejectCallback: null,
			_extraButtons: null,
			_extraCallbacks: {}
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

	function __applyExtraButtons(holder, extraButtons, colsCount) {
		if (extraButtons.lxEmpty()) return;

		holder._extraButtons = extraButtons;
		let buttonsWrapper = holder._popup->>buttons;
		buttonsWrapper.positioning().setCols(colsCount);
		for (let name in extraButtons) {
			let text = extraButtons[name];
			buttonsWrapper.add(lx.Button, {key:'extra', width:1, text, click:()=>__onExtra(holder, name)});
			holder[name] = function(callback) {
				this._extraCallbacks[name] = callback;
				return this;
			};
		}
	}
	function __clearExtraButtons(holder) {
		if (holder._extraButtons === null) return;

		let buttonsWrapper = holder._popup->>buttons;
		buttonsWrapper.del('extra');
		buttonsWrapper.positioning().setCols(2);

		for (let name in holder._extraButtons)
			delete holder[name];
		holder._extraButtons = null;
		holder._extraCallbacks = {};
	}

	let __instance = null,
		__active = null;
}

/**
 * @widget lx.ConfirmPopup
 * @content-disallowed
 */
#lx:namespace lx;
class ConfirmPopup extends lx.Box {
	#lx:const COLS_FOR_EXTRA_BUTTONS = 1;

    modifyConfigBeforeApply(config) {
    	config.key = config.key || 'confirmPopup';
		config.geom = config.geom || ['0%', '0%', '100%', '100%'];
		config.depthCluster = lx.DepthClusterMap.CLUSTER_OVER;

		//TODO ???
		// style: { position: 'fixed' }

        return config;
    }

	getBasicCss() {
		return {
			back: 'lx-ConfirmPopup-back',
		}
	}

	static initCss(css) {
		css.addClass('lx-ConfirmPopup-back', {
			backgroundColor: css.preset.bodyBackgroundColor,
			borderRadius: css.preset.borderRadius,
			border: 'solid 1px ' + css.preset.widgetBorderColor,
		});
	}

	/**
	 * @param [config] {Object: {
	 *     #merge(lx.Box::render::config),
	 *     [customButtons = false] {Boolean}
	 * }}
	 */
	render(config) {
		if (config.customButtons !== undefined)
			this.customButtons = config.customButtons;
	}

    #lx:client {
		clientRender(config) {
			this.holder = __getHolder(this);
			__render(this);
		}

		static open(message, extraButtons = {}, buttonColsCount = 2) {
			if (!__instance)
				__instance = new lx.ConfirmPopup({parent: lx.body});
			return __instance.open(message, extraButtons, buttonColsCount);
		}

		static close() {
			if (__instance)
				__close(__instance);
		}

	    open(message, extraButtons = {}, buttonColsCount = 2) {
			this->stream->message.text(message);
			this->stream->message.height(
				this->stream->message->text.height('px') + 10 + 'px'
			);

			var top = (this.height('px') - this->stream.height('px')) * 0.5;
			if (top < 0) top = 0;
			this->stream.top(top + 'px');

			__active = this;
			this.show();

			lx.app.keyboard.onKeydown(13, __onConfirm);
			lx.app.keyboard.onKeydown(27, __onReject);
			__applyExtraButtons(this.holder, extraButtons, buttonColsCount);
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

		var confirmPopupStream = new lx.Box({key:'stream', geom:['30%', '40%', '40%', '0%'], css:self.basicCss.back});
		confirmPopupStream.stream({indent:'10px'});

		confirmPopupStream.begin();
			(new lx.Box({key:'message'})).align(lx.CENTER, lx.MIDDLE);

			var buttons = new lx.Box({key:'buttons', height:'35px'});
			buttons.grid({step:'10px', cols:2});

			if (!self.customButtons) {
				new lx.Button({parent:buttons, key:'confirm', width:1, text:#lx:i18n(Yes)});
				new lx.Button({parent:buttons, key:'reject', width:1, text:#lx:i18n(No)});
				confirmPopupStream->>confirm.click(__onConfirm);
				confirmPopupStream->>reject.click(__onReject);
			}
		confirmPopupStream.end();
	}

	function __onConfirm() {
		if (!__active) return;
		let callback = __active.holder._confirmCallback;
		if (callback) {
			if (lx.isFunction(callback)) callback();
			else if (lx.isArray(callback))
				callback[1].call(callback[0]);
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

	function __onExtra(holder, name) {
		let callback = holder._extraCallbacks[name];
		if (callback) {
			if (lx.isFunction(callback)) callback();
			else if (lx.isArray(callback))
				callback[1].call(callback[0]);
		} 
		__close(holder._popup);
	}

	function __close(popup) {
		if (!__active) return;
		popup.hide();
		popup.holder._confirmCallback = null;
		popup.holder._rejectCallback = null;
		__clearExtraButtons(popup.holder);
		lx.app.keyboard.offKeydown(13, __onConfirm);
		lx.app.keyboard.offKeydown(27, __onReject);
		__active = null;
	}
}
