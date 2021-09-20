#lx:require app/js_extends;

#lx:client { window.lx = {}; };
#lx:server { global.lx = {}; };

#lx:require app/lx_function;
#lx:require widget/;

lx.LEFT = 1;
lx.CENTER = 2;
lx.WIDTH = 2;
lx.RIGHT = 3;
lx.JUSTIFY = 4;
lx.TOP = 5;
lx.MIDDLE = 6;
lx.HEIGHT = 6;
lx.BOTTOM = 7;
lx.VERTICAL = 1;
lx.HORIZONTAL = 2;

Object.defineProperty(lx, "globalContext", {
    get: function () {
        #lx:client { return window; };
        #lx:server { return global; };
    }
});

#lx:server {
    lx.node = __nodeModules__;

    lx.dump = function (data) {
        __out__.dumpList.push(data);
    };

    lx.log = function (data, category = null) {
        if (data === undefined)
            data = '%%%undefined%%%';
        else if (data === null)
            data = '%%%null%%%';
        else if (data && !lx.isString(data)) data = JSON.stringify(data);
        __out__.logList.push({category, data});
    };

    lx.addcslashes = function (str, charlist) {
        //   example 1: addcslashes('foo[ ]', 'A..z'); // Escape all ASCII within capital A to lower z range, including square brackets
        //   returns 1: "\\f\\o\\o\\[ \\]"
        //   example 2: addcslashes("zoo['.']", 'z..A'); // Only escape z, period, and A here since not a lower-to-higher range
        //   returns 2: "\\zoo['\\.']"
        //   _example 3: addcslashes("@a\u0000\u0010\u00A9", "\0..\37!@\177..\377"); // Escape as octals those specified and less than 32 (0x20) or greater than 126 (0x7E), but not otherwise
        //   _returns 3: '\\@a\\000\\020\\302\\251'
        //   _example 4: addcslashes("\u0020\u007E", "\40..\175"); // Those between 32 (0x20 or 040) and 126 (0x7E or 0176) decimal value will be backslashed if specified (not octalized)
        //   _returns 4: '\\ ~'
        //   _example 5: addcslashes("\r\u0007\n", '\0..\37'); // Recognize C escape sequences if specified
        //   _returns 5: "\\r\\a\\n"
        //   _example 6: addcslashes("\r\u0007\n", '\0'); // Do not recognize C escape sequences if not specified
        //   _returns 6: "\r\u0007\n"

        var target = ''
        var chrs = []
        var i = 0
        var j = 0
        var c = ''
        var next = ''
        var rangeBegin = ''
        var rangeEnd = ''
        var chr = ''
        var begin = 0
        var end = 0
        var octalLength = 0
        var postOctalPos = 0
        var cca = 0
        var escHexGrp = []
        var encoded = ''
        var percentHex = /%([\dA-Fa-f]+)/g

        var _pad = function (n, c) {
            if ((n = n + '').length < c) {
                return new Array(++c - n.length).join('0') + n
            }
            return n
        }

        for (i = 0; i < charlist.length; i++) {
            c = charlist.charAt(i)
            next = charlist.charAt(i + 1)
            if (c === '\\' && next && (/\d/).test(next)) {
                // Octal
                rangeBegin = charlist.slice(i + 1).match(/^\d+/)[0]
                octalLength = rangeBegin.length
                postOctalPos = i + octalLength + 1
                if (charlist.charAt(postOctalPos) + charlist.charAt(postOctalPos + 1) === '..') {
                    // Octal begins range
                    begin = rangeBegin.charCodeAt(0)
                    if ((/\\\d/).test(charlist.charAt(postOctalPos + 2) + charlist.charAt(postOctalPos + 3))) {
                        // Range ends with octal
                        rangeEnd = charlist.slice(postOctalPos + 3).match(/^\d+/)[0]
                        // Skip range end backslash
                        i += 1
                    } else if (charlist.charAt(postOctalPos + 2)) {
                        // Range ends with character
                        rangeEnd = charlist.charAt(postOctalPos + 2)
                    } else {
                        throw new Error('Range with no end point')
                    }
                    end = rangeEnd.charCodeAt(0)
                    if (end > begin) {
                        // Treat as a range
                        for (j = begin; j <= end; j++) {
                            chrs.push(String.fromCharCode(j))
                        }
                    } else {
                        // Supposed to treat period, begin and end as individual characters only, not a range
                        chrs.push('.', rangeBegin, rangeEnd)
                    }
                    // Skip dots and range end (already skipped range end backslash if present)
                    i += rangeEnd.length + 2
                } else {
                    // Octal is by itself
                    chr = String.fromCharCode(parseInt(rangeBegin, 8))
                    chrs.push(chr)
                }
                // Skip range begin
                i += octalLength
            } else if (next + charlist.charAt(i + 2) === '..') {
                // Character begins range
                rangeBegin = c
                begin = rangeBegin.charCodeAt(0)
                if ((/\\\d/).test(charlist.charAt(i + 3) + charlist.charAt(i + 4))) {
                    // Range ends with octal
                    rangeEnd = charlist.slice(i + 4).match(/^\d+/)[0]
                    // Skip range end backslash
                    i += 1
                } else if (charlist.charAt(i + 3)) {
                    // Range ends with character
                    rangeEnd = charlist.charAt(i + 3)
                } else {
                    throw new Error('Range with no end point')
                }
                end = rangeEnd.charCodeAt(0)
                if (end > begin) {
                    // Treat as a range
                    for (j = begin; j <= end; j++) {
                        chrs.push(String.fromCharCode(j))
                    }
                } else {
                    // Supposed to treat period, begin and end as individual characters only, not a range
                    chrs.push('.', rangeBegin, rangeEnd)
                }
                // Skip dots and range end (already skipped range end backslash if present)
                i += rangeEnd.length + 2
            } else {
                // Character is by itself
                chrs.push(c)
            }
        }

        for (i = 0; i < str.length; i++) {
            c = str.charAt(i)
            if (chrs.indexOf(c) !== -1) {
                target += '\\'
                cca = c.charCodeAt(0)
                if (cca < 32 || cca > 126) {
                    // Needs special escaping
                    switch (c) {
                        case '\n':
                            target += 'n'
                            break
                        case '\t':
                            target += 't'
                            break
                        case '\u000D':
                            target += 'r'
                            break
                        case '\u0007':
                            target += 'a'
                            break
                        case '\v':
                            target += 'v'
                            break
                        case '\b':
                            target += 'b'
                            break
                        case '\f':
                            target += 'f'
                            break
                        default:
                            // target += _pad(cca.toString(8), 3);break; // Sufficient for UTF-16
                            encoded = encodeURIComponent(c)

                            // 3-length-padded UTF-8 octets
                            if ((escHexGrp = percentHex.exec(encoded)) !== null) {
                                // already added a slash above:
                                target += _pad(parseInt(escHexGrp[1], 16).toString(8), 3)
                            }
                            while ((escHexGrp = percentHex.exec(encoded)) !== null) {
                                target += '\\' + _pad(parseInt(escHexGrp[1], 16).toString(8), 3)
                            }
                            break
                    }
                } else {
                    // Perform regular backslashed escaping
                    target += c
                }
            } else {
                // Just add the character unescaped
                target += c
            }
        }

        return target
    }
};

