<?php

namespace lx;

class SintaxExtender {
	/**
	 *
	 * */
	public static function applyExtendedSintax($code) {
		// ? >#id => lx.WidgetHelper.getById('id')
		$code = preg_replace_callback('/\?>#(\b.+?\b)/', function($matches) {
			return 'lx.WidgetHelper.getById(\'' . $matches[1] . '\')';
		}, $code);
		// ? >#{id} => lx.WidgetHelper.getById(id)
		$code = preg_replace_callback('/\?>#{(.+?)}/', function($matches) {
			return 'lx.WidgetHelper.getById(' . $matches[1] . ')';
		}, $code);

		// ? >.class => lx.WidgetHelper.getByClass('class')
		$code = preg_replace_callback('/\?>\.(\b.+?\b)/', function($matches) {
			return 'lx.WidgetHelper.getByClass(\'' . $matches[1] . '\')';
		}, $code);
		// ? >.{class} => lx.WidgetHelper.getByClass(class)
		$code = preg_replace_callback('/\?>\.{(.+?)}/', function($matches) {
			return 'lx.WidgetHelper.getByClass(' . $matches[1] . ')';
		}, $code);

		// ? >name => lx.WidgetHelper.getByName('name')
		$code = preg_replace_callback('/\?>(\b.+?\b)/', function($matches) {
			return 'lx.WidgetHelper.getByName(\'' . $matches[1] . '\')';
		}, $code);
		// ? >{name} => lx.WidgetHelper.getByName(name)
		$code = preg_replace_callback('/\?>{(.+?)}/', function($matches) {
			return 'lx.WidgetHelper.getByName(' . $matches[1] . ')';
		}, $code);

		// self:: => this.constructor.
		$code = str_replace('self::', 'this.constructor.', $code);

		// Module->key => Module.get('key')
		$code = preg_replace_callback('/\bModule->([\w_][\w\d]*?\b)/', function($matches) {
			return 'Module.get(\'' . $matches[1] . '\')';
		}, $code);

		// Module->>key => Module.find('key')
		$code = preg_replace_callback('/\bModule->>([\w_][\w\d]*?\b)/', function($matches) {
			return 'Module.find(\'' . $matches[1] . '\')';
		}, $code);

		// element->child => element.children.child
		$code = preg_replace_callback('/([\w\d_)\[\]])->([\w_$])/', function($matches) {
			return $matches[1] . '.children.' . $matches[2];
		}, $code);

		// element->>child => element.find('child')
		$code = preg_replace_callback('/([\w\d_)\[\]])->>([\w_][\w\d]*?\b)/', function($matches) {
			return $matches[1] . '.find(\'' . $matches[2] . '\')';
		}, $code);

		// element~>key => element.neighbor('key')
		$code = preg_replace_callback('/([\w\d_])~>([\w_][\w\d_]*?\b)/', function($matches) {
			return $matches[1] . '.neighbor(\'' . $matches[2] . '\')';
		}, $code);

		// someClass.someField = ^Resp.method(arg1, arg2); => Module.callToRespondent('Resp/method', [arg1, arg2], (_result_)=>{someClass.someField=_result_;});
		$regexp = '/(\b[\w_\d.\(\)]+)\s*=\s*\^([\w_][\w\d_]*?)\.([\w_][\w\d_]*?)\(([^)]*?)\)(;?)/';
		preg_match_all($regexp, $code, $matches);
		for ($i=0, $l=count($matches[0]); $i<$l; $i++) {
			$field = $matches[1][$i];
			$respondent = $matches[2][$i];
			$method = $matches[3][$i];
			$args = $matches[4][$i];
			$end = $matches[5][$i];
			$text = "Module.callToRespondent('$respondent/$method',[$args],(_result_)=>{".$field."=_result_;})$end";
			$code = str_replace($matches[0][$i], $text, $code);
		}

		//=====================================================================================================================
		//=====================================================================================================================
		//=====================================================================================================================

		// ^self::method(arg1, arg2) : (res)=> { someCode... }; => self.ajax('method', [arg1, arg2], (res)=> { someCode... });
		// С учетом, что 'self::' уже декодирован в 'this.constructor.'
		/*
		Рекурсивная подмаска регулярного выражения:
		(?P<name>{ ( (?>[^{}]+)|(?P>name) )* })
		(?P<name>reg) - задает имя группе
		(?P>name) - рекурсивно вызывает группу. Без именования рекурсия замыкается на все выражение, обозначается (?R)
		*/
		$regexp = '/\^this\.constructor\.([\w_][\w\d_]*?)(?P<therec0>\(((?>[^()]+)|(?P>therec0))*\))\s*:\s*\((.*?)\)\s*=>\s*(?P<therec>{((?>[^{}]+)|(?P>therec))*})/';
		preg_match_all($regexp, $code, $matches);
		while (!empty($matches[0])) {
			for ($i=0, $l=count($matches[0]); $i<$l; $i++) {
				$method = $matches[1][$i];

				$args = $matches[2][$i];
				/*
				Аргументы тоже ищутся рекурсивной подмаской - т.к. может быть и так ^Resp.method(f(), f2())...
				При этом может быть и так ^Resp.method()... а пустые скобки нам не нужны, заменяем их на пустую строку
				//todo - в остальных вариантах тоже самое надо сделать
				*/
				$args = preg_replace('/^\(/', '', $args);
				$args = preg_replace('/\)$/', '', $args);

				$res = $matches[4][$i];
				$func = $matches[5][$i];
				$text = "this.constructor.ajax('$method',[$args],($res)=>$func)";
				$code = str_replace($matches[0][$i], $text, $code);
			}
			preg_match_all($regexp, $code, $matches);
		}

		//---------------------------------------------------------------------------------------------------------------------

		// ^Resp.method(arg1, arg2); => Module.callToRespondent('Resp/method', [arg1, arg2]);
		$regexp = '/\^([\w_][\w\d_]*?)\.([\w_][\w\d_]*?)\(([^)]*?)\);/';
		preg_match_all($regexp, $code, $matches);
		for ($i=0, $l=count($matches[0]); $i<$l; $i++) {
			$respondent = $matches[1][$i];
			$method = $matches[2][$i];
			$args = $matches[3][$i];
			$text = "Module.callToRespondent('$respondent/$method',[$args]);";
			$code = str_replace($matches[0][$i], $text, $code);
		}

		// ^Resp.method(arg1, arg2) : (res)=> { someCode... }; => Module.callToRespondent('Resp/method', [arg1, arg2], (res)=> { someCode... });
		/*
		Рекурсивная подмаска регулярного выражения:
		(?P<name>{ ( (?>[^{}]+)|(?P>name) )* })
		(?P<name>reg) - задает имя группе
		(?P>name) - рекурсивно вызывает группу. Без именования рекурсия замыкается на все выражение, обозначается (?R)
		*/
		$regexp = '/\^([\w_][\w\d_]*?)\.([\w_][\w\d_]*?)(?P<therec0>\(((?>[^()]+)|(?P>therec0))*\))\s*:\s*\((.*?)\)\s*=>\s*(?P<therec>{((?>[^{}]+)|(?P>therec))*})/';
		preg_match_all($regexp, $code, $matches);
		//todo - оптимизировать
		while (!empty($matches[0])) {
			for ($i=0, $l=count($matches[0]); $i<$l; $i++) {
				$respondent = $matches[1][$i];
				$method = $matches[2][$i];

				$args = $matches[3][$i];
				/*
				Аргументы тоже ищутся рекурсивной подмаской - т.к. может быть и так ^Resp.method(f(), f2())...
				При этом может быть и так ^Resp.method()... а пустые скобки нам не нужны, заменяем их на пустую строку
				//todo - в остальных вариантах тоже самое надо сделать
				*/
				$args = preg_replace('/^\(/', '', $args);
				$args = preg_replace('/\)$/', '', $args);

				$res = $matches[5][$i];
				$func = $matches[6][$i];
				$text = "Module.callToRespondent('$respondent/$method',[$args],($res)=>$func)";
				$code = str_replace($matches[0][$i], $text, $code);
			}
			preg_match_all($regexp, $code, $matches);
		}

		// ^Resp.method(arg1, arg2) : (res)=>someCode; => Module.callToRespondent('Resp/method', [arg1, arg2], (res)=> { someCode... });
		$regexp = '/\^([\w_][\w\d_]*?)\.([\w_][\w\d_]*?)(?P<therec0>\(((?>[^()]+)|(?P>therec0))*\))\s*:\s*\((.*?)\)\s*=>\s*([^;]*?);/';
		preg_match_all($regexp, $code, $matches);
		for ($i=0, $l=count($matches[0]); $i<$l; $i++) {
			$respondent = $matches[1][$i];
			$method = $matches[2][$i];
			$args = $matches[3][$i];
			/*
			Аргументы тоже ищутся рекурсивной подмаской - т.к. может быть и так ^Resp.method(f(), f2())...
			При этом может быть и так ^Resp.method()... а пустые скобки нам не нужны, заменяем их на пустую строку
			//todo - в остальных вариантах тоже самое надо сделать
			*/
			$args = preg_replace('/^\(/', '', $args);
			$args = preg_replace('/\)$/', '', $args);

			$res = $matches[5][$i];
			$func = $matches[6][$i];
			$text = "Module.callToRespondent('$respondent/$method',[$args],($res)=>$func)";
			$code = str_replace($matches[0][$i], $text, $code);
		}

		// ^Resp.method(arg1, arg2) : someFunction; => Module.callToRespondent('Resp/method', [arg1, arg2], someFunction);
		$regexp = '/\^([\w_][\w\d_]*?)\.([\w_][\w\d_]*?)\((.*?)\)\s*:\s*([\w_][\w\d_]*?\.?[\w\d_]+?);/';
		preg_match_all($regexp, $code, $matches);
		for ($i=0, $l=count($matches[0]); $i<$l; $i++) {
			$respondent = $matches[1][$i];
			$method = $matches[2][$i];
			$args = $matches[3][$i];
			$func = $matches[4][$i];
			$text = "Module.callToRespondent('$respondent/$method',[$args],$func);";
			$code = str_replace($matches[0][$i], $text, $code);
		}

		// ^Resp.method(arg1, arg2) ? onLoad : onError; => Module.callToRespondent('Resp/method', [arg1, arg2], {success: onLoad, error: onError});
		$regexp = '/\^([\w_][\w\d_]*?)\.([\w_][\w\d_]*?)\((.*?)\)\s*\?\s*([\w_][\w\d_]*?\.?[\w\d_]+?)\s*:\s*([\w_][\w\d_]*?\.?[\w\d_]+?);/';
		preg_match_all($regexp, $code, $matches);
		for ($i=0, $l=count($matches[0]); $i<$l; $i++) {
			$respondent = $matches[1][$i];
			$method = $matches[2][$i];
			$args = $matches[3][$i];
			$onLoad = $matches[4][$i];
			$onError = $matches[5][$i];
			$text = "Module.callToRespondent('$respondent/$method',[$args],{success:$onLoad,error:$onError});";
			$code = str_replace($matches[0][$i], $text, $code);
		}

		//---------------------------------------------------------------------------------------------------------------------

		/*
		//todo
		^url(param1, param2) ? onSuccessHandler : onErrorHandler; => Module.ajax('url', [param1, param2], {success: onSuccessHandler, error: onErrorHandler});
		То же самое, но с фигурными скобками - чтобы не оборачивать в кавычки
		^{url}

		// Придумать что-то для активных запросов - чтобы отличать это от предыдущей ситуации
		^url({param0:value0, param1:value1}); => Module.activeRequest('url', {param0:value0, param1:value1});
		*/

		//=====================================================================================================================
		//=====================================================================================================================
		//=====================================================================================================================

		// #lx:model e = { a, b, sum }; => const e = (function(){ class _am_ extends lx.BindableModel { field a, b, sum; } return new _am_; })();
		// #lx:model e = ModelName; => const e = (function(){ class _am_ extends lx.BindableModel { #lx:modelName ModelName; } return new _am_; })();
		$regexp = '/#lx:model\s*(\b[\w_][\w\d_]*\b)\s*=\s*([\w\W]+?);/';
		preg_match_all($regexp, $code, $matches);
		for ($i=0, $l=count($matches[0]); $i<$l; $i++) {
			$name = $matches[1][$i];
			$fields = $matches[2][$i];
			$fields = trim($fields, ' ');
			if ($fields{0} == '{') {
				if (preg_match('/^{/', $fields)) $fields = preg_replace('/^{/', '', $fields);
				if (preg_match('/}$/', $fields)) $fields = preg_replace('/}$/', '', $fields);
				$fields = trim($fields, ' ');
				$fields = '#lx:schema ' . $fields;
			} else{
				$fields = '#lx:modelName ' . $fields;				
			}
			$text = "const $name = (function(){ class _am_ extends lx.BindableModel {" . $fields . ";} return new _am_; })();";
			$code = str_replace($matches[0][$i], $text, $code);
		}

		/*
		#lx:model-collection c = ModelName; =>
		const c = (function(){
			let c = new lx.ModelCollection();
			class _am_ extends lx.BindableModel { #lx:modelName ModelName; }
			c.setModelClass(_am_);
			return c;
		})();

		#lx:model-collection c2 = { x, y }; =>
		const c2 = (function(){
			let c = new lx.ModelCollection();
			class _am_ extends lx.BindableModel { field x, y; }
			c.setModelClass(_am_);
			return c;
		})();
		*/
		$regexp = '/#lx:model-collection\s*(\b[\w_][\w\d_]*\b)\s*=\s*([\w\W]+?);/';
		preg_match_all($regexp, $code, $matches);
		for ($i=0, $l=count($matches[0]); $i<$l; $i++) {
			$name = $matches[1][$i];
			$fields = $matches[2][$i];
			$fields = trim($fields, ' ');
			if ($fields{0} == '{') {
				$fields = preg_replace('/^{/', '', $fields);
				$fields = preg_replace('/}$/', '', $fields);
				$fields = preg_replace('/(\n\r|\r\n|\r|\n)$/', '', $fields);
				$fields = trim($fields, ' ');
				$fields = '#lx:schema ' . $fields;
			} else {
				$fields = '#lx:modelName ' . $fields;				
			}
			$text = "const $name = (function(){ let c=new lx.ModelCollection; class _am_ extends lx.BindableModel {" . $fields . ';} c.setModelClass(_am_); return c; })();';
			$code = str_replace($matches[0][$i], $text, $code);
		}

		//=====================================================================================================================
		//=====================================================================================================================
		//=====================================================================================================================


		$code = preg_replace_callback('/#lx:i18n(?P<therec>\(((?>[^()]+)|(?P>therec))*\))/', function($match) {
			return '\'#lx:i18n' . $match[2] . 'i18n:lx#\'';
		}, $code);

		$code = self::applyHtmlTemplater($code);

		return $code;
	}

