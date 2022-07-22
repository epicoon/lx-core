<?php

namespace lx;

abstract class NativeCommand extends AbstractCommand implements CommandExecutorInterface
{
    private array $arguments;
    protected ?CommandArgumentsList $params = null;
    
    public function __construct()
    {
        $this->arguments = $this->defineArguments();
    }

    abstract public function getName(): string;

    /**
     * @return mixed
     */
    abstract protected function run();

    protected function defineArguments(): array
    {
        return [];
    }

    public function setParams(array $params): void
    {
        $this->params = new CommandArgumentsList($params);
    }
    
    public function exec()
    {
        $errorReport = ($this->params) ? $this->validateInput($this->params): [];
        if (!empty($errorReport)) {
            foreach ($errorReport as $row) {
                echo $row . PHP_EOL;
            }
            return;
        }
        
        $this->run();
    }

    /**
     * @return array<CommandArgument>
     */
    public function getArgumentsSchema(): array
    {
        return $this->arguments;
    }

    /**
     * @return CommandExecutorInterface|string|array|null
     */
    public function getExecutor()
    {
        return $this;
    }
}
