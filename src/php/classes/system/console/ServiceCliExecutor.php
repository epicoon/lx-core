<?php

namespace lx;

/**
 * Class ServiceCliExecutor
 * @package lx
 */
class ServiceCliExecutor implements ServiceCliExecutorInterface
{
	/** @var CliProcessor */
	protected $processor;

	/** @var Service */
	protected $service = null;

	/**
	 * @param $processor CliProcessor
	 */
	public function setProcessor($processor)
	{
		$this->processor = $processor;
	}

	/**
	 * @return void
	 */
	public function run()
	{
		$this->processor->done();
	}

    /**
     * @return CliArgument
     */
	public static function getServiceArgument()
    {
        return (new CliArgument())->setKey(['service', 's', 0])
            ->setType(CliArgument::TYPE_STRING)
            ->setDescription('Service name');
    }

    /**
     * @return bool
     */
    public function defineService()
    {
        if ($this->service) {
            return true;
        }

        $processor = $this->processor;
        $serviceName = $processor->getArg('service');
        if ($serviceName) {
            if (Service::exists($serviceName)) {
                $this->service = lx::$app->getService($serviceName);
            } else {
                $processor->outln("Service '$serviceName' not found");
                return false;
            }
        }

        if ($this->service === null) {
            $this->service = $processor->getService();
        }

        return true;
    }
}
