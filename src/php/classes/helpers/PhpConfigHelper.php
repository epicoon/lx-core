<?php

namespace lx;

class PhpConfigHelper
{
    public static function get(FileInterface $file, array $keys): array
    {
        $data = $file->get();
        $result = [];
        foreach ($keys as $key) {
            $reg = '/\'' . $key . '\'\s*=>\s*\[([\w\W]*?)\]/';
            preg_match_all($reg, $data, $matches);
            if ($matches === null) {
                $result[$key] = null;
                continue;
            }
            
            $text = $matches[1][0];
            $text = preg_replace('/^\s*/', '', $text);
            $text = preg_replace('/,?\s*$/', '', $text);
            $arr = preg_split('/\s*,\s*/', $text);
            foreach ($arr as &$item) {
                $item = trim($item, '\'');
            }
            unset($item);
            $result[$key] = $arr;
        }
        return $result;
    }
}
