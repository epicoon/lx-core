<?php

namespace lx;

interface CommonFileInterface
{
    public function setPath(string $path): void;
    public static function construct(string $path): ?CommonFileInterface;
    public function getPath(): string;
    public function getName(): string;
    public function getParentDirPath(): string;
    public function getParentDir(): DirectoryInterface;
    public function exists(): bool;
    public function createLink(string $path): ?FileLinkInterface;
    public function remove(): bool;
    /**
     * @param string|DirectoryInterface $parent
     */
    public function belongs($parent): bool;
    /**
     * @param string|DirectoryInterface $parent
     */
    public function getRelativePath($parent): ?string;
    public function updatedAt(): int;
    public function isNewer(CommonFileInterface $file): bool;
    public function isOlder(CommonFileInterface $file): bool;
    public function isDirectory(): bool;
    public function isFile(): bool;
    public function isLink(): bool;
    public function getType(): int;
    public function rename(string $newName): bool;
    public function moveTo(DirectoryInterface $dir, ?string $newName = null);
}
