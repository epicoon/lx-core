function getEnvInfo() {
	var ua = navigator.userAgent;

	function bName() {
	    if (ua.search(/Edge/) > -1) return 'edge';
	    if (ua.search(/MSIE/) > -1) return 'ie';
	    if (ua.search(/Trident/) > -1) return 'ie11';
	    if (ua.search(/Firefox/) > -1) return 'firefox';
	    if (ua.search(/Opera/) > -1) return 'opera';
	    if (ua.search(/OPR/) > -1) return 'operaWebkit';
	    if (ua.search(/YaBrowser/) > -1) return 'yabrowser';
	    if (ua.search(/Chrome/) > -1) return 'chrome';
	    if (ua.search(/Safari/) > -1) return 'safari';
	    if (ua.search(/Maxthon/) > -1) return 'maxthon';
	};
	bName = bName();

	var version;
	switch (bName) {
	    case 'edge':
	        version = (ua.split('Edge')[1]).split('/')[1];
	        break;
	    case 'ie':
	        version = (ua.split('MSIE ')[1]).split(';')[0];
	        break;
	    case 'ie11':
	        bName = 'ie';
	        version = (ua.split('; rv:')[1]).split(')')[0];
	        break;
	    case 'firefox':
	        version = ua.split('Firefox/')[1];
	        break;
	    case 'opera':
	        version = ua.split('Version/')[1];
	        break;
	    case 'operaWebkit':
	        bName = 'opera';
	        version = ua.split('OPR/')[1];
	        break;
	    case 'yabrowser':
	        version = (ua.split('YaBrowser/')[1]).split(' ')[0];
	        break;
	    case 'chrome':
	        version = (ua.split('Chrome/')[1]).split(' ')[0];
	        break;
	    case 'safari':
	        version = (ua.split('Version/')[1]).split(' ')[0];
	        break;
	    case 'maxthon':
	        version = ua.split('Maxthon/')[1];
	        break;
	};

	var platform = 'desktop';

	if (/iphone|ipad|ipod|android|blackberry|mini|windows\sce|palm/i.test(navigator.userAgent.toLowerCase())) platform = 'mobile';

	var browsrObj;

	try {
	    browsrObj = {
	        platform: platform,
	        browser: bName,
	        versionFull: version,
	        versionShort: version.split('.')[0]
	    };
	} catch (err) {
	    browsrObj = {
	        platform: platform,
	        browser: 'unknown',
	        versionFull: 'unknown',
	        versionShort: 'unknown'
	    };
	};

	return browsrObj;
};

let settings = {},
	env = getEnvInfo();

lx.data = {};
lx.setSettings = function(data) {
	settings = data;
	delete this.setSettings;
};
lx.getSetting = function(name) {
	return settings[name];
};

Object.defineProperty(lx, "environment", {
	get: function() { return env; }
});

Object.defineProperty(lx, "language", {
	get: function() { return settings.lang || {}; }
});
