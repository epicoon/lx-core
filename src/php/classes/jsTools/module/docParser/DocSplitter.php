<?php

namespace lx\ModuleDocParser;

use lx\StringHelper;
use lx\Undefined;

class DocSplitter
{
    const ELEMENT_TYPE_COMMENT = 'comment';

    private string $moduleName;
    private string $doc;
    private string $currentDoc;
    private ?string $currentElement = null;

    public function __construct(string $moduleName, string $doc)
    {
        $this->moduleName = $moduleName;
        $arr = $this->docToArray($doc);
        $this->doc = implode(PHP_EOL, $arr);
    }

    public function split(): array
    {
        $this->currentDoc = $this->doc;

        $result = [];

        $prevDoc = $this->currentDoc;
        while ($this->currentDoc != '') {
            if ($this->currentElement === null) {
                $this->defineCurrentElement();
            }

            switch ($this->currentElement) {
                case self::ELEMENT_TYPE_COMMENT:
                    $result['comments'][] = $this->extractCommonValue();
                    break;

                case 'events':
                    $result[$this->currentElement] = $this->extractArrayValue();
                    break;

                case 'param':
                    $param = $this->extractParam();
                    $name = $param['name'];
                    unset($param['name']);
                    $result['params'][$name] = $param;
                    break;

                default:
                    $key = $this->currentElement;
                    $value = $this->extractCommonValue();
                    $result[$key] = ($value == '') ? true : $value;
            }

            if ($this->currentDoc == $prevDoc) {
                \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                    '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                    'msg' => "Module {$this->moduleName} doc parsing problem",
                ]);
                break;
            }
            $prevDoc = $this->currentDoc;
        }

        return $result;
    }

    private function docToArray(string $docString): array
    {
        $arr = preg_split('/(\r\n|\r|\n)/', $docString);
        foreach ($arr as $i => &$row) {
            $row = preg_replace('/^(\s*\/\*\*\s*|\s*\*\/\s*|\s*\* ?)/', '', $row);
            if ($row == '') {
                unset($arr[$i]);
            }
        }
        unset($row);
        return $arr;
    }

    private function defineCurrentElement(): void
    {
        if ($this->currentDoc[0] != '@') {
            $this->currentElement = self::ELEMENT_TYPE_COMMENT;
            return;
        }

        preg_match('/^@(\S+?)(?: +|(?:\r\n|\r|\n)|$)/', $this->currentDoc, $match);
        $this->currentDoc = preg_replace('/^@' . $match[1] . ' */', '', $this->currentDoc);
        $this->currentElement = $match[1];
    }

    private function extractCommonValue(): string
    {
        $reg = "/^(?:(([\w\W]*?)(?:\r\n|\r|\n))@|([\w\W]*)$)/";
        preg_match($reg, $this->currentDoc, $match);
        if (count($match) == 4) {
            $fullValue = $match[3];
            $value = $match[3];
        } else {
            $fullValue = $match[1];
            $value = $match[2];
        }
        $this->currentDoc = str_replace($fullValue, '', $this->currentDoc);
        $this->currentElement = null;
        return $value;
    }

    private function extractArrayValue(): array
    {
        $reg = '/\[[^]]+]/';
        preg_match($reg, $this->currentDoc, $match);
        $this->currentDoc = str_replace($match[0], '', $this->currentDoc);

        $str = trim($match[0], '[]');
        $str = preg_replace('/(^\s*|\s*$)/', '', $str);
        return preg_split('/\s*,\s*/', $str);
    }

    private function extractParam($param = null): array
    {
        if ($param === null) {
            $paramStr = &$this->currentDoc;
            $this->currentElement = null;
        } else {
            $paramStr = $param;
        }

        $type = null;
        $comment = null;
        $name = null;
        $attempts = 0;
        while ($attempts < 3) {
            if ($paramStr[0] == '{') {
                $type = $this->extractParamType($paramStr);
            } elseif (preg_match('/^\(:/', $paramStr)) {
                $comment = $this->extractParamComment($paramStr);
            } elseif (preg_match('/^[\[_\w]/', $paramStr)) {
                $name = $this->extractParamName($paramStr);
            }
            $attempts++;
        }

        $result = [];
        if ($name !== null) {
            $result = array_merge($result, $name);
        }
        if ($comment !== null) {
            $result['comment'] = $comment;
        }
        if ($type !== null) {
            $result = array_merge($result, $type);
        }
        return $result;
    }

    private function extractParamName(&$paramStr): ?array
    {
        if (!preg_match('/^[\[_\w]/', $paramStr)) {
            return null;
        }

        $nameReg = '/^(\[[^]]+]|\b[\w_\d]+\b)\s*/';
        preg_match($nameReg, $paramStr, $match);
        if (empty($match)) {
            return null;
        }

        $paramStr = str_replace($match[0], '', $paramStr);
        return $this->parseParamName($match[1]);
    }

    private function parseParamName(string $name): array
    {
        if ($name[0] != '[') {
            return [
                'required' => true,
                'name' => $name,
                'default' => new Undefined(),
            ];
        }

        $result = ['required' => false];
        $name = trim($name, '[]');
        $pare = preg_split('/\s*=\s*/', $name);
        $result['name'] = $pare[0];
        $result['default'] = $pare[1] ?? new Undefined();
        $result['default'] = $this->normalizeValue($result['default']);

        return $result;
    }

    private function extractParamComment(&$paramStr): ?string
    {
        if (!preg_match('/^\(:/', $paramStr)) {
            return null;
        }

        $commentReg = '/^\(:\s*(.+?)\s*:\)\s*/';
        preg_match($commentReg, $paramStr, $match);
        if (empty($match)) {
            return null;
        }

        $paramStr = str_replace($match[0], '', $paramStr);
        return $match[1];
    }

    private function extractParamType(&$paramStr): ?array
    {
        if ($paramStr[0] != '{') {
            return null;
        }

        $typeReg = '/^(?P<tp>{((?>[^{}]+)|(?P>tp))*})\s*/';
        preg_match($typeReg, $paramStr, $match);
        if (empty($match)) {
            return null;
        }

        $paramStr = str_replace($match[0], '', $paramStr);
        $definition = preg_replace('/(^\s*{\s*|\s*}\s*$)/', '', $match['tp']);
        return $this->parseParamDefinition($definition);
    }

    private function parseParamDefinition(string $definition): array
    {
        $typeAlternatives = StringHelper::smartSplit($definition, [
            'delimiter' => '|',
            'save' => ['[]', '{}', '()', '<>'],
        ]);
        $result = [];
        foreach ($typeAlternatives as $alternative) {
            $alternative = preg_replace('/^\s*|\s*$/', '', $alternative);
            preg_match('/^(Object|Array|Tuple|Dict)\s*:?\s*([\w\W]*)/', $alternative, $matches);
            if (empty($matches)) {
                $result[] = $this->parseParamType($alternative);
                continue;
            }

            $type = $matches[1];
            $typeDefinition = $matches[2];
            $map = [
                'type' => $type,
            ];
            switch ($type) {
                case 'Object':
                    $map['items'] = $this->parseObjectDefinition($typeDefinition);
                    break;
                case 'Tuple':
                    $map['items'] = $this->parseTupleDefinition($typeDefinition);
                    break;
                case 'Array':
                case 'Dict':
                    $map['items'] = $this->parseArrayDefinition($typeDefinition);
                    break;
            }

            $result[] = $map;
        }

        if (count($result) == 1) {
            return $result[0];
        }

        return ['typeAlternatives' => $result];
    }

    private function parseParamType(string $typeString): array
    {
        $type = $typeString;

        if (preg_match('/\[\]$/', $type)) {
            $type = preg_replace('/\[\]$/', '', $type);
            return [
                'type' => 'Array',
                'elems' => ['type' => $type],
            ];
        }

        $details = [];

        $enumReg = '/\s*&\s*Enum\s*\(([^)]+?)\)/';
        if (preg_match($enumReg, $type, $match)) {
            $type = str_replace($match[0], '', $type);
            $enumString = preg_replace('/^\s*|\s*$/', '', $match[1]);
            $details['enum'] = preg_split('/\s*,\s*/', $enumString);
            foreach ($details['enum'] as &$item) {
                $item = $this->normalizeValue($item);
            }
            unset($item);
        }

        $result = ['type' => $type];
        if (!empty($details)) {
            $result = array_merge($result, $details);
        }
        return $result;
    }

    private function parseObjectDefinition(string $definition): array
    {
        if (preg_match('/^#schema/', $definition)) {
            $schema = $this->parseLink($definition, 'schema');
            return $schema ? ['#schema' => $schema] : [];
        }

        $definition = preg_replace('/(^{\s*|\s*}$)/', '', $definition);
        $definitionMap = StringHelper::smartSplit($definition, [
            'delimiter' => ',',
            'save' => ['[]', '{}', '()', '<>'],
        ]);
        $result = [];
        foreach ($definitionMap as $param) {
            if (preg_match('/^#merge/', $param)) {
                $merge = $this->parseLink($param, 'merge');
                if ($merge) {
                    $result['#merge'][] = $merge;
                }
                continue;
            }

            $param = $this->extractParam($param);
            $name = $param['name'];
            unset($param['name']);
            $result[$name] = $param;
        }

        return $result;
    }

    private function parseTupleDefinition(string $definition): array
    {
        $definition = preg_replace('/(^\[\s*|\s*]$)/', '', $definition);
        $definitionMap = StringHelper::smartSplit($definition, [
            'delimiter' => ',',
            'save' => ['[]', '{}', '()', '<>'],
        ]);
        $result = [];
        foreach ($definitionMap as $elem) {
            $result[] = $this->extractParam($elem);
        }

        return $result;
    }

    private function parseArrayDefinition(string $definition): array
    {
        $definition = preg_replace('/(^\s*\<\s*|\s*\>\s*$)/', '', $definition);
        return $this->parseParamDefinition($definition);
    }

    private function parseLink(string $link, string $key): ?array
    {
        $path = trim(preg_replace('/^#' . $key . '/', '', $link), '()');
        $pathArr = explode('::', $path);
        if (count($pathArr) == 3) {
            return [
                'module' => $pathArr[0],
                'class' => $pathArr[0],
                'method' => $pathArr[1],
                'param' => $pathArr[2],
            ];
        }
        if (count($pathArr) == 4) {
            return [
                'module' => $pathArr[0],
                'class' => $pathArr[1],
                'method' => $pathArr[2],
                'param' => $pathArr[3],
            ];
        }
        return null;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function normalizeValue($value)
    {
        if (filter_var($value, FILTER_VALIDATE_INT)) {
            return (int)$value;
        }
        if ($value === '0') {
            return 0;
        }
        if ($value === 'false') {
            return false;
        }
        if ($value === 'true') {
            return true;
        }
        return $value;
    }
}
