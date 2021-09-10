<?php

namespace lx;

class FusionComponentList
{
	private FusionInterface $fusion;
	private array $config = [];
	private array $list = [];

	public function __construct(FusionInterface $fusion)
	{
		$this->fusion = $fusion;
	}

	public function __get(string $name): ?FusionComponentInterface
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

	public function has(string $name): bool
	{
		return array_key_exists($name, $this->list) || array_key_exists($name, $this->config);
	}

	public function load(array $list, array $defaults = []): void
	{
	    foreach ($defaults as &$item) {
	        if (is_string($item)) {
	            $item = ['class' => $item];
            }
        }
	    unset($item);

		$fullList = ArrayHelper::mergeRecursiveDistinct($list, $defaults);
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

	private function createInstance(string $name, array $data): void
    {
        $params = $data['params'];
        $params['__fusion__'] = $this->fusion;
        $this->list[$name] = \lx::$app->diProcessor->create($data['class'], $params, [], get_class($this->fusion));
    }
}
