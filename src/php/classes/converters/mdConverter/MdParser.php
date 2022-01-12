<?php

namespace lx;

class MdParser
{
    public function run(string $mdText): array
    {
        $lines = preg_split('/(\r\n|\r|\n)/', $mdText);
        $map = $this->buildMap($lines);
        $map = $this->processMap($map);
        return $map;
    }

    private function buildMap(array $lines): array
    {
        $map = [];
        foreach ($lines as $line) {
            $lineData = ['line' => $line];
            $this->normalizeLineData($lineData);
            $map[] = $lineData;
        }
        return $map;
    }

    private function normalizeLineData(array &$lineData): void
    {
        $line = $lineData['line'];

        if (preg_match('/^( |\t)+$/', $line)) {
            $line = '';
        }

        preg_match('/^( *)/', $line, $matches);
        if ($matches[1] !== '') {
            $len = strlen($matches[1]);
            $indent = (int)floor($len / 4);
        } else {
            preg_match('/^(\t*)/', $line, $matches);
            $indent = strlen($matches[1]);
        }

        $lineData['originLine'] = $line;
        $lineData['line'] = preg_replace('/^( |\t)*/', str_repeat(' ', $indent * 4), $line);
        $lineData['indent'] = $indent;
    }

    private function processMap(array $map): array
    {
        $this->cutBlockLineSpaces($map);
        $blocks = $this->defineBlocks($map);

        foreach ($blocks as &$block) {
            $this->cutBlockLineSpaces($block['lines']);

            switch ($block['type']) {
                case MdBlockTypeEnum::TYPE_UNORDERED_LIST:
                case MdBlockTypeEnum::TYPE_ORDERED_LIST:
                    $this->processListBlock($block);
                    break;

                case MdBlockTypeEnum::TYPE_BLOCKQUOTE:
                    $this->processBlockquoteBlock($block);
                    break;

            }
        }
        unset($block);

        return $blocks;
    }

    private function defineBlocks(array $lineDatas): array
    {
        $blocksBuilder = new MdBlocksBuilder();
        return $blocksBuilder
            ->setLinesData($lineDatas)
            ->getResult();
    }

    private function cutBlockLineSpaces(array &$lines): void
    {
        while (end($lines)['line'] == '') {
            array_pop($lines);
        }
    }

    private function processListBlock(array &$block): void
    {
        $lines = [];
        for ($i=0, $l=count($block['lines']); $i<$l; $i++) {
            $lineData = &$block['lines'][$i];
            $nextLineData = $block['lines'][$i + 1] ?? null;
            if (!$nextLineData) {
                $lines[] = $lineData;
                continue;
            }

            if ($nextLineData['line'] != '') {
                $regexp = $block['type'] == MdBlockTypeEnum::TYPE_ORDERED_LIST
                    ? '/^\d+\. /'
                    : '/^(-|\+|\*) /';
                if (preg_match($regexp, $nextLineData['line'])) {
                    $lines[] = $lineData;
                    continue;
                }

                $regexp = $block['type'] == MdBlockTypeEnum::TYPE_ORDERED_LIST
                    ? '/^    \d+\. /'
                    : '/^    (-|\+|\*) /';
                if (preg_match($regexp, $nextLineData['line'])) {
                    $farLineData = $nextLineData;
                } else {
                    $lineData['line'] = preg_replace('/  $/','<br>', $lineData['line']);
                    $lineData['line'] .= ' ' . $nextLineData['line'];
                    unset($block['lines'][$i + 1]);
                    $block['lines'] = array_values($block['lines']);
                    $i--;
                    $l--;
                    continue;
                }
            }

            if (isset($farLineData)) {
                $realFar = false;
                $j = $i + 1;
            } else {
                $farLineData = $block['lines'][$i + 2];
                $j = $i + 2;
                $realFar = true;
            }
            if ($farLineData['indent'] == 0) {
                $lines[] = $lineData;
                $i++;
                continue;
            }

            $map = [];
            $tempLineData = $farLineData;
            unset($farLineData);
            $deletedCounter = 0;
            $done = false;
            while (!$done) {
                if ($tempLineData['line'] != '') {
                    $tempLineData['indent']--;
                    $tempLineData['line'] = preg_replace('/^    /', '', $tempLineData['line']);
                }
                $map[] = $tempLineData;
                unset($block['lines'][$j]);
                $deletedCounter++;
                $j++;
                if ($j == $l) {
                    $done = true;
                    break;
                }
                $tempLineData = $block['lines'][$j];
                if ($tempLineData['indent'] == 0 && $tempLineData['line'] != '') {
                    $done = true;
                }
            }
            if ($realFar) {
                unset($block['lines'][$i + 1]);
                $deletedCounter++;
            }
            $l -= $deletedCounter;
            $i--;
            $block['lines'] = array_values($block['lines']);

            $lineData['content'] = $this->processMap($map);
        }
    }

    private function processBlockquoteBlock(array &$block): void
    {
        $map = [];
        foreach ($block['lines'] as $lineData) {
            $lineData['line'] = preg_replace('/^> ?/', '', $lineData['line']);
            $this->normalizeLineData($lineData);
            $map[] = $lineData;
        }

        $block['content'] = $this->processMap($map);
        unset($block['lines']);
    }
}
