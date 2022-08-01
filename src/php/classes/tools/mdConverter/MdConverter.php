<?php

namespace lx;

require (__DIR__ . '/MdBlockTypeEnum.php');
require (__DIR__ . '/MdParser.php');
require (__DIR__ . '/MdBlocksBuilder.php');
require (__DIR__ . '/MdRenderer.php');

class MdConverter
{
    private ?FileInterface $file = null;
    private ?string $parentDirectory = null;
    private string $mdText;

    public function setMdText(string $md): MdConverter
    {
        $this->md = $md;
        return $this;
    }

    public function setFile(FileInterface $file): MdConverter
    {
        $this->file = $file;
        $this->parentDirectory = $file->getParentDirPath();
        $this->mdText = $file->get();
        return $this;
    }

    public function setParentDirectory(string $path): MdConverter
    {
        $this->parentDirectory = $path;
        return $this;
    }

    public function run(): string
    {
        $parser = new MdParser();
        $map = $parser->run($this->mdText);

        $renderer = new MdRenderer();
        $result = $renderer->run($map);
        return $result;
    }
}
