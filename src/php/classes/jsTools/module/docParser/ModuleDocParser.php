<?php

namespace lx;

use lx;

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
            $map = lx::$app->jsModules->getMap();
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


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function process(string $code): array
    {
        $reg = '/(\/\*\*[\w\W]*?\*\/)?\s*(#lx:namespace\s+[\w_][\w\d_.]*;)?\s*class (\b.+?\b)([^{]*)(?P<code>{((?>[^{}]+)|(?P>code))*})/';
        preg_match_all($reg, $code, $matches);

        $classes = [];
        foreach ($matches[3] as $i => $className) {
            $classCode = $matches['code'][$i];
            $classDoc = $matches[1][$i];
            $classNamespace = $matches[2][$i];
            $classExtends = $matches[4][$i];
            $classData = $this->prepareClassData($className, $classNamespace, $classExtends, $classDoc, $classCode);
            $classes[$classData['fullName']] = $classData;
        }

        return $classes;
    }

    private function prepareClassData(
        string $className,
        string $classNamespace,
        string $classExtends,
        string $classDocString,
        string $classCode
    ): array
    {
        $namespace = $this->analiseNamespace($classNamespace);
        $extends = $this->analiseExtends($classExtends);
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
            $doc = $this->parseDoc($matches[1][$i]);
            if (preg_match('/^static\s+/', $methodName)) {
                $methodName = preg_replace('/^static\s+/', '', $methodName);
                $doc['static'] = true;
            }
            $methods[$methodName] = $doc;
        }

        return $methods;
    }

    private function parseDoc(string $docString): array
    {
        $splitter = new ModuleDocParser\DocSplitter($this->moduleName, $docString);
        return $splitter->split();
    }
    
    private function analiseNamespace(string $classNamespace): ?string
    {
        if ($classNamespace == '') {
            return null;
        }
        
        return preg_replace('/(^\s*#lx:namespace\s+|\s*;\s*$)/', '', $classNamespace);
    }

    private function analiseExtends(string $classExtends): ?string
    {
        $reg = '/extends\s+(\S+)/';
        preg_match($reg, $classExtends, $matches);
        return $matches[1] ?? null;
    }
}
