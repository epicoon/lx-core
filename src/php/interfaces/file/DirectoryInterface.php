<?php

namespace lx;

interface DirectoryInterface extends CommonFileInterface
{
    public function scan(): Vector;
    public function contains(string $name): bool;
    public function make(int $mode = 0777): void;
    public function makeDirectory(string $name, int $mode = 0777, bool $recursive = false): DirectoryInterface;
    public function copy(DirectoryInterface $dir, bool $rewrite = false): bool;
    public function clone(string $path, bool $rewrite = false): ?DirectoryInterface;
    public function getOrMakeDirectory(string $name, int $mode = 0777, bool $recursive = false): DirectoryInterface;
    public function makeFile(string $name, ?string $classOrInterface = null): FileInterface;
    /**
     * @return Vector<CommonFileInterface>|Vector<string>
     */
    public function getContent(array $rules = []): Vector;
    public function get(string $name): ?CommonFileInterface;
    public function find(string $filename): ?CommonFileInterface;
    /**
     * @return Vector<FileInterface>
     */
    public function getFiles(?string $pattern = null): Vector;
    /**
     * @return Vector<string>
     */
    public function getFileNames(?string $pattern = null): Vector;
    /**
     * @return Vector<FileInterface>
     */
    public function getAllFiles(?string $pattern = null): Vector;
    /**
     * @return Vector<string>
     */
    public function getAllFileNames(?string $pattern = null): Vector;
    /**
     * @return Vector<DirectoryInterface>
     */
    public function getDirectories(): Vector;
    /**
     * @return Vector<string>
     */
    public function getDirectoryNames(): Vector;
    /**
     * @return Vector<FileInterface>
     */
    public function getFilesByCallback(string $fileMask, callable $func): Vector;
}
