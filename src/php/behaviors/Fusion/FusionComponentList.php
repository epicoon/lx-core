<?php

namespace lx;

/**
 * Class FusionComponentList
 * @package lx
 */
class FusionComponentList
{
	/** @var FusionInterface */
	private $fusion;

	/** @var array */
	private $config = [];

	/** @var array */
	private $list = [];

	/**
	 * FusionComponentList constructor.
	 * @param FusionInterface $fusion
	 */
	public function __construct($fusion)
	{
		$this->fusion = $fusion;
	}

	/**
	 * @param string $name
	 * @return FusionComponentInterface|null
	 */
	public function __get($name)
	{
		if (array_key_exists($name, $this->list)) {
			return $this->list[$name];
		}

		if (array_key_exists($name, $this->config)) {
			$this->createInstance($name, $this->config[$name]);
            unset($this->config[$name]);
			return $this->list[$name];
		}

		return null;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function has($name)
	{
		return array_key_exists($name, $this->list) || array_key_exists($name, $this->config);
	}

	/**
	 * @param array $list
	 * @param array $defaults
	 */
	public function load($list, $defaults = [])
	{
		$fullList = $list + $defaults;
		foreach ($fullList as $name => $config) {
			if ( ! $config) {
				\lx::devLog(['_'=>[__FILE__,__CLASS__,__TRAIT__,__METHOD__,__LINE__],
					'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
					'msg' => "Wrong component config for $name",
				]);
				continue;
			}

			$data = ClassHelper::prepareConfig($config);
			if ( ! $data) {
				\lx::devLog(['_'=>[__FILE__,__CLASS__,__TRAIT__,__METHOD__,__LINE__],
					'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
					'msg' => "Component $name not found",
				]);
				continue;
			}

			if (is_subclass_of($data['class'], EventListenerInterface::class)) {
			    $this->createInstance($name, $data);
            } else {
                $this->config[$name] = $data;
            }
		}
	}

    /**
     * @param string $name
     * @param array $data
     */
	private function createInstance($name, $data)
    {
        $params = $data['params'];
        $params['__fusion__'] = $this->fusion;
        $this->list[$name] = \lx::$app->diProcessor->create($data['class'], $params);
    }
}
