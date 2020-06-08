<?php

namespace lx;

/**
 * Requested source is plugin or action:
 * 1. Plugin - will be returned plugin rendering result.
 * Object need to know service name and plugin name.
 *	$this->data == ['service' => 'serviceName', 'plugin' => 'pluginName']
 * 2. Action - will be returned result of a method invoke.
 * Object need to know action class name and method name. It can also know service name.
 *	$this->data == ['service' => 'serviceName', 'class' => 'className', 'method' => 'methodName']
 *
 * Class SourceContext
 * @package lx
 */
class SourceContext
{
	/** @var array */
	private $data;

	/** @var Plugin */
	private $plugin;

	/**
	 * SourceContext constructor.
	 * @param array $data
	 */
	public function __construct($data = [])
	{
		$this->setData($data);
	}

	/**
	 * @param array $data
	 */
	public function setData($data)
	{
		$this->data = $data;
	}

	/**
	 * @param string $methodName
	 * @param array $params
	 * @return ResponseInterface
	 */
	public function invoke($methodName = null, $params = null)
	{
		if ($this->isAction()) {
			return $this->invokeAction($methodName, $params);
		}

		if ($this->isPlugin()) {
			return $this->invokePlugin($methodName, $params);
		}

		return $this->getNotFoundResponse();
	}

	/**
	 * @return bool
	 */
	public function isAction()
	{
		$data = $this->data;
		return (
			(isset($data['class']) || isset($data['object']))
			&&
			isset($data['method'])
		);
	}

	/**
	 * @return bool
	 */
	public function isPlugin()
	{
		return isset($this->data['plugin']);
	}

	/**
	 * @return Service|null
	 */
	public function getService()
	{
		if (isset($this->data['service'])) {
			return \lx::$app->getService($this->data['service']);
		}

		return null;
	}

	/**
	 * @return Plugin|null
	 */
	public function getPlugin()
	{
		if ( ! $this->isPlugin()) {
			return null;
		}

		if ( ! $this->plugin) {
			$plugin = $this->getService()->getPlugin($this->data['plugin']);

			if (isset($this->data['params'])) {
				$plugin->addParams($this->data['params']);
			}

			if (isset($this->data['dependencies'])) {
				$plugin->addDependencies($this->data['dependencies']);
			}

			$this->plugin = $plugin;
		}

		return $this->plugin;
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @param string $methodName
	 * @param array $params
	 * @return ResponseInterface
	 */
	private function invokeAction($methodName, $params)
	{
		$object = $this->getObject();
		if (!$object) {
			return $this->getNotFoundResponse();
		}

		$methodName = $methodName ?? $this->data['method'] ?? null;
		if (!$methodName || !method_exists($object, $methodName)) {
            return $this->getNotFoundResponse();
		}

		$params = $params ?? $this->data['params'] ?? [];

		$result = $object->runAction($methodName, $params);
		$response = $this->prepareResponse($object, $result);
		return $response;
	}

	/**
	 * @param string $methodName
	 * @param array $params
	 * @return ResponseInterface
	 */
	private function invokePlugin($methodName, $params)
	{
		$plugin = $this->getPlugin();
		$methodName = $methodName ?? Plugin::DEFAULT_SOURCE_METHOD;
		if (!method_exists($plugin, $methodName)) {
            return $this->getNotFoundResponse();
		}

		$params = $params ?? $this->data['params'] ?? [];
		$result = $plugin->runAction($methodName, $params);
        $response = $this->prepareResponse($plugin, $result);
        return $response;
	}

    /**
     * @param Source $object
     * @param ResponseInterface|array|string|null $result
     */
	private function prepareResponse($object, $result)
	{
	    if ($object->hasErrors()) {
            return \lx::$app->diProcessor->createByInterface(ResponseInterface::class, [
                $object,
                ResponseCodeEnum::BAD_REQUEST_ERROR
            ]);
        }

        if ($result === null) {
            return \lx::$app->diProcessor->createByInterface(ResponseInterface::class, ['Ok']);
        }

        if ($result instanceof ResponseInterface) {
            return $result;
        }

        if (!is_object($result)) {
            return \lx::$app->diProcessor->createByInterface(ResponseInterface::class, [$result]);
        }

        \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
            '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
            'msg' => 'Wrong response class ' . get_class($result),
        ]);
        return $this->getNotFoundResponse();
    }

	/**
	 * @return SourceInterface|null
	 */
	private function getObject()
	{
		$object = null;
		if (isset($this->data['object'])) {
			$object = $this->data['object'];
		} elseif (isset($this->data['class'])) {
			$class = $this->data['class'];
			$config = [];
			if (isset($this->data['service'])) {
				$config['service'] = $this->getService();
			}
			$object = \lx::$app->diProcessor->create($class, $config);
		}

		if ($object && $object instanceof SourceInterface) {
			return $object;
		}

		return null;
	}

    /**
     * @return ResponseInterface
     */
	private function getNotFoundResponse()
    {
        return \lx::$app->diProcessor->createByInterface(ResponseInterface::class, [
            'Resource not found',
            ResponseCodeEnum::NOT_FOUND
        ]);
    }
}
