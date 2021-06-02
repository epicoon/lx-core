<?php

namespace lx;

interface CommonFileInterface
{
    public function setPath(string $path): void;
    public static function construct(string $path): ?BaseFile;
    public function getPath(): string;
    public function getName(): string;
    public function getParentDirPath(): string;
    public function getParentDir(): Directory;
    public function exists(): bool;
    public function createLink(string $path): ?FileLink;
    public function remove(): bool;
    /**
     * @param string|Directory $parent
     */
    public function belongs($parent): bool;
    /**
     * @param string|Directory $parent
     */
    public function getRelativePath($parent): ?string;
    public function createdAt(): int;
    public function isNewer(BaseFile $file): bool;
    public function isOlder(BaseFile $file): bool;
    public function isDirectory(): bool;
    public function isFile(): bool;
    public function getType(): int;
    public function rename(string $newName): bool;
    public function moveTo(Directory $dir, ?string $newName = null);
}
