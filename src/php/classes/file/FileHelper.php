<?php

namespace lx;

class FileHelper
{
    public static function matchFile(FileInterface $file, string $pattern): bool
    {
        if (!$file->exists()) {
            return false;
        }

        if ($pattern[0] != '/') {
            $pattern = '/' . $pattern . '/';
        }

        $text = file_get_contents($file->getPath());
        return (bool)preg_match($pattern, $text);
    }

    public static function replaceInFile(FileInterface $file, string $pattern, string $replacement): bool
    {
        if (!$file->exists()) {
            return false;
        }

        $text = file_get_contents($file->getPath());
        if ($pattern[0] != '/') {
            $pattern = '/' . $pattern . '/';
        }
        $text = preg_replace($pattern, $replacement, $text);
        file_put_contents($file->getPath(), $text);
        return true;
    }
}
