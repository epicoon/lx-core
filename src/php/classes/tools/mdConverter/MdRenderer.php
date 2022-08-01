<?php

namespace lx;

class MdRenderer
{
    private string $result = '';
    private bool $useWrapper = true;

    public function useWrapper(bool $value = true): MdRenderer
    {
        $this->useWrapper = $value;
        return $this;
    }

    public function run(array $map): string
    {
        $this->result = '';
        $this->renderMap($map);
        $this->renderInLines();
        if (!$this->useWrapper) {
            return $this->result;
        }

        $result = $this->openTag('div');
        $result .= $this->result . '</div>';
        return $result;
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

            case MdBlockTypeEnum::TYPE_TABLE:
                $this->renderTable($block);
                break;
            case MdBlockTypeEnum::TYPE_PARAGRAPH:
                $this->renderParagraph($block);
                break;
            case MdBlockTypeEnum::TYPE_CODEBLOCK:
                $this->renderCodeBlock($block);
                break;
            case MdBlockTypeEnum::TYPE_CODEBLOCK_TYPED:
                $this->renderCodeBlockTyped($block);
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
            $this->result .= $this->openTag($tag, [
                'id' => $matches[1],
            ]);
        } else {
            $this->result .= $this->openTag($tag);
        }
        $this->result .= $line . "</{$tag}>";
    }

    private function renderTable($block): void
    {
        $this->result .= $this->openTag('table');

        $firstRow = 0;
        $aligns = [];
        $subHeaderLine = $block['lines'][1]['line'] ?? null;
        if (!preg_match('/^\|(\s*:?-+:?\s*\|)+\s*$/', $subHeaderLine)) {
            $subHeaderLine = null;
        }
        if ($subHeaderLine) {
            $firstRow = 2;
            $header = trim($block['lines'][0]['line'], '| ');
            $titles = preg_split('/\s*\|\s*/', $header);
            $this->result .= $this->openTag('thead');
            $this->result .= $this->openTag('tr');
            foreach ($titles as $title) {
                $this->result .= $this->openTag('th', [
                    'style' => 'text-align: center',
                ]);
                $this->result .= $title . '</th>';
            }
            $this->result .= '</tr></thead>';

            $aligns = preg_split('/\s*\|\s*/', trim($subHeaderLine, '| '));
            foreach ($aligns as &$align) {
                $pre = $align[0] == ':';
                $post = $align[-1] == ':';
                switch (true) {
                    case ($pre && $post): $align = 'center'; break;
                    case $pre: $align = 'left'; break;
                    case $post: $align = 'right'; break;
                    default: $align = 'none';
                }
            }
            unset($align);
        }

        $this->result .= $this->openTag('tbody');
        for ($i=$firstRow, $l=count($block['lines']); $i<$l; $i++) {
            $this->result .= $this->openTag('tr');
            $line = $block['lines'][$i]['line'];
            $values = preg_split('/\s*\|\s*/', trim($line, '| '));
            foreach ($values as $col => $value) {
                $align = $aligns[$col] ?? 'none';
                $params = ($align == 'none') ? [] : ['style' => "text-align: {$align}"];
                $this->result .= $this->openTag('td', $params);
                $this->result .= $value . '</td>';
            }
            $this->result .= '</tr>';
        }
        $this->result .= '</tbody>';

        $this->result .= '</table>';
    }

    private function renderParagraph(array $block): void
    {
        $this->result .= $this->openTag('p');
        $paragraph = '';
        foreach ($block['lines'] as $lineData) {
            $lineData['line'] = preg_replace('/  $/', '<br>', $lineData['line']);
            $paragraph .= ' ' . $lineData['line'];
        }
        $this->result .= $paragraph . '</p>';
    }

    private function renderCodeBlock(array $block): void
    {
        $this->result .= $this->openTag('pre');
        $code = [];
        foreach ($block['lines'] as $lineData) {
            $line = $lineData['line'];
            $line = preg_replace('/^    /', '', $line);
            $code[] = $line;
        }
        $code = implode('<br>', $code);
        $this->result .= $code . '</pre>';
    }

    private function renderCodeBlockTyped(array $block): void
    {
        $this->result .= '<div>';
        $codeType = $block['codeType'] ?? null;
        $params = $codeType ? ['code-type' => $codeType] : [];
        $this->result .= $this->openTag('pre', $params);
        $code = [];
        foreach ($block['lines'] as $lineData) {
            if (preg_match('/^(```|~~~)/', $lineData['line'])) {
                continue;
            }
            $code[] = $lineData['originLine'];
        }
        $code = implode('<br>', $code);
        $this->result .= $code . '</pre>';
        $this->result .= '<img src="" onerror="if(lx.MdHighlighter)lx.MdHighlighter.highlight(this.parentNode.children[0]);this.parentNode.removeChild(this)">';
    }

    private function renderBlockquote(array $block): void
    {
        $this->result .= $this->openTag('blockquote');
        $subRenderer = new MdRenderer();
        $this->result .= $subRenderer->useWrapper(false)->run($block['content']) . '</blockquote>';
    }

    private function renderList(array $block): void
    {
        $tag = ($block['type'] == MdBlockTypeEnum::TYPE_ORDERED_LIST) ? 'ol' : 'ul';
        $this->result .= $this->openTag($tag);

        $regexp = ($block['type'] == MdBlockTypeEnum::TYPE_ORDERED_LIST)
            ? '/^\d+\. /'
            : '/^(-|\+|\*) /';
        foreach ($block['lines'] as $lineData) {
            $line = $lineData['line'];
            $line = preg_replace($regexp, '', $line);
            $this->result .= $this->openTag('li');
            $this->result .= $line;
            if (array_key_exists('content', $lineData)) {
                $subRenderer = new MdRenderer();
                $this->result .= $subRenderer->useWrapper(false)->run($lineData['content']);
            }
            $this->result .= '</li>';
        }
        $this->result .= "</{$tag}>";
    }

    private function renderInLines(): void
    {
        $imgPreg = '/!\[([^\]]+?)\]\(([^"]+?)\s*(?:"([^"]+?)")?\)/';
        $this->result = preg_replace_callback($imgPreg, function ($matches) {
            $link = $matches[2];
            $alt = $matches[1];
            $title = $matches[3] ?? null;
            return $title
                ? "<img src=\"{$link}\" title=\"{$title}\" alt=\"{$alt}\">"
                : "<img src=\"{$link}\" alt=\"{$alt}\">";
        }, $this->result);

        $linkPreg = '/\[([^\]]+?)\]\(([^"]+?)\s*(?:"([^"]+?)")?\)/';
        $this->result = preg_replace_callback($linkPreg, function ($matches) {
            $link = $matches[2];
            $content = $matches[1];
            $title = $matches[3] ?? null;
            return $title
                ? "<a href=\"{$link}\" title=\"{$title}\">{$content}</a>"
                : "<a href=\"{$link}\">{$content}</a>";
        }, $this->result);

        $boldPreg = '/(?:(?:\*\*(.+?)\*\*)|(?:\b__(.+?)__\b))/';
        $this->result = preg_replace_callback($boldPreg, function ($matches) {
            $text = $matches[1] ?: $matches[2];
            return "<strong>{$text}</strong>";
        }, $this->result);

        $italicPreg = '/(?:(?:\*(.+?)\*)|(?:\b_(.+?)_\b))/';
        $this->result = preg_replace_callback($italicPreg, function ($matches) {
            $text = $matches[1] ?: $matches[2];
            return "<em>{$text}</em>";
        }, $this->result);

        $delPreg = '/~~(.+?)~~/';
        $this->result = preg_replace_callback($delPreg, function ($matches) {
            return "<del>{$matches[1]}</del>";
        }, $this->result);
        
        $markPreg = '/==(.+?)==/';
        $this->result = preg_replace_callback($markPreg, function ($matches) {
            return "<mark>{$matches[1]}</mark>";
        }, $this->result);

        $subPreg = '/~(.+?)~/';
        $this->result = preg_replace_callback($subPreg, function ($matches) {
            return "<sub>{$matches[1]}</sub>";
        }, $this->result);

        $supPreg = '/\^(.+?)\^/';
        $this->result = preg_replace_callback($supPreg, function ($matches) {
            return "<sup>{$matches[1]}</sup>";
        }, $this->result);

        $codePreg = '/`(.+?)`/';
        $this->result = preg_replace_callback($codePreg, function ($matches) {
            return "<code>{$matches[1]}</code>";
        }, $this->result);
    }

    private function openTag(string $tag, array $args = []): string
    {
        $text = "<{$tag}";
        $class = $this->getCssClass($tag);
        if ($class) {
            $text .= " class=\"{$class}\"";
        }
        if (!empty($args)) {
            foreach ($args as $key => $value) {
                $text .= " {$key}=\"{$value}\"";
            }
        }
        $text .= '>';
        return $text;
    }

    private function getCssClass(string $tag): ?string
    {
        /* div
         * h1, h2, h3, h4, h5, h6
         * table, thead, tbody, tr, th, td
         * p, pre, blockquote
         * ol, ul, li
         */
        switch ($tag) {
            case 'div': return 'md-container';
            
            case 'p': return 'md-paragraph';
            case 'pre': return 'md-codeblock';
            case 'blockquote': return 'md-blockquote';

            case 'table': return 'md-table';
            case 'th': return 'md-table-header';
            case 'td': return 'md-table-cell';
        }
        return null;
    }
}
