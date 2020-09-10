<?php

namespace lx;

/**
 * Class CliCommandsList
 * @package lx
 */
class CliCommandsList
{
    /** @var array */
    private $map;

    /** @var CliCommand[] */
    private $list;

    /**
     * @param array|null $types
     * @return CliCommandsList
     */
    public function getSubList($types = null)
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
     * @return CliCommand[]
     */
    public function getCommands()
    {
        return $this->list;
    }

    /**
     * @return array
     */
    public function getCommandNames()
    {
        return array_keys($this->map);
    }

    /**
     * @param string $name
     * @return CliCommand|null
     */
    public function getCommand($name)
    {
        return $this->map[$name] ?? null;
    }

    /**
     * @param array $list
     */
    public function setCommands($list)
    {
        $this->list = [];
        foreach ($list as $command) {
            if (is_array($command)) {
                $command = new CliCommand($command);
            }

            $this->list[] = $command;
            $names = $command->getNames();
            foreach ($names as $name) {
                $this->map[$name] = $command;
            }
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function commandExists($name)
    {
        return array_key_exists($name, $this->map);
    }

    /**
     * @param $name
     */
    public function removeCommand($name)
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

    /**
     * @return array
     */
    public function toArray()
    {
        $result = [];
        foreach ($this->list as $command) {
            $result[] = $command->toArray();
        }

        return $result;
    }
}
