<?php

namespace lx;

use ReflectionClass;

/**
 * Class ObjectReestr
 * @package lx
 */
class ObjectReestr
{
    /** @var array */
    private static $traits = [];

    /** @var array */
    private static $traitMap = [];

    /**
     * @return array
     */
    public static function getTraitMap($className)
    {
        if (empty(self::$traitMap) || !array_key_exists($className, self::$traitMap)) {
            self::$traitMap[$className] = ClassHelper::getTraitNames($className, true);
        }

        return self::$traitMap[$className];
    }

    /**
     * @param string $traitName
     * @return array
     */
    public static function getTraitInfo($traitName)
    {
        if ( ! array_key_exists($traitName, self::$traits)) {
            self::loadTraitInfo($traitName);
        }

        return self::$traits[$traitName];
    }

    /**
     * @param string $traitName
     */
    private static function loadTraitInfo($traitName)
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