lx.getFirstDefined = function (...args) {
    for (var i = 0, l = args.len; i < l; i++)
        if (args[i] !== undefined) return args[i];
    return undefined;
};

lx.isInstance = function (value, constructor) {
    if (value === undefined)
        return (constructor === undefined);
    if (value === null)
        return (constructor === null);
    if (constructor === Number) return this.isNumber(value);
    if (constructor === String) return this.isString(value);
    if (this.isString(constructor)) constructor = this.getNamespace(constructor);
    if (value.constructor)
        return value.constructor === constructor;
    return value instanceof constructor;
};

lx.isNumber = function (value) {
    if (value === undefined || value === null) return false;
    if ( value.push !== undefined ) return false;
    return ( !isNaN(parseFloat(value)) && isFinite(value) );
};

lx.isBoolean = function (value) {
    if (value === undefined || value === null) return false;
    return value.constructor === Boolean;
};

lx.isString = function (value) {
    if (value === undefined || value === null) return false;
    return value.constructor === String;    
};

lx.isArray = function (value) {
    if (value === undefined || value === null) return false;
    return value.constructor === Array;
};

lx.isObject = function (value) {
    if (value === undefined || value === null) return false;
    return (value.constructor === Object || value.lxClassName() == 'Object');
};

lx.isFunction = function (value) {
    if (value === undefined || value === null) return false;
    return value.constructor === Function;
};

lx.createNamespace = function (namespace, props) {
    var arr = namespace.split(/[.\\]/),
        temp = lx.globalContext;
    for (var i = 0, l = arr.length; i < l; i++) {
        if (temp[arr[i]] === undefined) temp[arr[i]] = {};
        temp = temp[arr[i]];
    }
    if (props) temp.lxMerge(props);
    return temp;
};

lx.getNamespace = function (namespace) {
    var arr = lx.isString(namespace)
        ? namespace.split(/[.\\]/)
        : (lx.isArray(namespace) ? namespace : null);
    if (!arr) return null;
    if (arr.lxEmpty()) return lx.globalContext;

    var temp = lx.globalContext;
    for (var i = 0, l = arr.length; i < l; i++) {
        if (temp[arr[i]] === undefined) return null;
        temp = temp[arr[i]];
    }
    return temp;
};

lx.getClassConstructor = function (fullClassName) {
    var arr = fullClassName.split(/[.\\]/),
        name = arr.pop(),
        nmsp = lx.getNamespace(arr);
    if (!nmsp) return null;
    if (name in nmsp && this.isFunction(nmsp[name])) return nmsp[name];
    return null;
};

lx.classExists = function (name) {
    return !!lx.getClassConstructor(name);
};

lx.createObject = function (fullClassName, args = undefined) {
    if (this.isFunction(fullClassName)) {
        if (args === undefined) return new fullClassName();
        return new (Function.prototype.bind.apply(
            fullClassName,
            [null].lxMerge(args)
        ));
    }

    var arr = lx.isString(fullClassName)
        ? fullClassName.split(/[.\\]/)
        : (lx.isArray(fullClassName) ? fullClassName : null);
    if (!arr) return null;

    var temp = lx.globalContext;
    for (var i = 0, l = arr.length; i < l; i++) {
        if (temp[arr[i]] === undefined) return null;

        if (i == l - 1) {
            if (args !== undefined) {
                return new (Function.prototype.bind.apply(
                    temp[arr[i]],
                    [null].lxMerge(args)
                ));
            } else return new temp[arr[i]]();
        } else temp = temp[arr[i]];
    }

    return null;
};
