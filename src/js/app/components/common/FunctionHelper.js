

#lx:namespace lx;
class FunctionHelper extends lx.AppComponent {
    callFunction(data, args = []) {
        if (lx.isFunction(data)) data.apply(null, args);
        else if (lx.isArray(data)) data[1].apply(data[0], args);
    }

    // По переданным строкам аргументов и кода создает функцию и сразу ее запускает
    createAndCallFunction(args, code, context=null, params=[]) {
        var f = this.createFunction(args, code);
        return f.apply(context, params);
    }

    createAndCallFunctionWithArguments(args, code, context=null) {
        code = code.replace(/(^[^{]+?{|}\s*$)/g, '');
        var kstl = '}';
        var argsArr = [];
        var argNamesArr = [];
        for (var name in args) {
            argsArr.push(args[name]);
            argNamesArr.push(name);
        }
        return this.createAndCallFunction(argNamesArr.join(','), code, null, argsArr);
    }

    // Превращает функцию в строку в формате '(arg1, arg2) => ...function code'
    functionToString(func) {
        var funcText = null;
        if (lx.isFunction(func)) {
            funcText = func.toString();
            if (funcText.match(/^\s*\(/))
                funcText = funcText.replace(/^\s*\(([^\)]*?)\)\s*=>\s*{\s*/, '($1)=>');
            else
                funcText = funcText.replace(/^\s*function\s*\(([^\)]*?)\)\s*{\s*/, '($1)=>');
            funcText = funcText.replace(/\s*}\s*$/, '');
            var a = '}'; //TODO костыль для регулярок с рекурсивной подмаской
        } else if (lx.isString(func)) funcText = func;
        return funcText;
    }

    // Создает функцию из вариантов синтаксиса:
    // '(arg1, arg2) => ...function code'
    // 'function code'
    // 'function (arg1, arg2) { ...function code }'
    stringToFunction(str) {
        var arr = this.parseFunctionString(str);
        return this.createFunction(arr[0], arr[1]);
    }

    parseFunctionString(str) {
        if (str[0] == '(') {
            var reg = /^\(\s*([\w\W]*?)\s*\)\s*=>\s*{?([\w\W]*?)}?\s*$/,
                arr = reg.exec(str);
            if (!arr.len) return null;
            return [arr[1], arr[2]];
        }

        if (str.match(/^function/)) {
            var reg = /^function[^(]*\(\s*([\w\W]*?)\s*\)\s*{\s*([\w\W]*)\s*}\s*$/,
                arr = reg.exec(str);
            if (!arr.len) return null;
            return [arr[1], arr[2]];
        }

        return [null, str];
    }

    // Создать функцию по аргументам и коду
    createFunction(args, code) {
        if (code === undefined) return Function(args);
        if (args) return Function(args, code);
        return Function(code);
    }

    isEmptyFunction(func) {
        let funcText = func.toString();
        funcText = funcText.slice(funcText.indexOf('{') + 1, funcText.lastIndexOf('}'));
        return /^\s*$/.test(funcText);
    }
}
