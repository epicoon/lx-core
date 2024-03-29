<?php

namespace lx;

interface FileInterface extends CommonFileInterface
{
    public function getExtension(): string;
    public function getCleanName(): string;
    public function copy(FileInterface $file): bool;
    public function clone(string $path): ?FileInterface;
    public function open(string $flags = 'r'): bool;
    /**
     * @param mixed $info
     */
    public function write($info, string $flags='w'): bool;
    public function read(int $offset = 0, ?int $length = null): ?string;
    public function close(): bool;
    /**
     * @param mixed $info
     */
    public function put($info, int $flags = 0): bool;
    /**
     * @param mixed $info
     */
    public function append($info): bool;
    /**
     * @return mixed
     */
    public function get();
    public function getTail(int $rowsCount): string;
}