	/**
	 *
	 * */
	public static function applyHtmlTemplater($code) {
		$reg = '/#lx:(?P<tpl>\<((?>[^<>]+)|(?P>tpl))*\>)/';
		return preg_replace_callback($reg, function($match) {
			$tpl = $match['tpl'];

			$tags = [];
			$content = [];
			$counter = -1;
			$result = '\'';

			$cutNext = function($str) use (&$tags, &$content, &$counter, &$result) {
				$str = preg_replace('/^\s*/', '', $str);

				if ($str{0} == '<') {
					if ($counter > -1) {
						$result .= '<' . end($tags);
						if ($content[$counter]['id']) {
							$result .= ' id="' . $content[$counter]['id'] . '"';
						}
						if ($content[$counter]['name']) {
							$result .= ' name="' . $content[$counter]['name'] . '"';
						}
						if (!empty($content[$counter]['css'])) {
							$css = implode(' ', $content[$counter]['css']);
							$result .= ' class="' . $css . '"';
						}
						if ($content[$counter]['style']) {
							$result .= ' style=' . $content[$counter]['style'];
						}
						if ($content[$counter]['data']) {
							$result .= ' lx-data="' . $content[$counter]['data'] . '"';
						}
						$result .= '>';
						if ($content[$counter]['text']) {
							$result .= $content[$counter]['text'];
						}
						array_pop($content);
						$counter--;
					}

					$content[] = [
						'id' => null,
						'name' => null,
						'css' => [],
						'style' => null,
						'data' => null,
						'text' => null,
						'rendered' => false,
					];
					$counter++;

					$arr = preg_split('/^<(.+?\b)/', $str, 0, PREG_SPLIT_DELIM_CAPTURE);
					$tag = $arr[1];
					$newStr = $arr[2];
					$tags[] = $tag;
					return $newStr;
				}
				if ($str{0} == '>') {
					$newStr = preg_replace('/^>/', '', $str);

					$tag = array_pop($tags);
					if ($counter > -1 && !$content[$counter]['rendered']) {
						$result .= '<' . $tag;
						if ($content[$counter]['id']) {
							$result .= ' id="' . $content[$counter]['id'] . '"';
						}
						if ($content[$counter]['name']) {
							$result .= ' name="' . $content[$counter]['name'] . '"';
						}
						if (!empty($content[$counter]['css'])) {
							$css = implode(' ', $content[$counter]['css']);
							$result .= ' class="' . $css . '"';
						}
						if ($content[$counter]['style']) {
							$result .= ' style=' . $content[$counter]['style'];
						}
						if ($content[$counter]['data']) {
							$result .= ' lx-data="' . $content[$counter]['data'] . '"';
						}
						$result .= '>';
						if ($content[$counter]['text']) {
							$result .= $content[$counter]['text'];
						}
						array_pop($content);
						$counter--;
					}
					$result .= "</$tag>";

					return $newStr;
				}
				if ($str{0} == '#') {
					if ($str{1} == '{') {
						$reg = '/^#(?P<re>{((?>[^{}]+)|(?P>re))*})/';
						$arr = preg_split($reg, $str, 0, PREG_SPLIT_DELIM_CAPTURE);
						$idCode = preg_replace('/(^{|}$)/', '', $arr[1]);
						$newStr = $arr[3];
						$content[$counter]['id'] = "'+$idCode+'";
						return $newStr;
					} else {
						$arr = preg_split('/^#(.+?\b)/', $str, 0, PREG_SPLIT_DELIM_CAPTURE);
						$id = $arr[1];
						$newStr = $arr[2];
						$content[$counter]['id'] = $id;
						return $newStr;
					}
				}
				if ($str{0} == ':') {
					if ($str{1} == '{') {
						$reg = '/^:(?P<re>{((?>[^{}]+)|(?P>re))*})/';
						$arr = preg_split($reg, $str, 0, PREG_SPLIT_DELIM_CAPTURE);
						$nameCode = preg_replace('/(^{|}$)/', '', $arr[1]);
						$newStr = $arr[3];
						$content[$counter]['name'] = "'+$nameCode+'";
						return $newStr;
					} else {
						$arr = preg_split('/^:(.+?\b)/', $str, 0, PREG_SPLIT_DELIM_CAPTURE);
						$name = $arr[1];
						$newStr = $arr[2];
						$content[$counter]['name'] = $name;
						return $newStr;
					}
				}
				if ($str{0} == '.') {
					if ($str{1} == '{') {
						$reg = '/^\.(?P<re>{((?>[^{}]+)|(?P>re))*})/';
						$arr = preg_split($reg, $str, 0, PREG_SPLIT_DELIM_CAPTURE);
						$cssCode = preg_replace('/(^{|}$)/', '', $arr[1]);
						$newStr = $arr[3];
						$content[$counter]['css'][] = "'+$cssCode+'";
						return $newStr;
					} else {
						$arr = preg_split('/^\.(.+?\b)/', $str, 0, PREG_SPLIT_DELIM_CAPTURE);
						$css = $arr[1];
						$newStr = $arr[2];
						$content[$counter]['css'][] = $css;
						return $newStr;
					}
				}
				if ($str{0} == '"') {
					$arr = preg_split('/^("[^"]*?")/', $str, 0, PREG_SPLIT_DELIM_CAPTURE);
					$style = $arr[1];
					$newStr = $arr[2];
					$content[$counter]['style'] = $style;
					return $newStr;
				}
				if ($str{0} == '[') {
					$reg = '/^(?P<re>\[((?>[^\[\]]+)|(?P>re))*\])/';
					$arr = preg_split($reg, $str, 0, PREG_SPLIT_DELIM_CAPTURE);
					$data = preg_replace('/(^\[|\]$)/', '', $arr[1]);
					$newStr = $arr[3];
					$reg = '/(?P<re>{((?>[^{}]+)|(?P>re))*})/';
					$data = preg_replace_callback($reg, function($dataMatch) {
						$res = preg_replace('/(^{|}$)/', '', $dataMatch[1]);
						return "'+$res+'";
					}, $data);
					$content[$counter]['data'] = $data;
					return $newStr;
				}
				if ($str{0} == '=') {
					$reg = '/^=(?P<re>\(((?>[^\(\)]+)|(?P>re))*\))/';
					$arr = preg_split($reg, $str, 0, PREG_SPLIT_DELIM_CAPTURE);
					$text = preg_replace('/(^\(|\)$)/', '', $arr[1]);
					$newStr = $arr[3];
					if ($text{0} == '{') {
						$text = preg_replace('/(^{|}$)/', '', $text);
						$content[$counter]['text'] = "'+$text+'";
					} else {
						$content[$counter]['text'] = $text;
					}
					return $newStr;
				}

				return $str;
			};

			while ($tpl != '') {
				$tpl = $cutNext($tpl);
			}

			$result .= '\'';
			return $result;
		}, $code);
	}
	
