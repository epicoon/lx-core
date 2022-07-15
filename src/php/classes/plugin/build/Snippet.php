<?php

namespace lx;

class Snippet
{
    private File $file;
    private array $attributes = [];
    private array $metaData = [];
    private ?string $renderIndex = null;
    private array $pluginModifications = [];
    private array $self = [];
    private string $htmlContent = '';
    private array $lx = [];
    private ?string $js = null;
    private array $dependencies = [];
    private array $fileDependencies = [];
    private array $innerSnippetKeys = [];

    public function __construct(SnippetBuildContext $context, array $data)
    {
        $this->snippetBuildContext = $context;
        $this->pluginBuildContext = $context->getPluginBuildContext();
        $this->parent = false;
        $this->attributes = $data['attributes'] ?? [];
        $this->renderIndex = $data['index'];

        $this->retrieveFile($data);
    }

    public function getPlugin(): Plugin
    {
        return $this->pluginBuildContext->getPlugin();
    }

    public function getRenderIndex(): ?string
    {
        return $this->renderIndex;
    }

    public function setPluginModifications(array $data): void
    {
        $this->pluginModifications = $data;
    }

    public function getPluginModifications(): array
    {
        return $this->pluginModifications;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getInnerSnippetKeys(): array
    {
        return $this->innerSnippetKeys;
    }

    public function getFile(): File
    {
        return $this->file;
    }

    public function setDependencies(array $dependencies, array $files): void
    {
        $this->dependencies = $dependencies;
        $this->fileDependencies = $files;
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function getFileDependencies(): array
    {
        return $this->fileDependencies;
    }

    public function getBuildData(): array
    {
        return [
            'filePath' => $this->file->getPath(),
            'attributes' => $this->attributes,
        ];
    }

    public function applyBuildData(array $data): void
    {
        if (!empty($data['attributes'])) {
            $this->attributes += $data['attributes'];
        }

        $this->self = $data['selfData'];
        $this->htmlContent = $data['html'];
        $this->lx = $data['lx'];
        $this->js = $data['js'];
        $this->metaData = $data['meta'];

        $this->runBuildProcess();
    }

    public function getData(): array
    {
        $hasContent = function ($field) {
            return !($field === [] || $field === '' || $field === null);
        };

        $result = [];
        if ($hasContent($this->attributes)) $result['attributes'] = $this->attributes;
        if ($hasContent($this->self)) $result['self'] = $this->self;
        if ($hasContent($this->htmlContent)) $result['html'] = $this->htmlContent;
        if ($hasContent($this->lx)) $result['lx'] = $this->lx;
        if ($hasContent($this->js)) $result['js'] = $this->js;
        if ($hasContent($this->metaData)) $result['meta'] = $this->metaData;
        return $result;
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function retrieveFile(array $data): void
    {
        $path = PluginConductor::defineSnippetPath($data['path']);
        if (!$path) {
            \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Snippet '{$data['path']}' is not found",
                'plugin' => $this->getPlugin()->name,
                'data' => $data,
            ]);
            return;
        }

        $this->file = new File($path);
    }

    private function runBuildProcess(): void
    {
        foreach ($this->lx as &$elemData) {
            // Injection of snippet to element
            if (isset($elemData['snippetInfo'])) {
                $snippetInfo = $elemData['snippetInfo'];
                unset($elemData['snippetInfo']);

                $snippet = $this->addInnerSnippet($snippetInfo);
                if ($snippet !== null) {
                    $elemData['ib'] = $snippet->getRenderIndex();
                }
            }
        }
        unset($elemData);
    }

    private function addInnerSnippet(array $snippetInfo): ?Snippet
    {
        $path = $snippetInfo['path'];
        $attributes = $snippetInfo['attributes'];

        if (is_string($path)) {
            $fullPath = $this->getPlugin()
                ->conductor
                ->getFullPath($path, $this->file->getParentDirPath());
            $fullPath = PluginConductor::defineSnippetPath($fullPath);
        } else {
            if (isset($path['plugin'])) {
                if (!isset($path['snippet'])) {
                    return null;
                }

                $plugin = \lx::$app->getPlugin($path['plugin']);
                if (!$plugin) {
                    return null;
                }

                $fullPath = $plugin->conductor->getSnippetPath($path['snippet']);
            }
        }

        if (!$fullPath) {
            return null;
        }

        $snippet = $this->snippetBuildContext->addSnippet([
            'path' => $fullPath,
            'attributes' => $attributes,
        ]);
        $this->innerSnippetKeys[] = $snippet->getRenderIndex();

        return $snippet;
    }
}
