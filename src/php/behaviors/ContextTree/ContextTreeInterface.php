<?php

namespace lx;

/**
 * @see ContextTreeTrait
 */
interface ContextTreeInterface
{
	public function constructContextTree(iterable $config = []);
	public function getHead(): ContextTreeInterface;
	public function getKey(): string;
	public function setKey(string $key): void;
	public function getParent(): ?ContextTreeInterface;
	public function setParent(ContextTreeInterface $parent): void;
	public function getNested(): array;
	public function isHead(): bool;
	public function add(iterable $config = []): ContextTreeInterface;
    public function nest(ContextTreeInterface $context): bool;
    public function unnest(ContextTreeInterface $context): bool;
	public function eachContext(callable $func): void;
}
