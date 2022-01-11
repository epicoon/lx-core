<?php

namespace lx;

class MdRenderer
{
    private string $result = '';

    public function run(array $map): string
    {
        $this->result = '';
        $this->renderMap($map);
        return $this->result;
    }

    private function renderMap(array $map): void
    {
        foreach ($map as $block) {
            $this->renderBlock($block);
        }
    }

    private function renderBlock(array $block): void
    {
        switch ($block['type']) {
            case MdBlockTypeEnum::TYPE_LINE:
                $this->result .= '<hr>';
                break;

            case MdBlockTypeEnum::TYPE_TITLE_1: $this->renderTitle('h1', $block['lines'][0]['line']); break;
            case MdBlockTypeEnum::TYPE_TITLE_2: $this->renderTitle('h2', $block['lines'][0]['line']); break;
            case MdBlockTypeEnum::TYPE_TITLE_3: $this->renderTitle('h3', $block['lines'][0]['line']); break;
            case MdBlockTypeEnum::TYPE_TITLE_4: $this->renderTitle('h4', $block['lines'][0]['line']); break;
            case MdBlockTypeEnum::TYPE_TITLE_5: $this->renderTitle('h5', $block['lines'][0]['line']); break;
            case MdBlockTypeEnum::TYPE_TITLE_6: $this->renderTitle('h6', $block['lines'][0]['line']); break;

            case MdBlockTypeEnum::TYPE_PARAGRAPH:
                $this->renderParagraph($block);
                break;
            case MdBlockTypeEnum::TYPE_CODEBLOCK:
                $this->renderCodeBlock($block);
                break;
            case MdBlockTypeEnum::TYPE_BLOCKQUOTE:
                $this->renderBlockquote($block);
                break;
            case MdBlockTypeEnum::TYPE_ORDERED_LIST:
            case MdBlockTypeEnum::TYPE_UNORDERED_LIST:
                $this->renderList($block);
                break;
        }
    }

    private function renderTitle(string $tag, string $line): void
    {
        $line = preg_replace('/^#{1,6}/', '', $line);
        if (preg_match('/\{#([\w\d_-]+?)\}$/', $line, $matches)) {
            $line = preg_replace('/\s*\{#[\w\d_-]+?\}$/', '', $line);
            $this->openTag($tag, [
                'id' => $matches[1],
            ]);
        } else {
            $this->openTag($tag);
        }
        $this->result .= $line . "</{$tag}>";
    }

    private function renderParagraph(array $block): void
    {
        $this->openTag('p');
        $paragraph = '';
        foreach ($block['lines'] as $lineData) {
            $paragraph .= ' ' . $lineData['line'];
        }
        $this->result .= $paragraph . '</p>';
    }

    private function renderCodeBlock(array $block): void
    {
        $this->openTag('pre');
        $code = [];
        foreach ($block['lines'] as $lineData) {
            $line = $lineData['line'];
            $line = preg_replace('/^    /', '', $line);
            $code[] = $line;
        }
        $code = implode('<br>', $code);
        $this->result .= $code . '</pre>';
    }

    private function renderBlockquote(array $block): void
    {
        $this->openTag('blockquote');
        $subRenderer = new MdRenderer();
        $this->result .= $subRenderer->run($block['content']) . '</blockquote>';
    }

    private function renderList(array $block): void
    {
        $tag = ($block['type'] == MdBlockTypeEnum::TYPE_ORDERED_LIST) ? 'ol' : 'ul';
        $this->openTag($tag);

        $regexp = ($block['type'] == MdBlockTypeEnum::TYPE_ORDERED_LIST)
            ? '/^\d+\. /'
            : '/^(-|\+|\*) /';
        foreach ($block['lines'] as $lineData) {
            $line = $lineData['line'];
            $line = preg_replace($regexp, '', $line);
            $this->openTag('li');
            $this->result .= $line;
            if (array_key_exists('content', $lineData)) {
                $subRenderer = new MdRenderer();
                $this->result .= $subRenderer->run($lineData['content']);
            }
            $this->result .= '</li>';
        }
        $this->result .= "</{$tag}>";
    }

    private function openTag(string $tag, array $args = []): void
    {
        $this->result .= "<{$tag}";
        $class = $this->getCssClass($tag);
        if ($class) {
            $this->result .= " class=\"{$class}\"";
        }
        if (!empty($args)) {
            foreach ($args as $key => $value) {
                $this->result .= " {$key}=\"{$value}\"";
            }
        }
        $this->result .= '>';
    }

    private function getCssClass(string $tag): ?string
    {
        return null;
    }
}
