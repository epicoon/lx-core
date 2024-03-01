#lx:public;

function __construct(self, config) {
    self.core = null;
    self.name = config.name;
    self.attributes =  {};
    self.root = config.root;
    self.widgetBasicCssList = {};
    self._cssPreset = null;

    self.eventCallbacks = [];
    self.destructCallbacks = [];
    self.namespaces = [];
    self.dependencies = {};

    self._eventDispatcher = null;
    self._onFocus = null;
    self._onUnfocus = null;

    if (self.root) {
        __init(self, config);
        self.root.click(__onClick);
    }

    self.guiNodes = {};
    self.init();
}

function __destruct(self) {
    lx.app.plugins.unfocusPlugin(self);
    self.root.off('click', __onClick);

    // Удаление вложенных плагинов
    let childPlugins = self.getChildPlugins(true);
    for (let i=0, l=childPlugins.len; i<l; i++)
        childPlugins[i].del();

    // Удаление хэндлеров клавиатуры
    lx.app.keyboard.offKeydown(null, null, {plugin:self});
    lx.app.keyboard.offKeyup(null, null, {plugin:self});

    // Коллбэки на удаление
    for (let i=0, l=self.destructCallbacks.len; i<l; i++)
        lx.app.functionHelper.callFunction(self.destructCallbacks[i]);

    // Клиентский метод очистки
    if (self.destruct) self.destruct();

    // Очистка элемента, который был для плагина корневым
    delete self.root.plugin;
    self.root.clear();

    // Удаление зависимостей от ресурсов
    lx.app.dependencies.independ(self.dependencies);

    //TODO если плагин был не в единственном экземпляре, то он удалит обший ресурс. Нужно такие пространства имен учитывать как зависимости
    // Удаление пространств имен, созданных плагином
    for (let i=0, l=self.namespaces.len; i<l; i++)
        delete window[self.namespaces[i]];

    // Удаление из списка плагинов
    lx.app.plugins.remove(self);

    self.eventCallbacks = [];
}

function __init(self, config) {
    // Вероятность мала, но если ключ уже используется каким-то плагином, который был
    // загружен предыдущими запросами - сгенерим уникальный
    const list = lx.app.plugins.getList();
    if (config.key in list) {
        var key;
        function randKey() {
            return '' +
                lx.Math.decChangeNotation(lx.Math.randomInteger(0, 255), 16) +
                lx.Math.decChangeNotation(lx.Math.randomInteger(0, 255), 16) +
                lx.Math.decChangeNotation(lx.Math.randomInteger(0, 255), 16);
        };
        do {
            key = randKey();
        } while (key in list);
        self.key = key;
    } else self.key = config.key;
    lx.app.plugins.add(self.key, self);

    if (config.parent) self.parent = config.parent;
    if (config.main || config.isMain) self.isMain = true;
    if (config.attributes) self.attributes = config.attributes;

    if (config.images) self.images = config.images;
    self._cssPreset = config.cssPreset;

    // Информация о зависимостях
    if (config.dep) {
        if (config.dep.m) self.dependencies.modules = config.dep.m;
        if (config.dep.c) self.dependencies.css = config.dep.c;
        if (config.dep.s) self.dependencies.scripts = config.dep.s;
        lx.app.dependencies.depend(self.dependencies);
    }

    if (config.wgdl) {
        self.widgetBasicCssList = config.wgdl;
    }

    self.root.plugin = self;
}

function __onClick() {
    this.plugin.focus();
}

function __setTitle(self, value) {
    if (this.isMainContext()) document.title = val;
}

function __getTitle(self) {
    return document.title;
}
