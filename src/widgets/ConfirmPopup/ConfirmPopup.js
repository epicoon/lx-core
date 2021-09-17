#lx:module lx.ConfirmPopup;
#lx:module-data {
    i18n: i18n.yaml
};

#lx:use lx.Rect;
#lx:use lx.Box;
#lx:use lx.Button;

#lx:private;

#lx:client {
	let instance = null,
		_yesCallback = null,
		_noCallback = null,
		_extraButtons = null,
		_extraCallbacks = {},
		callbackHolder = {
		yes: function(callback) {
			_yesCallback = callback;
			return this;
		},
		no: function(callback) {
			_noCallback = callback;
			return this;
		}
	};

	function __applyExtraButtons(extraButtons, colsWithExtra) {
		if (extraButtons.lxEmpty) return;

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

class ConfirmPopup extends lx.Box #lx:namespace lx {
	#lx:const COLS_FOR_EXTRA_BUTTONS = 1;

    modifyConfigBeforeApply(config) {
    	config.key = config.key || 'confirmPopup';
    	config.style = {display: 'none'};
        return config;
    }

    build(config) {
    	this.extraCols = config.extraCols || self::COLS_FOR_EXTRA_BUTTONS;
    }

    #lx:client {
	    open(message, extraButtons = {}) {
	    	let popup = __getInstance();
			popup->stream->message.text(message);
			popup->stream->message.height(
				popup->stream->message->text.height('px') + 10 + 'px'
			);

			var top = (popup.height('px') - popup->stream.height('px')) * 0.5;
			if (top < 0) top = 0;
			popup->stream.top(top + 'px');

			popup.show();

			lx.keydown(13, __onYes);
			lx.keydown(27, __onNo);
			__applyExtraButtons(extraButtons, this.extraCols + 2);
			return callbackHolder;
	    }

	    close() {
	    	__close();
	    }
    }
}

#lx:client {
	function __getInstance() {
		if (instance === null) {
			instance = new lx.Box({
				parent: lx.body,
				geom: ['0%', '0%', '100%', '100%'],
				style: {
		        	'z-index': 1000,
		        	position: 'fixed',
		        	overflow: 'auto'
				}
			});
	    	instance.useRenderCache();
	    	instance.begin();
	    	__renderContent(instance);
	    	instance.end();
	    	instance.applyRenderCache();
	    	instance.hide();
		}

		return instance;
	}

	function __renderContent(self) {
		new lx.Rect({geom:true, style: {fill:'black', opacity:0.5}});

		var inputPopupStream = new lx.Box({key:'stream', geom:['30%', '40%', '40%', '0%']});
		inputPopupStream.fill('white');
		inputPopupStream.border();
		inputPopupStream.roundCorners('8px');
		inputPopupStream.stream({indent:'10px'});

		inputPopupStream.begin();
			(new lx.Box({key:'message'})).align(lx.CENTER, lx.MIDDLE);

			var buttons = new lx.Box({key:'buttons', height:'35px'});
			buttons.grid({step:'10px', cols:2});

			new lx.Button({parent:buttons, key:'yes', width:1, text:#lx:i18n(Yes)});
			new lx.Button({parent:buttons, key:'no', width:1, text:#lx:i18n(No)});
		inputPopupStream.end();

		inputPopupStream->>yes.click(__onYes);
		inputPopupStream->>no.click(__onNo);
	}

	function __onYes() {
		if (_yesCallback) {
			if (_yesCallback.isFunction) _yesCallback();
			else if (_yesCallback.isArray)
				_yesCallback[1].call(_yesCallback[0]);
		} 
		__close();
	}

	function __onNo() {
		if (_noCallback) {
			if (_noCallback.isFunction) _noCallback();
			else if (_noCallback.isArray)
				_noCallback[1].call(_noCallback[0]);
		} 
		__close();
	}

	function __onExtra(name) {
		let callback = _extraCallbacks[name];
		if (callback) {
			if (callback.isFunction) callback();
			else if (callback.isArray)
				callback[1].call(callback[0]);
		} 
		__close();
	}

	function __close() {
		__getInstance().hide();
		_yesCallback = null;
		_noCallback = null;
		__clearExtraButtons();
		lx.keydownOff(13, __onYes);
		lx.keydownOff(27, __onNo);
	}
}
