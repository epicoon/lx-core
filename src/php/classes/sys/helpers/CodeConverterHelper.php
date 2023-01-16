<?php

namespace lx;

class CodeConverterHelper
{
    /**
     * Convert array to JS-like code string
     */
    public static function arrayToJsCode(array $array): string
    {
        $rec = function ($val) use (&$rec) {
            if (is_array($val)) {
                $arr = [];
                $keys = [];
                $assoc = false;
                foreach ($val as $key => $item) {
                    $keys[] = $key;
                    $arr[] = $rec($item);
                    if (is_string($key)) $assoc = true;
                }
                if (!$assoc) return '[' . implode(',', $arr) . ']';

                $temp = [];
                foreach ($keys as $i => $key) {
                    $temp[] = "'$key':{$arr[$i]}";
                }
                return '{' . implode(',', $temp) . '}';
            }

            if (is_string($val)) {
                if ($val == '') return '\'\'';
                if (preg_match('/\n/', $val)) {
                    if (preg_match('/`/', $val)) {
                        $val = preg_replace('/(?:\n|\r|\r\n)/', '$1\'+\'', $val);
                    } else {
                        $val = "`$val`";
                        return $val;
                    }
                }
                if (preg_match('/^#lx:/', $val)) {
                    return $val;
                }
                if ($val[0] == '\'') {
                    return $val;
                } else {
                    $val = addcslashes($val, "'");
                    return "'$val'";
                }
            }
            if ($val === true) return 'true';
            if ($val === false) return 'false';
            if ($val === null) return 'null';
            return $val;
        };

        $result = $rec($array);
        return $result;
    }

    public static function arrayToPhpCode(array $value, int $indent = 0): string
    {
        if (empty($value)) {
            return '[]';
        }

        if (!is_array($value)) {
            return "'" .  addslashes($value) . "'";
        }

        $out = '';
        $tab = '    ';
        $margin = str_repeat($tab, $indent++);

        $out .= '[' . PHP_EOL;
        foreach ($value as $key => $row) {
            $out .= $margin . $tab;
            if (is_numeric($key)) {
                $out .= $key . ' => ';
            } else {
                $out .= "'" . $key . "' => ";
            }

            if (is_array($row)) {
                $out .= self::arrayToPhpCode($row, $indent);
            } elseif (is_null($row)) {
                $out .= 'null';
            } elseif (is_numeric($row)) {
                $out .= $row;
            } elseif ($row === true) {
                $out .= 'true';
            } elseif ($row === false) {
                $out .= 'false';
            } else {
                $out .= "'" . addslashes($row) . "'";
            }

            $out .= ',' . PHP_EOL;
        }

        $out .= $margin . ']';

        return $out;
    }
}
