<?php

namespace lx;

use lx;

class WebAssetHelper
{
    public static function getLinksMap(array $map): array
    {
        /** @var RouterInterface|null $router */
        $router = lx::$app->router;
        $prefix = $router ? $router->getAssetPrefix() : '';

        $result = [
            'origins' => [],
            'links' => [],
            'names' => [],
        ];

        foreach ($map as $key => $value) {
            if (preg_match('/^(http:|https:)/', $value)) {
                $result['names'][$key] = $value;
                continue;
            }

            if (preg_match('/^\/web\//', $value)) {
                $result['names'][$key] = $prefix . $value;
                continue;
            }

            preg_match('/\.[^.\/]+$/', $value, $ext);
            $ext = $ext[0] ?? '';
            if ($ext == '.css') {
                $parentDir = dirname($value);
                $file = basename($value);
                $path = '/web/auto/' . md5($parentDir);
                $result['origins'][$key] = $parentDir;
                $result['links'][$key] = $path;
                $result['names'][$key] = $prefix . $path . '/' . $file;
            } else {
                $path = '/web/auto/' . md5($value);
                $result['origins'][$key] = $value;
                $result['links'][$key] = $path . $ext;
                $result['names'][$key] = $prefix . $path . $ext;
            }
        }

        return $result;
    }

    public static function createLinks(array $originalPathes, array $linkPathes): void
    {
        $sitePath = lx::$conductor->sitePath;
        foreach ($linkPathes as $key => $linkPath) {
            if ($linkPath[0] != '/') {
                $linkPath = '/' . $linkPath;
            }
            $originPath = $originalPathes[$key];
            if ($originPath[0] != '/') {
                $originPath = '/' . $originPath;
            }
            $fullLinkPath = $sitePath . $linkPath;
            $fullOriginPath = $sitePath . $originPath;
            if ($fullOriginPath == $fullLinkPath || !file_exists($fullOriginPath)) {
                continue;
            }

            $linkFile = new FileLink($fullLinkPath);
            if (!$linkFile->exists()) {
                $dir = $linkFile->getParentDir();
                $dir->make();
                $linkFile->create(BaseFile::construct($fullOriginPath));
            }
        }
    }
}
