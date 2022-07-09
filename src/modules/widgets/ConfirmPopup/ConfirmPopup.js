#lx:module lx.ConfirmPopup;
#lx:module-data {
    i18n: i18n.yaml
};

#lx:use lx.Box;
#lx:use lx.Button;

#lx:client {
	let instance = null,
		_confirmCallback = null,
		_rejectCallback = null,
		_extraButtons = null,
		_extraCallbacks = {},
		callbackHolder = {
			confirm: function(callback) {
				_confirmCallback = callback;
				return this;
			},
			reject: function(callback) {
				_rejectCallback = callback;
				return this;
			}
		};

	function __applyExtraButtons(extraButtons, colsWithExtra) {
		if (extraButtons.lxEmpty()) return;

		_extraButtons = extraButtons;
		let buttonsWrapper = __getInstance()->>buttons;
		buttonsWrapper.positioning().setCols(colsWithExtra);
		for (let name in extraButtons) {
			let text = extraButtons[name];
			buttonsWrapper.add(lx.Button, {key:'extra', width:1, text, click:()=>__onExtra(name)});
			callbackHolder[name] = function(callback) {
				_extraCallbacks[name] = callback;
				return this;
			};
		}
	}

	function __clearExtraButtons() {
		if (_extraButtons === null) return;

		let buttonsWrapper = __getInstance()->>buttons;
		buttonsWrapper.del('extra');
		buttonsWrapper.positioning().setCols(2);

		for (let name in _extraButtons)
			delete callbackHolder[name];
		_extraButtons = null;
		_extraCallbacks = {};
	}
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
    	config.style = {display: 'none'};
        return config;
    }

	getBasicCss() {
		return {
			back: 'lx-ConfirmPopup-back',
		}
	}

	static initCssAsset(css) {
		css.addClass('lx-ConfirmPopup-back', {
			backgroundColor: css.preset.bodyBackgroundColor,
			borderRadius: css.preset.borderRadius,
			border: 'solid 1px ' + css.preset.widgetBorderColor,
		});
	}

	/**
	 * @widget-init
	 *
	 * @param [config] {Object: {
	 *     #merge(lx.Rect::constructor::config),
	 *     [extraCols = lx.ConfirmPopup.COLS_FOR_EXTRA_BUTTONS] {Number}
	 * }}
	 */
    build(config) {
    	this.extraCols = config.extraCols || self::COLS_FOR_EXTRA_BUTTONS;
    }

    #lx:client {
	    open(message, extraButtons = {}) {
	    	let popup = __getInstance(this);
			popup->stream->message.text(message);
			popup->stream->message.height(
				popup->stream->message->text.height('px') + 10 + 'px'
			);

			var top = (popup.height('px') - popup->stream.height('px')) * 0.5;
			if (top < 0) top = 0;
			popup->stream.top(top + 'px');

			popup.show();

			lx.onKeydown(13, __onConfirm);
			lx.onKeydown(27, __onReject);
			__applyExtraButtons(extraButtons, this.extraCols + 2);
			return callbackHolder;
	    }

	    close() {
	    	__close();
	    }
    }
}

#lx:client {
	function __getInstance(self = null) {
		if (instance === null) {
			instance = new lx.Box({
				parent: lx.body,
				geom: ['0%', '0%', '100%', '100%'],
				depthCluster: lx.DepthClusterMap.CLUSTER_OVER,
				style: { position: 'fixed' }
			});
			instance.overflow('auto');
	    	instance.useRenderCache();
	    	instance.begin();
	    	__renderContent(self);
	    	instance.end();
	    	instance.applyRenderCache();
	    	instance.hide();
		}

		return instance;
	}

	function __renderContent(self) {
		(new lx.Rect({geom:true})).fill('black').opacity(0.5);

		var inputPopupStream = new lx.Box({key:'stream', geom:['30%', '40%', '40%', '0%'], css:self.basicCss.back});
		inputPopupStream.stream({indent:'10px'});

		inputPopupStream.begin();
			(new lx.Box({key:'message'})).align(lx.CENTER, lx.MIDDLE);

			var buttons = new lx.Box({key:'buttons', height:'35px'});
			buttons.grid({step:'10px', cols:2});

			new lx.Button({parent:buttons, key:'confirm', width:1, text:#lx:i18n(Yes)});
			new lx.Button({parent:buttons, key:'reject', width:1, text:#lx:i18n(No)});
		inputPopupStream.end();

		inputPopupStream->>confirm.click(__onConfirm);
		inputPopupStream->>reject.click(__onReject);
	}

	function __onConfirm() {
		if (_confirmCallback) {
			if (lx.isFunction(_confirmCallback)) _confirmCallback();
			else if (lx.isArray(_confirmCallback))
				_confirmCallback[1].call(_confirmCallback[0]);
		} 
		__close();
	}

	function __onReject() {
		if (_rejectCallback) {
			if (lx.isFunction(_rejectCallback)) _rejectCallback();
			else if (lx.isArray(_rejectCallback))
				_rejectCallback[1].call(_rejectCallback[0]);
		} 
		__close();
	}

	function __onExtra(name) {
		let callback = _extraCallbacks[name];
		if (callback) {
			if (lx.isFunction(callback)) callback();
			else if (lx.isArray(callback))
				callback[1].call(callback[0]);
		} 
		__close();
	}

	function __close() {
		__getInstance().hide();
		_confirmCallback = null;
		_rejectCallback = null;
		__clearExtraButtons();
		lx.offKeydown(13, __onConfirm);
		lx.offKeydown(27, __onReject);
	}
}
