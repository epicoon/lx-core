<?php

namespace lx;

class SyntaxExtender
{
	private JsCompiler $compiler;
	private ?string $currentPath;
	private ?Service $currentService;

	public function __construct(JsCompiler $compiler)
    {
		$this->compiler = $compiler;

		$this->currentPath = null;
		$this->currentService = null;
	}

	public function applyExtendedSyntax(string $code, ?string $path): string
    {
		// #lx:php(php-code) => mixed
		$reg = '/#lx:php(?P<therec>\(((?>[^()]+)|(?P>therec))*\))/';
		$code = preg_replace_callback($reg, function($match) {
			$phpCode = 'return ' . $match[1] . ';';
			$result = eval($phpCode);

			if (is_string($result)) {
				$result = "'$result'";
			} elseif (is_array($result)) {
				$result = CodeConverterHelper::arrayToJsCode($result);
			} elseif (is_object($result)) {
				if (method_exists($result, 'toString')) {
					$result = "'{$result->toString()}'";
				} else {
					$arr = json_decode(json_encode($result), true);
					$result = CodeConverterHelper::arrayToJsCode($arr);
				}
			}

			return $result;
		}, $code);

		// ? >#id => lx.DomSelector.getWidgetById('id')
		$code = preg_replace_callback('/\?>#(\b.+?\b)/', function($matches) {
			return 'lx.DomSelector.getWidgetById(\'' . $matches[1] . '\')';
		}, $code);
		// ? >#{id} => lx.DomSelector.getWidgetById(id)
		$code = preg_replace_callback('/\?>#{(.+?)}/', function($matches) {
			return 'lx.DomSelector.getWidgetById(' . $matches[1] . ')';
		}, $code);

		// ? >.class => lx.DomSelector.getWidgetByClass('class')
		$code = preg_replace_callback('/\?>\.(\b.+?\b)/', function($matches) {
			return 'lx.DomSelector.getWidgetByClass(\'' . $matches[1] . '\')';
		}, $code);
		// ? >.{class} => lx.DomSelector.getWidgetByClass(class)
		$code = preg_replace_callback('/\?>\.{(.+?)}/', function($matches) {
			return 'lx.DomSelector.getWidgetByClass(' . $matches[1] . ')';
		}, $code);

		// ? >name => lx.DomSelector.getWidgetByName('name')
		$code = preg_replace_callback('/\?>(\b.+?\b)/', function($matches) {
			return 'lx.DomSelector.getWidgetByName(\'' . $matches[1] . '\')';
		}, $code);
		// ? >{name} => lx.DomSelector.getWidgetByName(name)
		$code = preg_replace_callback('/\?>{(.+?)}/', function($matches) {
			return 'lx.DomSelector.getWidgetByName(' . $matches[1] . ')';
		}, $code);

		// self:: => this.constructor.
		$code = str_replace('self::', 'this.constructor.', $code);

		// Plugin->key => Plugin.get('key')
		// Snippet->key => Snippet.get('key')
		$code = preg_replace_callback('/\b(Plugin|Snippet)->([\w_][\w\d]*?\b)/', function($matches) {
			return $matches[1] . '.get(\'' . $matches[2] . '\')';
		}, $code);

		// Plugin->>key => Plugin.find('key')
		// Snippet->>key => Snippet.find('key')
		$code = preg_replace_callback('/\b(Plugin|Snippet)->>([\w_][\w\d]*?\b)/', function($matches) {
			return $matches[1] . '.find(\'' . $matches[2] . '\')';
		}, $code);

		// element->child => element.childrenByKeys.child
		$code = preg_replace_callback('/([\w\d_)\[\]])->([\w_$])/', function($matches) {
			return $matches[1] . '.childrenByKeys.' . $matches[2];
		}, $code);

		// element->>child => element.find('child')
		$code = preg_replace_callback('/([\w\d_)\[\]])->>([\w_][\w\d]*?\b)/', function($matches) {
			return $matches[1] . '.find(\'' . $matches[2] . '\')';
		}, $code);

		// element~>key => element.neighbor('key')
		$code = preg_replace_callback('/([\w\d_])~>([\w_][\w\d_]*?\b)/', function($matches) {
			return $matches[1] . '.neighbor(\'' . $matches[2] . '\')';
		}, $code);

		//==============================================================================================================
		//==============================================================================================================
		//==============================================================================================================

		// ^self::method(arg1, arg2) => self::ajax('method', [arg1, arg2]).send()
		$regexp = '/\^this\.constructor\.([\w_][\w\d_]*?)(?P<therec>\(((?>[^()]+)|(?P>therec))*\))/';
		preg_match_all($regexp, $code, $matches);
		for ($i=0, $l=count($matches[0]); $i<$l; $i++) {
			$method = $matches[1][$i];
			$args = $matches[2][$i];
			$args = preg_replace('/(^\(|\)$)/', '', $args);

			$text = "this.constructor.ajax('$method',[$args]).send()";
			$code = str_replace($matches[0][$i], $text, $code);
		}

		// ^Resp.method(arg1, arg2) => __plugin__.ajax('Resp.method', [arg1, arg2]).send()
		$regexp = '/\^([\w_][\w\d_]*?\.[\w_][\w\d_]*?)(?P<therec>\(((?>[^()]+)|(?P>therec))*\))/';
		preg_match_all($regexp, $code, $matches);
		for ($i=0, $l=count($matches[0]); $i<$l; $i++) {
			$method = $matches[1][$i];
			$args = $matches[2][$i];
			$args = preg_replace('/(^\(|\)$)/', '', $args);

			$text = "__plugin__.ajax('$method',[$args]).send()";
			$code = str_replace($matches[0][$i], $text, $code);
		}

		//==============================================================================================================
		//==============================================================================================================
		//==============================================================================================================

        // #lx:model <ModelName>;
        // =>
        // (function(){ class _am_ extends lx.BindableModel { #lx:schema <ModelName>; } return new _am_; })();

        // #lx:model { a, b };
        // =>
        // (function(){ class _am_ extends lx.BindableModel { #lx:schema a, b; } return new _am_; })();

        $regexp = '/#lx:model\s*(<(?:[\w\d_])+?>|(?P<therec>{((?>[^{}]+)|(?P>therec))*}));/';
        $code = preg_replace_callback($regexp, function ($matches) {
            $schema = $matches[1];
            $schema = preg_replace('/(^\{|\}$)/', '', $schema);
            return "(function(){ class _am_ extends lx.BindableModel { #lx:schema $schema; } return new _am_; })();";
        }, $code);

		//=====================================================================================================================
		//=====================================================================================================================
		//=====================================================================================================================

        // #lx:modelSchema <Car>  =>  {/* schema */}
        $regexp = '/#lx:modelSchema\s*<([^>]+?)>/';
        $code = preg_replace_callback($regexp, function ($matches) use ($path) {
            $modelName = $matches[1];
            $schemaString = $this->getModelSchemaString($modelName, $path);
            if ($schemaString === null) {
                return '{}';
            }
            return $schemaString;
        }, $code);

        //=====================================================================================================================
        //=====================================================================================================================
        //=====================================================================================================================


		$reg = '/\'?#lx:i18n(?P<therec>\(((?>[^()]+)|(?P>therec))*\))\'?/';
		$code = preg_replace_callback($reg, function($match) {
			return '\'#lx:i18n' . addcslashes(trim($match['therec'], '()'), '\'') . 'i18n:lx#\'';
		}, $code);

		$code = $this->applyHtmlTemplater($code);
		$code = $this->applyExtendedSyntaxForClasses($code, $path);

		return $code;
	}

