<?php

namespace lx;

use lx;

class ServiceCliExecutor implements ServiceCliExecutorInterface
{
	protected CliProcessor $processor;
	protected ?Service $service = null;
    protected ?Plugin $plugin = null;

	public function setProcessor(CliProcessor $processor): void
	{
		$this->processor = $processor;
	}

	public function run(): void
	{
		$this->processor->done();
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

        return $this->service !== null;
    }

    public function definePlugin(): bool
    {
        if ($this->plugin) {
            return true;
        }

        $processor = $this->processor;
        $pluginName = $processor->getArg('plugin');
        if ($pluginName) {
            $plugin = lx::$app->getPlugin($pluginName);
            if ($plugin) {
                $this->plugin = $plugin;
            } else {
                $processor->outln("Plugin '$pluginName' not found");
                return false;
            }
        }

        if ($this->plugin === null) {
            $this->plugin = $processor->getPlugin();
        }

        return $this->plugin !== null;
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
            'plugin' => $plugin->render()->getData(),
        ]);
        $processor->done();
    }
}
