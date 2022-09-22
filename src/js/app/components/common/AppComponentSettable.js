#lx:namespace lx;
class AppComponentSettable extends lx.AppComponent {
    constructor(app) {
        super(app);
        this.settings = {};
    }

    addSettings(list) {
        for (let key in list)
            this.settings[key] = list[key];
    }

    applyData(data) {
        // pass
    }
}
