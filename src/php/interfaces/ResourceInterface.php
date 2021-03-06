<?php

namespace lx;

/**
 * Interface ResourceInterface
 * @package lx
 */
interface ResourceInterface extends ErrorCollectorInterface
{
	/**
	 * @param array $params
	 * @param UserInterface $user
	 * @return ResponseInterface
	 */
	public function run($params, $user = null);

	/**
	 * @param string $actionName
	 * @param array $params
	 * @param UserInterface $user
	 * @return ResponseInterface
	 */
	public function runAction($actionName, $params, $user = null);

    /**
     * @param mixed $data
     * @return ResponseInterface
     */
    public function prepareResponse($data);

    /**
     * @param array|string $error
     * @param int $code
     * @return ResponseInterface
     */
    public function prepareErrorResponse($error, $code = ResponseCodeEnum::BAD_REQUEST_ERROR);
}
