#lx:module lx.ConfirmPopup;
#lx:module-data {
    i18n: i18n.yaml
};

#lx:use lx.Rect;
#lx:use lx.Box;
#lx:use lx.Button;

#lx:private;

#lx:client {
	let instance = null;
	let onEnterCallback = null;
}

class ConfirmPopup extends lx.Box #lx:namespace lx {
    modifyConfigBeforeApply(config) {
    	config.style = {display: 'none'};
        return config;
    }

    #lx:client {
	    open(message, callback) {
	    	let popup = __getInstance();
			popup->stream->message.text(message);
			popup->stream->message.height(
				popup->stream->message->text.height('px') + 10 + 'px'
			);

			var top = (popup.height('px') - popup->stream.height('px')) * 0.5;
			if (top < 0) top = 0;
			popup->stream.top(top + 'px');

			popup.show();
			onEnterCallback = callback;

			lx.keydown(13, __onEnter);
			lx.keydown(27, __close);
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
			buttons.grid({step:'10px',cols:2});

			new lx.Button({parent:buttons, key:'yes', width:1, text:#lx:i18n(Yes)});
			new lx.Button({parent:buttons, key:'no', width:1, text:#lx:i18n(No)});
		inputPopupStream.end();

		inputPopupStream->>yes.click(__onEnter);
		inputPopupStream->>no.click(__close);
	}

	function __onEnter() {
		if (onEnterCallback) {
			if (onEnterCallback.isFunction) onEnterCallback();
			else if (onEnterCallback.isArray)
				onEnterCallback[1].call(onEnterCallback[0]);
		} 
		__close();
	}

	function __close() {
		__getInstance().hide();
		onEnterCallback = null;
		lx.keydownOff(13, __onEnter);
		lx.keydownOff(27, __close);
	}
}
