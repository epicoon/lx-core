<?php

namespace lx;

interface ResourceInterface
{
    public function beforeAction(): void;
    public function beforeSuccessfulAction(): void;
    public function beforeFailedAction(): void;
    public function afterAction(): void;
    public function afterSuccessfulAction(): void;
    public function afterFailedAction(): void;

    public function run(array $params, UserInterface $user = null): ResponseInterface;
	public function runAction(string $actionName, array $params, ?UserInterface $user = null): ?ResponseInterface;

    /**
     * @param mixed $data
     */
    public function prepareResponse($data): ResponseInterface;
    /**
     * @param mixed $data
     */
    public function prepareWarningResponse($data = []): ResponseInterface;
    /**
     * @param array|string $error
     */
    public function prepareErrorResponse($error, int $code = ResponseCodeEnum::BAD_REQUEST_ERROR): ResponseInterface;
}
