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
    public function getFiles(array $rules = []): Vector;
    /**
     * @return Vector<string>
     */
    public function getFileNames(array $rules = []): Vector;
    /**
     * @return Vector<FileInterface>
     */
    public function getAllFiles(array $rules = []): Vector;
    /**
     * @return Vector<string>
     */
    public function getAllFileNames(array $rules = []): Vector;
    /**
     * @return Vector<DirectoryInterface>
     */
    public function getDirectories(array $rules = []): Vector;
    /**
     * @return Vector<string>
     */
    public function getDirectoryNames(array $rules = []): Vector;
    /**
     * @return Vector<FileInterface>
     */
    public function getFilesByCallback(string $fileMask, callable $func): Vector;
}