	/**
	 *
	 * */
	public static function applyExtendedSintaxForClasses($code) {
		$code = self::classInNamespace($code);

		// Для всех отнаследованных классов надо добавить метод постнаследования
		$reg = '/class\s+\b(.+?)\b\s+extends\s+[^{]+?(?P<re>{((?>[^{}]+)|(?P>re))*})/';
		preg_match_all($reg, $code, $matches);

		if (!empty($matches[0])) {
			foreach ($matches[0] as $i => $implement) {
				$class = $matches[1][$i];
				$implementResult = $implement;

				// Среди таких классов - отнаследованные от модели имеют свой синтаксис:
				// 1. #lx:const NAME = value;
				$implementResult = preg_replace_callback(self::inClassReg('const'), function ($matches) {
					$constString = $matches[2];

					//todo учесть возможные запятые внутри строк, сделать стандартный алгоритм такого разбиения и вынести куда-то в хелпер
					$constPareArray = preg_split('/\s*,\s*/', $constString);
					$code = '';
					foreach ($constPareArray as $constPare) {
						preg_match_all('/^([^=]+?)\s*=\s*(.+)$/', $constPare, $pare);
						$name = $pare[1][0];
						$value = $pare[2][0];
						if ($value{0} != '\'' && $value{0} != '"' && preg_match('/::/', $value)) {
							$value = eval('return ' . $value . ';');
						}
						$code .= "static get $name(){return $value;}";
					}

					return $matches[1] . $code;
				}, $implementResult);

				// 2. #lx:schema ...;
				/*
					#lx:schema
						aaa : {type:'integer', default: 11},
						bbb,
						ccc << arr[0],
						ddd << arr[1] : {type:'string', default: 'ee'};

					__setSchema() {
						this.setSchema({
							aaa:{type:'integer', default: 11},
							bbb:{},
							ccc:{ref:'arr[0]'},
							ddd:{type:'string', default: 'ee',ref:'arr[1]'};
						});
					}
				*/
				$implementResult = preg_replace_callback(self::inClassReg('schema'), function ($matches) {
					$schema = $matches[2];
					$regexp = '/(?P<therec>{((?>[^{}]+)|(?P>therec))*})/';
					preg_match_all($regexp, $schema, $defs);
					$schema = preg_replace($regexp, '№№№', $schema);
					$fields = preg_split('/[\s\r]*,[\s\r]*/', $schema);
					$index = 0;
					foreach ($fields as &$field) {
						$pare = preg_split('/[\s\r]*:[\s\r]*/', $field);
						$key = $pare[0];
						$def = (isset($pare[1])) ? $pare[1] : '{}';
						if ($def == '№№№') {
							$def = $defs[0][$index++];
						}
						if (preg_match('/<</', $key)) {
							$temp = preg_split('/[\s\r]*<<[\s\r]*/', $key);
							$key = $temp[0];
							if ($def == '{}')
								$def = '{ref:\''.$temp[1].'\'}';
							else {
								$def = preg_replace('/}$/', ',ref:\''.$temp[1].'\'}', $def);
							}
						}
						$field = "$key:$def";
					}
					unset($field);

					$code = 'static __setSchema(){this.initSchema({'. implode(',', $fields) .'});}';
					return $matches[1] . $code;
				}, $implementResult);

				// 3. #lx:behaviors ...;
				/*
				//todo
				нужно генерить код, проверяющий, что предложенные бихевиоры это реально существующие
				классы, отнаследованные от lx.Behavior
				*/
				$implementResult = preg_replace_callback(self::inClassReg('behaviors?'), function ($matches) {
					$behaviors = preg_split('/[\s\r]*,[\s\r]*/', $matches[2]);
					foreach ($behaviors as &$behavior) {
						$behavior .= '.inject(this);';
					}
					unset($behavior);
					$behaviorsCode = 'static __injectBehaviors(){' . implode(',', $behaviors) . '}';

					return $matches[1] . $behaviorsCode;
				}, $implementResult);

				// 4. #lx:modelName xxx;
				$implementResult = preg_replace_callback('/#lx:modelName[\s\r]+([^;]+)?;/', function ($matches) {
					$modelName = $matches[1];

					$mp = ModuleBuilder::active()->getModule()->getService()->modelProvider;
					$schema = $mp->getSchema($modelName);

					$fields = [];
					foreach ($schema->fieldNames() as $fieldName) {
						$field = $schema->field($fieldName);
						if ($field->isForbidden()) continue;
						$fields[] = $field->toStringForClient();
					}

					$fieldCode = 'static __setSchema(){this.initSchema({' . implode(',', $fields) . '});}';
					return $fieldCode;
				}, $implementResult);

				$code = str_replace($implement, $implementResult . "if($class.__afterDefinition)$class.__afterDefinition();", $code);
			}
		}

		return $code;
	}

	/**
	 *
	 * */
	private static function inClassReg($keyword) {
		return '/(}|;|{)[\s\r]*#lx:'.$keyword.'[\s\r]+([^;]+)?;/';
	}

	/**
	 *
	 * */
	private static function classInNamespace($code) {
		$regexp = '/(class [^{]*) #lx:namespace ([^{]*)(?P<therec>{((?>[^{}]+)|(?P>therec))*})/';

		$code = preg_replace_callback($regexp, function ($matches) {
			$namespace = trim($matches[2]);
			$class = trim($matches[1]);
			$implementation = trim($matches[3]);

			preg_match_all('/class (\b.+?\b)/', $class, $className);
			$className = $className[1][0];

			$str = "lx.createNamespace('$namespace');";
			$str .= "if('$className' in $namespace)return;";
			$str .= $class . $implementation;
			$str .= "$className.__namespace='$namespace';$namespace.$className=$className;";
			$str = '(function(){' . $str . '})();';

			return $str;
		}, $code);

		return $code;
	}
}
