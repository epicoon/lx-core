<?php

namespace lx;

class SintaxExtender {
	/**
	 *
	 * */
	public static function applyExtendedSintax($code) {
		// ? >#id => lx.WidgetHelper.getById('id')
		$code = preg_replace_callback('/\?>#(\b.+?\b)/', function($matches) {
			return 'lx.WidgetHelper.getById("' . $matches[1] . '")';
		}, $code);
		// ? >#{id} => lx.WidgetHelper.getById(id)
		$code = preg_replace_callback('/\?>#{(.+?)}/', function($matches) {
			return 'lx.WidgetHelper.getById(' . $matches[1] . ')';
		}, $code);

		// ? >.class => lx.WidgetHelper.getByClass('class')
		$code = preg_replace_callback('/\?>\.(\b.+?\b)/', function($matches) {
			return 'lx.WidgetHelper.getByClass("' . $matches[1] . '")';
		}, $code);
		// ? >.{class} => lx.WidgetHelper.getByClass(class)
		$code = preg_replace_callback('/\?>\.{(.+?)}/', function($matches) {
			return 'lx.WidgetHelper.getByClass(' . $matches[1] . ')';
		}, $code);

		// ? >name => lx.WidgetHelper.getByName('name')
		$code = preg_replace_callback('/\?>(\b.+?\b)/', function($matches) {
			return 'lx.WidgetHelper.getByName("' . $matches[1] . '")';
		}, $code);
		// ? >{name} => lx.WidgetHelper.getByName(name)
		$code = preg_replace_callback('/\?>{(.+?)}/', function($matches) {
			return 'lx.WidgetHelper.getByName(' . $matches[1] . ')';
		}, $code);

		// self:: => this.constructor.
		$code = str_replace('self::', 'this.constructor.', $code);

		// Module->key => Module.get("key")
		$code = preg_replace_callback('/\bModule->([\w_][\w\d]*?\b)/', function($matches) {
			return 'Module.get("' . $matches[1] . '")';
		}, $code);

		// Module->>key => Module.find("key")
		$code = preg_replace_callback('/\bModule->>([\w_][\w\d]*?\b)/', function($matches) {
			return 'Module.find("' . $matches[1] . '")';
		}, $code);

		// element->child => element.children.child
		$code = preg_replace_callback('/([\w\d_)\[\]])->([\w_$])/', function($matches) {
			return $matches[1] . '.children.' . $matches[2];
		}, $code);

		// element->>child => element.find("child")
		$code = preg_replace_callback('/([\w\d_)\[\]])->>([\w_][\w\d]*?\b)/', function($matches) {
			return $matches[1] . '.find("' . $matches[2] . '")';
		}, $code);

		// element~>key => element.neighbor("key")
		$code = preg_replace_callback('/([\w\d_])~>([\w_][\w\d_]*?\b)/', function($matches) {
			return $matches[1] . '.neighbor("' . $matches[2] . '")';
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

		// #lx:model e = { a, b, sum }; => const e = (function(){ class _am_ extends lx.Model { field a, b, sum; } return new _am_; })();
		// #lx:model e = #lx:MODEL_NAME someName; => const e = (function(){ class _am_ extends lx.Model { #lx:MODEL_NAME someName; } return new _am_; })();
		$regexp = '/#lx:model\s*(\b[\w_][\w\d_]*\b)\s*=\s*([\w\W]+?);/';
		preg_match_all($regexp, $code, $matches);
		for ($i=0, $l=count($matches[0]); $i<$l; $i++) {
			$name = $matches[1][$i];
			$fields = $matches[2][$i];
			$fields = trim($fields, ' ');
			if (preg_match('/^{/', $fields)) $fields = preg_replace('/^{/', '', $fields);
			if (preg_match('/}$/', $fields)) $fields = preg_replace('/}$/', '', $fields);
			$fields = trim($fields, ' ');
			if ($fields{0} != '#') $fields = '#lx:schema ' . $fields;
			$text = "const $name = (function(){ class _am_ extends lx.Model {" . $fields . ";} return new _am_; })();";
			$code = str_replace($matches[0][$i], $text, $code);
		}

		/*
		#lx:model-collection c = #lx:MODEL_NAME someName; =>
		const c = (function(){
			let c = new lx.ModelCollection();
			class _am_ extends lx.Model { #lx:MODEL_NAME someName; }
			c.setModelClass(_am_);
			return c;
		})();

		#lx:model-collection c2 = { x, y }; =>
		const c2 = (function(){
			let c = new lx.ModelCollection();
			class _am_ extends lx.Model { field x, y; }
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
			$fields = preg_replace('/^{/', '', $fields);
			$fields = preg_replace('/}$/', '', $fields);
			$fields = preg_replace('/(\n\r|\r\n|\r|\n)$/', '', $fields);
			$fields = trim($fields, ' ');
			if ($fields{0} != '#') $fields = '#lx:schema ' . $fields;
			$text = "const $name = (function(){ let c=new lx.ModelCollection; class _am_ extends lx.Model {" . $fields . ';} c.setModelClass(_am_); return c; })();';
			$code = str_replace($matches[0][$i], $text, $code);
		}

		return $code;
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
				// 1. #lx:schema ...;
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

				// 2. #lx:behaviors ...;
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

				// 3. #lx:MODEL_NAME xxx;
				$implementResult = preg_replace_callback('/#lx:MODEL_NAME[\s\r]+([^;]+)?;/', function ($matches) {
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

			$str = "if(window.$namespace === undefined)window.$namespace = {};";
			$str .= "if('$className' in $namespace)return;";
			$str .= $class . $implementation;
			$str .= "$className.__namespace='$namespace';$namespace.$className=$className;";
			$str = '(function(){' . $str . '})();';

			return $str;
		}, $code);

		return $code;
	}
}
