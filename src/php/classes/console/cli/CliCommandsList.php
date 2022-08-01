<?php

namespace lx;

class CliCommandsList
{
    private array $map = [];
    /** @var array<CliCommand> */
    private array $list;

    public function getSubList(?array $types = null): CliCommandsList
    {
        if ($types === null) {
            return $this;
        }

        $slice = [];
        foreach ($this->list as $command) {
            if (!in_array($command->getType(), $types)) {
                continue;
            }

            $slice[] = $command;
        }

        $subList = new self();
        $subList->setCommands($slice);
        return $subList;
    }

    /**
     * @return array<CliCommand>
     */
    public function getCommands(): array
    {
        return $this->list;
    }

    public function getCommandNames(): array
    {
        return array_keys($this->map);
    }

    public function getCommand(string $name): ?CliCommand
    {
        return $this->map[$name] ?? null;
    }

    public function setCommands(array $list): void
    {
        $this->list = [];
        foreach ($list as $command) {
            if (is_array($command)) {
                $config = $command;
                $command = new CliCommand();
                $command->init($config);
            }

            $this->list[] = $command;
            $names = $command->getNames();
            foreach ($names as $name) {
                $this->map[$name] = $command;
            }
        }
    }

    public function commandExists(string $name): bool
    {
        return array_key_exists($name, $this->map);
    }

    public function removeCommand(string $name): void
    {
        $index = null;
        $names = [];
        foreach ($this->list as $i => $command) {
            if (in_array($name, $command->getNames())) {
                $index = $i;
                $names = $command->getNames();
                break;
            }
        }

        if ($index === null) {
            return;
        }

        unset($this->list[$index]);
        $this->list = array_values($this->list);
        foreach ($names as $name) {
            unset($this->map[$name]);
        }
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this->list as $command) {
            $result[] = $command->toArray();
        }

        return $result;
    }
}
