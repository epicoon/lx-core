<?php

namespace lx;

use ReflectionClass;

class ObjectReestr
{
    private static array $traits = [];
    private static array $traitMap = [];

    public static function getTraitMap(string $className): array
    {
        if (empty(self::$traitMap) || !array_key_exists($className, self::$traitMap)) {
            self::$traitMap[$className] = ClassHelper::getTraitNames($className, true);
        }

        return self::$traitMap[$className];
    }

    public static function getTraitInfo(string $traitName): array
    {
        if (!array_key_exists($traitName, self::$traits)) {
            self::loadTraitInfo($traitName);
        }

        return self::$traits[$traitName];
    }

    private static function loadTraitInfo(string $traitName): void
    {
        try {
            $trait = new ReflectionClass($traitName);
        } catch (\ReflectionException $e) {
            return;
        }

        if (isset($trait)) {
            self::$traits[$traitName] = [];
            $methods = $trait->getMethods();
            foreach ($methods as $method) {
                $doc = $method->getDocComment();
                if ($doc) {
                    preg_match_all('/@magic +([^\s]+?)\s/', $doc, $match);
                    if (empty($match[0])) {
                        continue;
                    }

                    $type = $match[1][0];
                    self::$traits[$traitName][$type] = $method->getName();
                }
            }
        }
    }
}
