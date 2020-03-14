<?php

namespace lx;

/**
 * Interface ConductorInterface
 * @package lx
 */
interface ConductorInterface
{
	/**
	 * @return string
	 * */
	public function getPath();

	/**
	 * @param string $fileName
	 * @param string $relativePath
	 * @return string
	 */
	public function getFullPath($fileName, $relativePath = null);

	/**
	 * @param string $path
	 * @param string $defaultLocation
	 * @return string
	 */
	public function getRelativePath($path, $defaultLocation = null);

	/**
	 * @param string $name
	 * @return BaseFile|null
	 */
	public function getFile($name);

	/**
	 * @return string
	 */
	public function getSystemPath($name = null);
}
