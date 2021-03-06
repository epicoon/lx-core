class BoxChildren {
    constructor() {
        this.data = [];
    }

    isEmpty() {
        return !this.data.length;
    }

    count() {
        return this.data.length;
    }

    push(el) {
        this.data.push(el);
    }

    remove(el) {
        this.data.remove(el);
    }

    contains(el) {
        return this.data.contains(el);
    }

    get(num) {
        if (num >= this.data.length) return null;
        return this.data[num];
    }

    insertBefore(el, next) {
        var index = this.data.indexOf(next);
        if (el.isArray) this.data.apply(this.data, [index, 0].concat(el));
        else this.data.splice(index, 0, el);
    }

    prev(elem) {
        var index = this.data.indexOf(elem);
        if (index == 0) return null;
        return this.data[index - 1];
    }

    next(elem) {
        var index = this.data.indexOf(elem);
        if (index + 1 == this.data.length) return;
        return this.data[index + 1];
    }

    last() {
        if (!this.data.length) return null;
        return this.data[this.data.length - 1];
    }

    each(f) {
        this.data.each(f);
    }

    reset() {
        this.data = [];
    }
}
