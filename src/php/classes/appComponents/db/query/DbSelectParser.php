<?php

namespace lx;

class DbSelectParser
{
    public function parse(string $query): array
    {
        $reg = '/(?:^|\s+)('
            . 'select|from'
            . '|(?:(?:cross |inner |left outer |left |right outer |right |full outer |full )?join)'
            . '|where|group by|order by|limit|offset'
            . ')(?:\s+|$)/i';
        $array = preg_split($reg, $query, null, PREG_SPLIT_DELIM_CAPTURE);
        array_shift($array);

        $map = [];
        for ($i = 0, $l = count($array); $i < $l; $i += 2) {
            $key = strtolower($array[$i]);
            $value = $array[$i + 1];
            switch (true) {
                case ($key == 'select'):
                    $value = $this->parseSelect($value);
                    $map[$key] = $value;
                    break;
                case ($key == 'from'):
                    $value = $this->parseFrom($value);
                    $map[$key] = $value;
                    break;
                case (preg_match('/\bjoin\b/', $key)):
                    $value = $this->parseJoin($key, $value);
                    $map['join'][] = $value;
                    break;

                //TODO where, group by, order by, limit, offset

                default:
                    $map[$key] = $value;
            }
        }

        return $map;
    }

    private function parseSelect(string $value): array
    {
        $result = [];
        $arr = preg_split('/\s*,\s*/', $value);
        foreach ($arr as $item) {
            $names = preg_split('/(\s+as\s+|\s+)/', $item);
            preg_match('/^(.+?)\.([^.]+?)$/', $names[0], $name);
            if (empty($name)) {
                $result[] = [null, $names[0], $names[1] ?? null];
            } else {
                $result[] = [$name[1], $name[2], $names[1] ?? null];
            }
        }
        return $result;
    }

    private function parseFrom(string $value): array
    {
        $result = [];
        $arr = preg_split('/\s*,\s*/', $value);
        foreach ($arr as $item) {
            $names = preg_split('/(\s+as\s+|\s+)/', $item);
            $result[] = [$names[0], $names[1] ?? null];
        }
        return $result;
    }

    private function parseJoin(string $key, string $value): array
    {
        $arr = preg_split('/\s+on\s+/', $value);
        $name = preg_split('/(\s+as\s+|\s+)/', $arr[0]);

        $key = preg_replace('/\s+join/', '', $key);
        switch ($key) {
            case '': $key = 'inner'; break;
            case 'left': $key = 'outer left'; break;
            case 'right': $key = 'outer right'; break;
            case 'full': $key = 'outer full'; break;
        }

        //TODO $arr[1] - condition, need to parse
        return [
            [$name[0], $name[1] ?? null],
            $key,
            $arr[1]
        ];
    }

}
