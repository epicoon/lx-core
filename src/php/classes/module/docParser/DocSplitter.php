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

        $definition = null;
        $comment = null;
        $name = null;
        $attempts = 0;
        while ($attempts < 3) {
            if ($paramStr[0] == '{') {
                $definition = $this->extractParamDefinition($paramStr);
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
        if ($definition !== null) {
            $result = array_merge($result, $definition);
        }
        return $result;
    }

    private function extractParamDefinition(&$paramStr): ?array
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
        $definition = preg_replace('/(^{|}$)/', '', $match['tp']);
        return $this->parseParamDefinition($definition);
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

    private function parseParamDefinition(string $definition): array
    {
        $definitionArr = StringHelper::smartSplit($definition, [
            'delimiter' => ':',
            'save' => ['[]', '{}', '()'],
        ]);
        //TODO если count < 1 || count > 2

        if (count($definitionArr) == 1) {
            return $this->parseParamType($definitionArr[0]);
        }

        $result = $this->parseParamType($definitionArr[0]);
        $type = (array)$result['type'];
        switch (true) {
            case (in_array('Object', $type)):
                $result['fields'] = $this->parseObjectDefinition($definitionArr[1]);
                break;
            case (in_array('Array', $type)):
                $result['elems'] = $this->parseArrayDefinition($definitionArr[1]);
                break;
        }

        return $result;
    }

    private function parseParamType(string $typeString): array
    {
        $type = $typeString;

        $details = [];

        $enumReg = '/\s*&\s*Enum\s*\(([^)]+?)\)/';
        if (preg_match($enumReg, $type, $match)) {
            $type = str_replace($match[0], '', $type);
            $details['enum'] = preg_split('/\s*,\s*/', $match[1]);
            foreach ($details['enum'] as &$item) {
                $item = $this->normalizeValue($item);
            }
            unset($item);
        }

        $types = preg_split('/\s*\|\s*/', $type);
        $result = (count($types) == 1)
            ? ['type' => $types[0]]
            : ['type' => $types];
        if (!empty($details)) {
            $result = array_merge($result, $details);
        }
        return $result;
    }

    private function parseArrayDefinition(string $definition): array
    {
        $definition = preg_replace('/(^\[\s*|\s*]$)/', '', $definition);
        $definitionMap = StringHelper::smartSplit($definition, [
            'delimiter' => ',',
            'save' => ['[]', '{}', '()'],
        ]);
        $result = [];
        foreach ($definitionMap as $elem) {
            $result[] = $this->extractParam($elem);
        }

        return $result;
    }

    private function parseObjectDefinition(string $definition): array
    {
        if (preg_match('/^#schema/', $definition)) {
            return [
                '#schema' => $this->parseLink($definition, 'schema'),
            ];
        }

        $definition = preg_replace('/(^{\s*|\s*}$)/', '', $definition);
        $definitionMap = StringHelper::smartSplit($definition, [
            'delimiter' => ',',
            'save' => ['[]', '{}', '()'],
        ]);
        $result = [];
        foreach ($definitionMap as $param) {
            if (preg_match('/^#merge/', $param)) {
                $result['#merge'][] = $this->parseLink($param, 'merge');
                continue;
            }

            $param = $this->extractParam($param);
            $name = $param['name'];
            unset($param['name']);
            $result[$name] = $param;
        }

        return $result;
    }

    private function parseLink(string $link, string $key): array
    {
        $path = trim(preg_replace('/^#' . $key . '/', '', $link), '()');
        $pathArr = explode('::', $path);
        return [
            'class' => $pathArr[0],
            'method' => $pathArr[1],
            'param' => $pathArr[2],
        ];
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
