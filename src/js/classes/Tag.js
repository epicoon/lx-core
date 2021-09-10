class Tag #lx:namespace lx {
    constructor(config) {
        this.tag = config.tag || 'div';
        this.attributes = config.attributes || {};
        this.classList = config.classList || [];
        this.style = config.style || {};
        this.content = config.content || '';
    }

    toString() {
        return this.getOpenString()
            + this.getContentString()
            + this.getCloseString();
    }

    getOpenString() {
        var result = '<' + this.tag;

        for (var name in this.attributes) {
            result += ' ' + name + (
                (this.attributes[name] === null || this.attributes[name] === '')
                    ? ''
                    : '="' + this.attributes[name] + '"'
            );
        }

        if (this.classList.len) result += ' class="' + this.classList.join(' ') + '"';

        if ( ! this.style.lxEmpty) {
            result += ' style="';
            for (var name in this.style) {
                var val = this.style[name];
                if (val === undefined || val === null || val === '') continue;
                var propName = name.replace(/([A-Z])/g, function(x){return "-" + x.toLowerCase()});
                result += propName + ':' + val + ';';
            }
            result += '"';
        }

        return result + '>';
    }

    getContentString() {
        if (this.content === undefined || this.content === null) return '';
        if (this.content.isString || this.content.isNumber) return this.content;
        if (this.content.isBoolean) {
            if (this.content) return 'true';
            return 'false';
        }
        if (this.content.is(lx.Tag)) return this.content.toString();

        var result = '';
        if (this.content.isArray) {
            function renderArray(arr) {
                var result = '';
                for (var i=0, l=arr.len; i<l; i++) {
                    var item = arr[i];
                    if (item.isString) result += item;
                    else if (item.is(lx.Tag)) result += item.toString();
                    else if (item.isArray) result += renderArray(item);
                }
                return result;
            }
            result += renderArray(this.content);
        }

        return result;
    }

    getCloseString() {
        return '</' + this.tag + '>';
    }
}
