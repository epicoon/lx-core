<?php

namespace lx;

use lx;

class WebCli extends Module
{
    public function getCommandList(): HttpResponseInterface
    {
        $processor = new CliProcessor();
        $list = $processor->getCommandsList()->getSubList([
            CliProcessor::COMMAND_TYPE_COMMON,
            CliProcessor::COMMAND_TYPE_WEB,
        ]);

        return $this->prepareResponse(
            array_merge([
                [
                    'command' => ['clear'],
                    'description' => 'Clear console',
                ],
            ], $list->toArray())
        );
    }

    public function handleCommand(
        string $command,
        string $inputString,
        array $processParams,
        ?string $serviceName,
        ?string $pluginName
    ): HttpResponseInterface
    {
        $service = null;
        if ($serviceName) {
            $service = lx::$app->getService($serviceName);
            if (!$service) {
                return $this->prepareWarningResponse([
                    'success' => false,
                    'data' => 'Service name is wrong'
                ]);
            }
        }
        $plugin = null;
        if ($pluginName) {
            $plugin = lx::$app->getPlugin($pluginName);
            if (!$plugin) {
                return $this->prepareWarningResponse([
                    'success' => false,
                    'data' => 'Plugin name is wrong'
                ]);
            }
        }

        $processor = new CliProcessor();
        list($__pass, $args) = $processor->parseInput($inputString);
        $result = $processor->handleCommand(
            $command,
            CliProcessor::COMMAND_TYPE_WEB,
            $args,
            $processParams,
            $service,
            $plugin
        );

        $resService = $processor->getService();
        $resPlugin = $processor->getPlugin();
        $result['service'] = $resService ? $resService->name : null;
        $result['plugin'] = $resPlugin ? $resPlugin->name : null;

        return $this->prepareResponse($result);
    }

    public function tryFinishCommand(string $currentInput): HttpResponseInterface
    {
        $processor = new CliProcessor();
        $list = $processor->getCommandsList()->getSubList([
            CliProcessor::COMMAND_TYPE_COMMON,
            CliProcessor::COMMAND_TYPE_WEB,
        ]);
        $command = $processor->autoCompleteCommand($currentInput, $list);
        return $this->prepareResponse($command);
    }
}
