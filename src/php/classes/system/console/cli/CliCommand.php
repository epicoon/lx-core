<?php

namespace lx;

class CliCommand extends AbstractCommand
{
    private array $data;

    public function init(array $config): void
    {
        $this->data = $config;
        $this->data['command'] = (array)$this->data['command'];
    }

    public function getName(): string
    {
        return $this->data['command'][0] ?? '';
    }

    public function getNames(): array
    {
        return $this->data['command'];
    }

    public function getType(): int
    {
        return $this->data['type'] ?? CliProcessor::COMMAND_TYPE_COMMON;
    }

    public function getDescription(): string
    {
        return $this->data['description'] ?? parent::getDescription();
    }

    /**
     * @return array<CommandArgument>
     */
    public function getArgumentsSchema(): array
    {
        return $this->data['arguments'] ?? [];
    }

    /**
     * @return string|array|null
     */
    public function getExecutor()
    {
        return $this->data['handler'] ?? null;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
