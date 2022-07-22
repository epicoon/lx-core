<?php

namespace lx;

/**
 * Requested resource is a plugin or action:
 * 1. Plugin - will be returned the plugin rendering result.
 * Object has to know service name and plugin name.
 *	$this->data == ['service' => 'serviceName', 'plugin' => 'pluginName']
 * 2. Action - will be returned the result of a method invoke.
 * Object has to know action class name and method name. It can also know service name.
 *	$this->data == ['service' => 'serviceName', 'class' => 'className', 'method' => 'methodName']
 */
class ResourceContext implements ResourceContextInterface
{
	private array $data;
	private Plugin $plugin;
	private ?ResourceInterface $resource;

	public function __construct(array $data = [])
	{
		$this->setData($data);
	}

	public function setData(array $data): void
	{
		$this->data = $data;
	}

    public function setParams(array $params): void
    {
        $this->data['params'] = $params;
    }

	public function invoke(): ResponseInterface
	{
		if ($this->isAction()) {
			return $this->invokeAction();
		}

		if ($this->isPlugin()) {
			return $this->invokePlugin();
		}

		return $this->getNotFoundResponse();
	}

	public function isAction(): bool
	{
		$data = $this->data;
		return (
			(isset($data['class']) || isset($data['object']))
			&&
			isset($data['method'])
		);
	}

	public function isPlugin(): bool
	{
		return isset($this->data['plugin']);
	}

	public function getService(): ?Service
	{
		if (isset($this->data['service'])) {
			return \lx::$app->getService($this->data['service']);
		}

		return null;
	}

	public function getPlugin(): ?Plugin
	{
		if (!$this->isPlugin()) {
			return null;
		}

		if (!isset($this->plugin)) {
			$plugin = $this->getService()->getPlugin($this->data['plugin']);

			if (isset($this->data['attributes'])) {
				$plugin->addAttributes($this->data['attributes']);
			}

			if (isset($this->data['dependencies'])) {
				$plugin->addDependencies($this->data['dependencies']);
			}

			$this->plugin = $plugin;
		}

		return $this->plugin;
	}
	
	public function getResource(): ?ResourceInterface
    {
        if ($this->isPlugin()) {
            return $this->getPlugin();
        }
        
        if (!isset($this->resource)) {
            $this->resource = null;
            $object = null;
            if (isset($this->data['object'])) {
                $object = $this->data['object'];
            } elseif (isset($this->data['class'])) {
                $class = $this->data['class'];
                $config = [];
                if (isset($this->data['service'])) {
                    $config['service'] = $this->getService();
                }
                $object = \lx::$app->diProcessor->create($class, [$config]);
            }

            if ($object && $object instanceof ResourceInterface) {
                $this->resource = $object;
            }
        }
        
        return $this->resource;
    }


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function invokeAction(): ResponseInterface
	{
		$object = $this->getResource();
		if (!$object) {
			return $this->getNotFoundResponse();
		}

		$methodName = $this->data['method'] ?? null;
		if (!$methodName || !method_exists($object, $methodName)) {
            return $this->getNotFoundResponse();
		}

		$params = $this->data['params'] ?? [];

		//TODO здесь нужен try catch с фиксацией 500-проблем 
		$result = $object->runAction($methodName, $params);
		$response = $this->prepareResponse($object, $result);
		return $response;
	}

	private function invokePlugin(): ResponseInterface
	{
		$plugin = $this->getPlugin();
		$methodName = Plugin::DEFAULT_RESOURCE_METHOD;
		if (!method_exists($plugin, $methodName)) {
            return $this->getNotFoundResponse();
		}

		$params = $this->data['params'] ?? [];

        //TODO здесь нужен try catch с фиксацией 500-проблем 
		$result = $plugin->runAction($methodName, $params);
        $response = $this->prepareResponse($plugin, $result);
        return $response;
	}

	private function prepareResponse(ResourceInterface $object, ?ResponseInterface $result): ResponseInterface
	{
        if ($result === null) {
            return \lx::$app->diProcessor->createByInterface(ResponseInterface::class, ['Ok']);
        }

        return $result;
    }
    
	private function getNotFoundResponse(): ResponseInterface
    {
        return \lx::$app->diProcessor->createByInterface(ResponseInterface::class, [
            'Resource not found',
            ResponseCodeEnum::NOT_FOUND
        ]);
    }
}
