<?php

namespace lx;

/**
 * Class PluginFrontendJsCompiler
 * @package lx
 */
class PluginFrontendJsCompiler extends JsCompiler
{
    /** @var Plugin */
    private $plugin;

    /** @var array */
    private $compiledSnippets;

    /**
     * PluginFrontendJsCompiler constructor.
     * @param Plugin $plugin
     */
    public function __construct($plugin)
    {
        parent::__construct($plugin->conductor);
        $this->plugin = $plugin;
        $this->compiledSnippets = [];
    }

    /**
     * @param string $code
     * @param string|null $path
     * @return string
     */
    protected function compileExtensions($code, $path = null)
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

    /**
     * @param string $code
     * @return array
     */
    private function getNamesFromSetSnippet($code)
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

    /**
     * @param string $code
     * @return array
     */
    private function getNamesFromAddSnippet($code)
    {
        $reg = '/\.addSnippet\(\s*[\'"]([^\'"]+?)[\'"]/';
        preg_match_all($reg, $code, $matches);
        if (empty($matches[0])) {
            return [];
        }

        return $matches[1];
    }

    /**
     * @param string $code
     * @return array
     */
    private function getNamesFromAddSnippets($code)
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

    /**
     * @param string $name
     * @param string $path
     * @return string
     */
    private function compileSnippet($name, $path)
    {
        $code = $this->compileFile($path);
        $code = "lx.SnippetMap.registerSnippetMaker('$name', function(Plugin, Snippet){{$code}});";
        $this->compiledSnippets[] = $snippetPath;
        return $code;
    }

    /**
     * @param string $path
     * @return bool
     */
    private function snippedAlreadyCompiled($path)
    {
        return in_array($path, $this->compiledSnippets);
    }
}
