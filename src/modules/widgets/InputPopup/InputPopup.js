#lx:module lx.InputPopup;
#lx:module-data {
    i18n: i18n.yaml
};

#lx:use lx.Box;
#lx:use lx.Button;
#lx:use lx.Input;

#lx:client {
	let instance = null,

		onEnterCallback = null,

		_confirmCallback = null,
		_rejectCallback = null,		
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
}

/**
 * @widget lx.InputPopup
 * @content-disallowed
 */
#lx:namespace lx;
class InputPopup extends lx.Box {
    modifyConfigBeforeApply(config) {
    	config.key = config.key || 'inputPopup';
    	config.style = {display: 'none'};
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
	    open(captions, defaults = {}) {
			if (!lx.isArray(captions)) captions = [captions];

			var popup = __getInstance(this);
			var buttons = popup->stream->buttons;

			popup->stream.del('r');
			popup.useRenderCache();
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
			popup.applyRenderCache();

			var top = (popup.height('px') - popup->stream.height('px')) * 0.5;
			if (top < 0) top = 0;
			popup->stream.top(top + 'px');

			popup.show();

			lx.app.keyboard.onKeydown(13, __onConfirm);
			lx.app.keyboard.onKeydown(27, __onReject);

			var rows = popup->stream->r;
			if (lx.isArray(rows)) rows[0]->input.focus();
			else rows->input.focus();

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
			var buttons = new lx.Box({key:'buttons', height:'35px'});
			buttons.grid({step:'10px',cols:2});
			new lx.Button({parent:buttons, key:'confirm', width:1, text:#lx:i18n(OK)});
			new lx.Button({parent:buttons, key:'reject', width:1, text:#lx:i18n(Close)});
		inputPopupStream.end();

		inputPopupStream->>confirm.click(__onConfirm);
		inputPopupStream->>reject.click(__onReject);
	}

	function __onConfirm() {
		if (_confirmCallback) {
			var popup = __getInstance();
			var values = [];
			if (popup->stream.contains('r')) {
				var rows = popup->stream->r;
				if (rows) {
					if (!lx.isArray(rows)) rows = [rows];
					rows.forEach(a=>values.push(a->input.value()));
				}
			}
			if (values.len == 1) values = values[0];
			if (lx.isFunction(_confirmCallback)) _confirmCallback(values);
			else if (lx.isArray(_confirmCallback))
				_confirmCallback[1].call(_confirmCallback[0], values);
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

	function __close() {
		__getInstance().hide();
		_confirmCallback = null;
		_rejectCallback = null;
		lx.app.keyboard.offKeydown(13, __onConfirm);
		lx.app.keyboard.offKeydown(27, __onReject);
	}
}
