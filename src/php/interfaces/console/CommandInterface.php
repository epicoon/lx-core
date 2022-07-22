<?php

namespace lx;

interface CommandInterface
{
    public function getName(): string;
    public function getDescription(): string;
    public function getArgumentsSchema(): array;

    /**
     * @return CommandExecutorInterface|string|array|null
     */
    public function getExecutor();
}
