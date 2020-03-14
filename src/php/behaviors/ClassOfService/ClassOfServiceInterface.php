<?php

namespace lx;

/**
 * Can be applied to classes declared in services
 * 
 * Interface ClassOfServiceInterface
 * @package lx
 */
interface ClassOfServiceInterface
{
	/**
	 * Get service name for current class
	 *
	 * @return string|null
	 */
	public function getServiceName();

	/**
	 * Get service for current class
	 *
	 * @return lx\Service|null
	 */
	public function getService();
}
