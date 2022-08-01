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

    public function run(array $params, UserInterface $user = null): HttpResponseInterface;
	public function runAction(string $actionName, array $params, ?UserInterface $user = null): ?HttpResponseInterface;

    /**
     * @param mixed $data
     */
    public function prepareResponse($data): HttpResponseInterface;
    /**
     * @param mixed $data
     */
    public function prepareWarningResponse($data = []): HttpResponseInterface;
    /**
     * @param array|string $error
     */
    public function prepareErrorResponse($error, int $code = HttpResponse::BAD_REQUEST): HttpResponseInterface;
}
