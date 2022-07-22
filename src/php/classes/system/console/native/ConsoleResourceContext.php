<?php

namespace lx;

use lx;

class ConsoleResourceContext implements ResourceContextInterface, FlightRecorderHolderInterface
{
    use FlightRecorderHolderTrait;

    /** @var CommandExecutorInterface|string|null */
    private $executor;
    private array $params;

    public function __construct(array $config)
    {
        $this->executor = $config['executor'] ?? null;
        $this->params = $config['params'] ?? [];
    }

    public function validate(): bool
    {
        if (!$this->executor) {
            $this->addFlightRecord('Command executor is undefined');
            return false;
        }

        if (!ClassHelper::implements($this->executor, CommandExecutorInterface::class)) {
            $this->addFlightRecord('Executor must implement lx\CommandExecutorInterface');
            return false;
        }

        return true;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * @return mixed
     */
    public function invoke()
    {
        $executor = $this->executor;
        if (is_string($executor)) {
            $executor = lx::$app->diProcessor->create($executor);
        }

        $executor->setParams($this->params);
        try {
            $executor->exec();
        } catch (\Throwable $exception) {
            echo $exception->getMessage() . PHP_EOL;
        }
    }
}
