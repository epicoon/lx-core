<?php

namespace lx;

interface CommandInterface
{
    public function getName(): string;
    public function getDescription(): string;
    /**
     * @return array<CommandArgument>
     */
    public function getArgumentsSchema(): array;

    /**
     * @return CommandExecutorInterface|string|array|null
     */
    public function getExecutor();
}
