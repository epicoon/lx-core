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

    protected function defineArguments(): array
    {
        return [];
    }

    /**
     * @return mixed
     */
    abstract protected function process();

    public function setParams(array $params): void
    {
        $this->params = new CommandArgumentsList($params);
    }
    
    public function run()
    {
        $errorReport = ($this->params) ? $this->validateInput($this->params): [];
        //TODO $this->getInputRequire($this->params);
        if (!empty($errorReport)) {
            foreach ($errorReport as $row) {
                echo $row . PHP_EOL;
            }
            return;
        }
        
        $this->process();
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
