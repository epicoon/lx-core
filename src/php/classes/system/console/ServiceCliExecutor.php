<?php

namespace lx;

use lx;

class ServiceCliExecutor implements ServiceCliExecutorInterface
{
	protected CliProcessor $processor;
	protected ?Service $service = null;

	public function setProcessor(CliProcessor $processor): void
	{
		$this->processor = $processor;
	}

	public function run(): void
	{
		$this->processor->done();
	}

	public static function getServiceArgument(): CliArgument
    {
        return (new CliArgument())->setKeys(['service', 's', 0])
            ->setType(CliArgument::TYPE_STRING)
            ->setDescription('Service name');
    }

    public function defineService(): bool
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

    public function sendPlugin(array $config): void
    {
        $processor = $this->processor;

        $pluginName = $config['name'] ?? null;
        if ($pluginName === null) {
            $processor->outln('Wrong request config');
            return;
        }

        $plugin = lx::$app->getPlugin($pluginName);
        if ($plugin === null) {
            $processor->outln("Plugin $pluginName not found");
            return;
        }

        $processor->setData([
            'code' => 'ext',
            'type' => 'plugin',
            'message' => $config['message'] ?? 'Plugin loaded',
            'header' => $config['header'] ?? "Plugin $pluginName",
            'plugin' => $plugin->run()->getData(),
        ]);
        $processor->done();
    }
}
