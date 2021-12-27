<?php

namespace lx;

class PluginFrontendJsCompiler extends JsCompiler
{
    private Plugin $plugin;
    private array $compiledSnippets;

    public function __construct(Plugin $plugin)
    {
        parent::__construct($plugin->conductor, $plugin->assetManager);
        $this->plugin = $plugin;
        $this->compiledSnippets = [];
    }

    protected function compileExtensions(string $code, ?string $path = null): string
    {
        $snippetNames = array_merge(
            $this->getNamesFromSetSnippet($code),
            $this->getNamesFromAddSnippet($code),
            $this->getNamesFromAddSnippets($code)
        );
        if (empty($snippetNames)) {
            return $code;
        }

        $snippetCodes = [];
        foreach ($snippetNames as $snippetName) {
            $snippetPath = $this->plugin->conductor->getSnippetPath($snippetName);
            if (!$snippetPath || $this->snippedAlreadyCompiled($snippetPath)) {
                continue;
            }

            $snippetCodes[] = $this->compileSnippet($snippetName, $snippetPath);
        }

        $code = implode('', $snippetCodes) . $code;
        return $code;
    }

    private function getNamesFromSetSnippet(string $code): array
    {
        $reg = '/\.setSnippet\(\s*(?:[\'"]([^\'"]+?)[\'"]|{[^}]*?path\s*:\s*[\'"]([^\'"]+?)[\'"])/';
        preg_match_all($reg, $code, $matches);
        if (empty($matches[0])) {
            return [];
        }

        $result = [];
        for ($i=0, $l=count($matches[0]); $i < $l; $i++) {
            $snippetName = $matches[1][$i];
            if ($snippetName == '') {
                $snippetName = $matches[2][$i];
            }

            $result[] = $snippetName;
        }

        return $result;
    }

    private function getNamesFromAddSnippet(string $code): array
    {
        $reg = '/\.addSnippet\(\s*[\'"]([^\'"]+?)[\'"]/';
        preg_match_all($reg, $code, $matches);
        if (empty($matches[0])) {
            return [];
        }

        return $matches[1];
    }

    private function getNamesFromAddSnippets(string $code): array
    {
        $reg = '/\.addSnippets(?P<therec>\(((?>[^()]+)|(?P>therec))*\))/';
        preg_match_all($reg, $code, $argsMatches);
        if (empty($argsMatches[0])) {
            return [];
        }

        $result = [];
        for ($i=0, $l=count($argsMatches[0]); $i < $l; $i++) {
            $args = $argsMatches['therec'][$i];
            if (preg_match('/^\(\s*{/', $args)) {
                preg_match_all('/([\w\d_]+?)\s*:\s*(?P<therec>{((?>[^{}]+)|(?P>therec))*})/', $args, $matches);
                $result = array_values(array_unique(array_merge($result, $matches[1])));
            } elseif (preg_match('/^\(\s*\[\s*{/', $args)) {
                preg_match_all('/path\s*:\s*[\'"]([^\'"]+?)[\'"]/', $args, $matches);
                $result = array_values(array_unique(array_merge($result, $matches[1])));
            } elseif (preg_match('/^\(\s*\[/', $args)) {
                preg_match_all('/[\'"]([^\'"]+?)[\'"]/', $args, $matches);
                $result = array_values(array_unique(array_merge($result, $matches[1])));
            }
        }

        return $result;
    }

    private function compileSnippet(string $name, string $path): string
    {
        $code = $this->compileFile($path);
        $code = "lx.SnippetMap.registerSnippetMaker('$name', function(Plugin, Snippet){{$code}});";
        $this->compiledSnippets[] = $snippetPath;
        return $code;
    }

    private function snippedAlreadyCompiled(string $path): bool
    {
        return in_array($path, $this->compiledSnippets);
    }
}
