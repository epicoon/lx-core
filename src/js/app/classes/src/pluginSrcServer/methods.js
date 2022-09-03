#lx:public;

function __construct(self, config) {
    self.serviceName = config.serviceName;
    self.name = config.name;
    self.path = config.path;
    self.images = config.images;
    self._cssPreset = config.cssPreset;
    self.widgetBasicCssList = config.widgetBasicCss || {};
    self._title = config.title;
    self._icon = config.icon;
    self._changes = {
        title: null,
        icon: null
    };

    self._oldAttributes = config.attributes ? config.attributes.lxClone() : {};
    self.attributes = config.attributes || {};
}

function __setTitle(self, value) {
    self._title = value;
    self._changes.title = value;
}

function __getTitle(self) {
    return self._title;
}
