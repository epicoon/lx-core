<?php

namespace lx;

class MdBlocksBuilder
{
    private array $linesData = [];
    private array $result = [];

    private bool $typeDefined = false;
    private string $prevType = MdBlockTypeEnum::TYPE_NONE;
    private string $currentType = MdBlockTypeEnum::TYPE_NONE;

    public function setLinesData(array $linesData): MdBlocksBuilder
    {
        $this->linesData = $linesData;
        return $this;
    }

    public function getResult(): array
    {
        if (empty($this->linesData)) {
            return [];
        }

        $this->run();
        return $this->result;
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * CHECKERS
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function checkMap(): array
    {
        return [
            'checkLine',
            'checkTitle',
            'checkCodeblock',
            'checkBlockquote',
            'checkOrderedList',
            'checkUnorderedList',
            'checkParagraph',
        ];
    }

    private function checkLine(ParseData $parseData): void
    {
        if (preg_match('/^(---+|\*\*\*+)\s*$/', $parseData->line)) {
            $this->defineType(MdBlockTypeEnum::TYPE_LINE);
        }
    }

    private function checkTitle(ParseData $parseData): void
    {
        if (preg_match('/^(#{1,6})/', $parseData->line, $matches)) {
            switch (strlen($matches[1])) {
                case 1: $this->defineType(MdBlockTypeEnum::TYPE_TITLE_1); break;
                case 2: $this->defineType(MdBlockTypeEnum::TYPE_TITLE_2); break;
                case 3: $this->defineType(MdBlockTypeEnum::TYPE_TITLE_3); break;
                case 4: $this->defineType(MdBlockTypeEnum::TYPE_TITLE_4); break;
                case 5: $this->defineType(MdBlockTypeEnum::TYPE_TITLE_5); break;
                case 6: $this->defineType(MdBlockTypeEnum::TYPE_TITLE_6); break;
            }
        } elseif ($parseData->indent == 0
            && count($parseData->blockLines) == 1
            && preg_match('/^-+\s*$/', $parseData->line)
        ) {
            $this->defineType(MdBlockTypeEnum::TYPE_TITLE_1);
            $this->defineType(MdBlockTypeEnum::TYPE_TITLE_1);
        } elseif ($parseData->indent == 0
            && count($parseData->blockLines) == 1
            && preg_match('/^=+\s*$/', $parseData->line)
        ) {
            $this->defineType(MdBlockTypeEnum::TYPE_TITLE_2);
            $this->defineType(MdBlockTypeEnum::TYPE_TITLE_2);
        }
    }

    private function checkCodeblock(ParseData $parseData): void
    {
        if ($parseData->indent
            && $this->isCurrentType(
                MdBlockTypeEnum::TYPE_NONE,
                MdBlockTypeEnum::TYPE_CODEBLOCK,
                MdBlockTypeEnum::TYPE_TITLE_1,
                MdBlockTypeEnum::TYPE_TITLE_2,
                MdBlockTypeEnum::TYPE_TITLE_3,
                MdBlockTypeEnum::TYPE_TITLE_4,
                MdBlockTypeEnum::TYPE_TITLE_5,
                MdBlockTypeEnum::TYPE_TITLE_6,
            )
        ) {
            $this->defineType(MdBlockTypeEnum::TYPE_CODEBLOCK);
        }
    }

    private function checkBlockquote(ParseData $parseData): void
    {
        if ($parseData->line[0] == '>'
            || ($parseData->line != '' && $this->isCurrentType(MdBlockTypeEnum::TYPE_BLOCKQUOTE))
        ) {
            $this->defineType(MdBlockTypeEnum::TYPE_BLOCKQUOTE);
        }
    }

    private function checkOrderedList(ParseData $parseData): void
    {
        if (preg_match('/^\d+\. /', $parseData->line)
            || ($this->isCurrentType(MdBlockTypeEnum::TYPE_ORDERED_LIST) && (
                    $parseData->spaces == 1
                    || ($parseData->line != '' && $parseData->indent == 0 && $parseData->spacesBefore == 0)
                    || ($parseData->line != '' && $parseData->indent)
                ))
        ) {
            $this->defineType(MdBlockTypeEnum::TYPE_ORDERED_LIST);
        }
    }

    private function checkUnorderedList(ParseData $parseData): void
    {
        if (preg_match('/^(\*|\+|-) /', $parseData->line)
            || ($this->isCurrentType(MdBlockTypeEnum::TYPE_UNORDERED_LIST) && (
                    $parseData->spaces == 1
                    || ($parseData->line != '' && $parseData->indent == 0 && $parseData->spacesBefore == 0)
                    || ($parseData->line != '' && $parseData->indent)
                ))
        ) {
            $this->defineType(MdBlockTypeEnum::TYPE_UNORDERED_LIST);
        }
    }

    private function checkParagraph(ParseData $parseData): void
    {
        $this->defineType(MdBlockTypeEnum::TYPE_PARAGRAPH);
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function run(): void
    {
        $this->result = [];
        $this->prevType = MdBlockTypeEnum::TYPE_NONE;
        $this->currentType = MdBlockTypeEnum::TYPE_NONE;
        $parseData = new ParseData();
        foreach ($this->linesData as $lineData) {
            $this->typeDefined = false;
            $parseData->line = $lineData['line'];
            if ($parseData->line == '') {
                if ($this->isCurrentType(MdBlockTypeEnum::TYPE_NONE)) {
                    continue;
                }

                $parseData->incSpaces();
                if ($this->isCurrentType(MdBlockTypeEnum::TYPE_CODEBLOCK)
                    || ($parseData->spaces == 1 && $this->isCurrentType(
                        MdBlockTypeEnum::TYPE_UNORDERED_LIST,
                        MdBlockTypeEnum::TYPE_ORDERED_LIST
                    ))
                ) {
                    $this->defineType($this->currentType);
                } else {
                    $parseData->dropSpaces();
                    $this->defineType(MdBlockTypeEnum::TYPE_NONE);
                }
            } else {
                $parseData->dropSpaces();
            }
            $parseData->indent = $lineData['indent'];

            $checkers = $this->checkMap();
            foreach ($checkers as $checker) {
                if ($this->typeDefined) {
                    break;
                }

                $this->$checker($parseData);
            }

            if ($this->typeIsChanged()) {
                $this->result[] = [
                    'type' => $this->prevType,
                    'lines' => $parseData->blockLines,
                ];
                $parseData->blockLines = [];
            }
            if (!$this->isCurrentType(MdBlockTypeEnum::TYPE_NONE)) {
                $parseData->blockLines[] = $lineData;
            }
        }
        if (!empty($lines = $parseData->blockLines)) {
            $this->result[] = [
                'type' => $this->currentType,
                'lines' => $lines,
            ];
        }
    }

    private function isCurrentType(string ...$types): bool
    {
        return in_array($this->currentType, $types);
    }

    public function defineType(string $type): void
    {
        $this->prevType = $this->currentType;
        $this->currentType = $type;
        $this->typeDefined = true;
    }

    public function typeIsChanged(): bool
    {
        return $this->currentType != $this->prevType && $this->prevType != MdBlockTypeEnum::TYPE_NONE;
    }
}

/**
 * @property string $line
 * @property int $indent
 * @property int $spaces
 * @property int $spacesBefore
 * @property array $blockLines
 */
class ParseData extends DataObject {
    public function __construct()
    {
        parent::__construct([
            'line' => '',
            'indent' => 0,
            'spaces' => 0,
            'spacesBefore' => 0,
            'blockLines' => [],
        ]);
    }

    public function incSpaces(): void
    {
        $this->spacesBefore = $this->spaces;
        $this->spaces++;
    }

    public function dropSpaces(): void
    {
        $this->spacesBefore = $this->spaces;
        $this->spaces = 0;
    }
}