	private function applyExtendedSyntaxForClasses(string $code, ?string $path): string
    {
		// Работа со всеми классами
		$reg = '/(#lx:namespace\s+[\w_][\w\d_.]*;)?\s*class\s+\b(.+?)\b([^{]*)(?P<re>{((?>[^{}]+)|(?P>re))*})/';
		preg_match_all($reg, $code, $matches);

		if (!empty($matches[0])) {
			foreach ($matches[0] as $i => $implement) {
			    if (preg_match('/\bclass\s+\b.+?\b\s+(?:{|#)/', $matches['re'][0])) {
			        $processedRe = $this->applyExtendedSyntaxForClasses($matches['re'][0], $path);
			        $implementTemp = str_replace($matches['re'][0], $processedRe, $implement);
                } else {
                    $implementTemp = $implement;
                }

				$class = $matches[2][$i];
                if ($matches[1][$i] == '') {
                    $namespace = null;
                } else {
    				preg_match('/#lx:namespace\s+([_\w\d.]+?)\s*;/', $matches[1][$i], $namespace);
    				$namespace = $namespace[1] ?? null;
                }
                $implementResult = preg_replace(
                    '/#lx:namespace\s+'.$namespace.'\s*;\s*/',
                    '',
                    $implementTemp
                );

				// 1. #lx:const NAME = value;
				$implementResult = preg_replace_callback($this->inClassReg('const'), function ($matches) {
					$constString = $matches[2];

					$constPareArray = StringHelper::smartSplit($constString, [
						'delimiter' => ',',
						'save' => ['[]', '{}', '"', "'"],
					]);

					$code = '';
					foreach ($constPareArray as $constPare) {
					    if ($constPare === '') {
					        continue;
                        }

						preg_match_all('/^([^=]+?)\s*=\s*([\w\W]+)\s*$/', $constPare, $pare);
						$name = $pare[1][0];
						$value = $pare[2][0];
						if ($value[0] != '\'' && $value[0] != '"' && preg_match('/::/', $value)) {
							$value = eval('return ' . $value . ';');
							if (is_string($value)) $value = "'$value'";
							else if (is_array($value)) $value = json_encode($value);
						}
						$code .= "static get $name(){return $value;}";
					}

					return $matches[1] . $code;
				}, $implementResult);
				
				// 2. #lx:server methodName() {}  |  #lx:client methodName() {}
				$regexpTail = '\s*[^\(]+?\([^\)]*?\)\s*(?P<re>{((?>[^{}]+)|(?P>re))*})/';
				if ($this->compiler->contextIsClient()) {
					$regexp = '/#lx:client/';
					$implementResult = preg_replace($regexp, '', $implementResult);
					$regexp = '/#lx:server' . $regexpTail;
					$implementResult = preg_replace($regexp, '', $implementResult);
				} elseif ($this->compiler->contextIsServer()) {
					$regexp = '/#lx:server/';
					$implementResult = preg_replace($regexp, '', $implementResult);
					$regexp = '/#lx:client' . $regexpTail;
					$implementResult = preg_replace($regexp, '', $implementResult);
				}

				if ($namespace) {
					$str = "lx.createNamespace('$namespace');";
					$str .= "if('$class' in lx.globalContext.$namespace)return;";
					$str .= $implementResult;
					$str .= "$class.__namespace='$namespace';lx.globalContext.$namespace.$class=$class;";
					$implementResult = $str;
				}

				$implementResult .= "if($class.__afterDefinition)$class.__afterDefinition();";

				if ($namespace) {
					$implementResult = '(function(){' . $implementResult . '})();';
				}

				$code = str_replace($implement, $implementResult, $code);
			}
		}

		// Работа с наследуемыми классами
		$reg = '/class\s+\b(.+?)\b\s+extends\s+[^{]+?(?P<re>{((?>[^{}]+)|(?P>re))*})/';
		preg_match_all($reg, $code, $matches);
		if (!empty($matches[0])) {
			foreach ($matches[0] as $i => $implement) {
				$class = $matches[1][$i];
				$implementResult = $implement;

				// Среди таких классов - отнаследованные от модели имеют свой синтаксис:
				// 1. #lx:schema ...;
				/*
                    #lx:schema <ModelName>;

					#lx:schema
						aaa : {type:'integer', default: 11},
						bbb,
						ccc << arr[0],
						ddd << arr[1] : {type:'string', default: 'ee'};

					static __setSchema() {
						this.initSchema({
							aaa:{type:'integer', default: 11},
							bbb:{},
							ccc:{ref:'arr[0]'},
							ddd:{type:'string', default: 'ee',ref:'arr[1]'};
						});
					}
				*/
				$implementResult = preg_replace_callback(
                    $this->inClassReg('schema'),
                    function ($matches) use ($path) {
                        $schema = $matches[2];
                        if ($schema == '') {
                            return $matches[1];
                        }

                        if ($schema[0] == '<') {
                            $schema = trim($schema, '><');
                            $schemaCode = $this->getModelSchemaString($schema, $path);
                            if ($schemaCode === null) {
                                return $matches[1];
                            }
                        } else {
                            $regexp = '/(?P<therec>{((?>[^{}]+)|(?P>therec))*})/';
                            preg_match_all($regexp, $schema, $defs);
                            $schema = preg_replace($regexp, '№№№', $schema);
                            $fields = preg_split('/\s*,\s*/', $schema);
                            $index = 0;
                            foreach ($fields as &$field) {
                                $field = preg_replace('/\s*$/','', $field);
                                $pare = preg_split('/\s*:\s*/', $field);
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
                            $schemaCode = '{' . implode(',', $fields) . '}';
                        }

                        $code = 'static __setSchema(){this.initSchema(' . $schemaCode . ');}';
                        return $matches[1] . $code;
                    },
                    $implementResult
                );

				// 2. #lx:behaviors ...;
				/*
				//todo
				нужно генерить код, проверяющий, что предложенные бихевиоры это реально существующие
				классы, отнаследованные от lx.Behavior
				*/
				$implementResult = preg_replace_callback($this->inClassReg('behaviors?'), function ($matches) {
					$behaviors = preg_split('/[\s\r]*,[\s\r]*/', $matches[2]);
					foreach ($behaviors as &$behavior) {
						$behavior .= '.injectInto(this);';
					}
					unset($behavior);
					$behaviorsCode = 'static __injectBehaviors(){' . implode(',', $behaviors) . '}';

					return $matches[1] . $behaviorsCode;
				}, $implementResult);

				$code = str_replace($implement, $implementResult, $code);
			}
		}

		return $code;
	}

	//TODO есть сервис шаблонизации, этот функционал можно переосмыслить и перенести туда
	private function applyHtmlTemplater(string $code): string
    {
		$reg = '/#lx:(?P<tpl>\<((?>[^<>]+)|(?P>tpl))*\>)/';
		return preg_replace_callback($reg, function($match) {
			$tpl = $match['tpl'];

			$tags = [];
			$content = [];
			$counter = -1;
			$result = '\'';

			$cutNext = function($str) use (&$tags, &$content, &$counter, &$result) {
				$str = preg_replace('/^\s*/', '', $str);

				if ($str[0] == '<') {
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
				if ($str[0] == '>') {
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
				if ($str[0] == '#') {
					if ($str[1] == '{') {
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
				if ($str[0] == ':') {
					if ($str[1] == '{') {
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
				if ($str[0] == '.') {
					if ($str[1] == '{') {
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
				if ($str[0] == '"') {
					$arr = preg_split('/^("[^"]*?")/', $str, 0, PREG_SPLIT_DELIM_CAPTURE);
					$style = $arr[1];
					$newStr = $arr[2];
					$content[$counter]['style'] = $style;
					return $newStr;
				}
				if ($str[0] == '[') {
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
				if ($str[0] == '=') {
					$reg = '/^=(?P<re>\(((?>[^\(\)]+)|(?P>re))*\))/';
					$arr = preg_split($reg, $str, 0, PREG_SPLIT_DELIM_CAPTURE);
					$text = preg_replace('/(^\(|\)$)/', '', $arr[1]);
					$newStr = $arr[3];
					if ($text[0] == '{') {
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

	private function inClassReg(string $keyword): string
    {
		return '/(}|;|{)\s*#lx:'.$keyword.'[\s\r]+([^;]+)?[\s\r]*;/';
	}

	private function getCurrentService(?string $path): ?Service
    {
		if ($this->currentPath == $path) {
			return $this->currentService;
		}

		$this->currentPath = $path;
		$this->currentService = \lx::$app->serviceProvider->setFileName($path)->getService();
		return $this->currentService;
	}
    
    private function getModelSchemaString(?string $modelName, ?string $path): ?string
    {
        if (!$modelName) {
            return null;
        }

        $service = $this->getCurrentService($path);
        if (!$service) {
            return null;
        }

        $manager = $service->modelManager;
        if (!$manager) {
            return null;
        }

        $schema = $manager->getModelSchema($modelName);
        $schemaArray = $schema ? ($schema->toArray())['fields'] : [];
        return CodeConverterHelper::arrayToJsCode($schemaArray);
    }
}
