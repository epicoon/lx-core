<?php

namespace lx;

interface FileInterface
{
    /**
     * @param string $path
     * @return $this
     */
    public function setPath($path);

    /**
     * @param string $path
     * @return BaseFile|null
     */
    public static function construct($path);

    /**
     * @return string
     */
    public function getPath();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getParentDirPath();

    /**
     * @return Directory
     */
    public function getParentDir();

    /**
     * @return bool
     */
    public function exists();

    /**
     * @param string $path
     * @return FileLink|null
     */
    public function createLink($path);

    /**
     * @param string|Directory $parent
     * @return bool
     */
    public function belongs($parent);

    /**
     * @param string|Directory $parent
     * @return string|false
     */
    public function getRelativePath($parent);

    /**
     * @return int
     */
    public function createdAt();

    /**
     * @param BaseFile $file
     * @return bool
     */
    public function isNewer($file);

    /**
     * @param BaseFile $file
     * @return bool
     */
    public function isOlder($file);

    /**
     * @return bool
     */
    public function isDir();

    /**
     * @return bool
     */
    public function isFile();

    /**
     * @return int
     */
    public function getType();

    /**
     * @param string $newName
     * @return bool
     */
    public function rename($newName);

    /**
     * @param Directory $dir
     * @param string $newName
     */
    public function moveTo($dir, $newName = null);
}
