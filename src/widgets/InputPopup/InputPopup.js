#lx:module lx.InputPopup;
#lx:module-data {
    i18n: i18n.yaml
};

#lx:use lx.Rect;
#lx:use lx.Box;
#lx:use lx.Button;
#lx:use lx.Input;

#lx:private;

#lx:client {
	let instance = null;
	let onEnterCallback = null;
}

class InputPopup extends lx.Box #lx:namespace lx {
    modifyConfigBeforeApply(config) {
    	config.key = config.key || 'inputPopup';
    	config.style = {display: 'none'};
        return config;
    }

    #lx:client {
	    open(captions, defaults, callback) {
			if (!captions.isArray) captions = [captions];
			if (defaults.isFunction) {
				callback = defaults;
				defaults = {};
			}

			var popup = __getInstance();
			var buttons = popup->stream->buttons;

			popup->stream.del('r');
			popup.useRenderCache();
			captions.each((caption)=>{
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
			popup.applyRenderCache();

			var top = (popup.height('px') - popup->stream.height('px')) * 0.5;
			if (top < 0) top = 0;
			popup->stream.top(top + 'px');

			popup.show();
			onEnterCallback = callback;

			lx.keydown(13, __onEnter);
			lx.keydown(27, __close);

			var rows = popup->stream->r;
			if (rows.isArray) rows[0]->input.focus();
			else rows->input.focus();
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
			var buttons = new lx.Box({key:'buttons', height:'35px'});
			buttons.grid({step:'10px',cols:2});
			new lx.Button({parent:buttons, key:'ok', width:1, text:#lx:i18n(OK)});
			new lx.Button({parent:buttons, key:'close', width:1, text:#lx:i18n(Close)});
		inputPopupStream.end();

		inputPopupStream->>ok.click(__onEnter);
		inputPopupStream->>close.click(__close);
	}

	function __onEnter() {
		if (onEnterCallback) {
			var popup = __getInstance();
			var values = [];
			if (popup->stream.contains('r')) {
				var rows = popup->stream->r;
				if (rows) {
					if (!rows.isArray) rows = [rows];
					rows.each((a)=> values.push(a->input.value()));
				}
			}
			if (values.len == 1) values = values[0];
			if (onEnterCallback.isFunction) onEnterCallback(values);
			else if (onEnterCallback.isArray)
				onEnterCallback[1].call(onEnterCallback[0], values);
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
