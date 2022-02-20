<?php

namespace lx;

use lx\ModuleDocParser\DocSplitter;

require_once(__DIR__ . '/DocSplitter.php');

class ModuleDocParser
{
    private ?string $moduleName = null;
    private ?FileInterface $moduleFile = null;
    private ?string $moduleCode = null;

    public function setName(string $name): ModuleDocParser
    {
        $this->moduleName = $name;
        return $this;
    }

    public function setFile(FileInterface $file): ModuleDocParser
    {
        $this->file = $file;
        return $this;
    }

    public function setCode(string $code): ModuleDocParser
    {
        $this->moduleCode = $code;
        return $this;
    }

    public function getFile(): ?FileInterface
    {
        if ($this->moduleFile) {
            return $this->moduleFile;
        }

        if ($this->moduleName) {
            $map = (new JsModuleMap())->getMap();
            if (!array_key_exists($this->moduleName, $map)) {
                return null;
            }
            $this->moduleFile = new File($map[$this->moduleName]['path']);
            return $this->moduleFile;
        }

        return null;
    }

    public function getCode(): ?string
    {
        if ($this->moduleCode) {
            return $this->moduleCode;
        }

        $file = $this->getFile();
        if ($file && $file->exists()) {
            $this->moduleCode = $file->get();
            return $this->moduleCode;
        }

        return null;
    }

    public function parse(): array
    {
        $code = $this->getCode();
        if (!$code) {
            return [];
        }

        return $this->process($code);
    }

    public static function parseAll(): array
    {
        $map = (new JsModuleMap())->getMap();
        $result = [];
        foreach ($map as $moduleName => $moduleData) {
            $moduleData = self::parseByData($moduleData);
            $result[$moduleName] = $moduleData;
        }
        return $result;
    }

    public static function parseModules(array $modules): array
    {
        $map = (new JsModuleMap())->getMap();
        $result = [];
        foreach ($modules as $moduleName) {
            if (!array_key_exists($moduleName, $map)) {
                \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                    '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                    'msg' => "Module $moduleName not found",
                ]);
                continue;
            }

            $moduleData = self::parseByData($map[$moduleName]);
            $result[$moduleName] = $moduleData;
        }
        return $result;
    }

    private static function parseByData($moduleData): array
    {
        $file = new File($moduleData['path']);
        $parser = new ModuleDocParser();
        $parser->setName($moduleData['name'])
            ->setFile($file);
        return $parser->parse();
    }

    private function process(string $code): array
    {
        $reg = '/(\/\*\*[\w\W]*?\*\/)?\s*class (\b.+?\b)([^{]*)(?P<code>{((?>[^{}]+)|(?P>code))*})/';
        preg_match_all($reg, $code, $matches);

        $classes = [];
        foreach ($matches[2] as $i => $className) {
            $classCode = $matches['code'][$i];
            $classDoc = $matches[1][$i];
            $classExtends = $matches[3][$i];
            $classData = $this->prepareClassData($className, $classExtends, $classDoc, $classCode);
            $classes[$classData['fullName']] = $classData;
        }

        return $classes;
    }

    private function prepareClassData(
        string $className,
        string $classExtends,
        string $classDocString,
        string $classCode
    ): array
    {
        list($extends, $namespace) = $this->analiseExtends($classExtends);
        $classDoc = $this->parseDoc($classDocString);
        $codeMap = $this->analiseCode($classCode);
        return [
            'name' => $className,
            'namespace' => $namespace,
            'fullName' => $namespace ? "$namespace.$className" : $className,
            'extends' => $extends,
            'doc' => $classDoc,
            'methods' => $codeMap,
        ];
    }

    private function analiseCode(string $classCode): array
    {
        $reg = '/(\/\*\*[\w\W]*?\*\/)\s+([^(]+)/';
        preg_match_all($reg, $classCode, $matches);

        $methods = [];
        foreach ($matches[2] as $i => $methodName) {
            $methods[$methodName] = $this->parseDoc($matches[1][$i]);
        }

        return $methods;
    }

    private function parseDoc(string $docString): array
    {
        $splitter = new ModuleDocParser\DocSplitter($this->moduleName, $docString);
        return $splitter->split();
    }

    private function analiseExtends(string $classExtends): array
    {
        $reg = '/extends\s+(\S+)/';
        preg_match($reg, $classExtends, $matches);
        $result[] = $matches[1] ?? null;

        $reg = '/#lx:namespace\s+(\S+)/';
        preg_match($reg, $classExtends, $matches);
        $result[] = $matches[1] ?? null;

        return $result;
    }
}
