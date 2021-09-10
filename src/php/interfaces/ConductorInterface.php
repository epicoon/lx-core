<?php

namespace lx;

interface ConductorInterface
{
	public function getPath(): string;
	public function getFullPath(string $fileName, ?string $relativePath = null): ?string;
	public function getRelativePath(string $path, ?string $defaultLocation = null): string;
}
