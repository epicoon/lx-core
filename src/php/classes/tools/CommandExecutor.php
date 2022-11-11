<?php

namespace lx;

use lx;

class CommandExecutor
{
    private ?string $directory = null;
    private ?string $executor = null;
    private ?string $command = null;
    private ?array $commands = null;
    private ?array $args = null;
    private bool $async = false;
    private string $messageOutput = '/dev/null';
    private string $errorOutput = '/dev/null';
    private bool $createOutputFiles = false;
    private ?string $password = null;

    public function __construct(array $config = [])
    {
        $fields = [
            'directory', 'executor', 'command', 'commands', 'args', 'async',
            'messageOutput', 'errorOutput', 'createOutputFiles', 'password'
        ];
        foreach ($fields as $field) {
            if (array_key_exists($field, $config)) {
                $this->$field = $config[$field];
            }
        }
    }

    public function setDirectory(string $directory): CommandExecutor
    {
        $this->directory = $directory;
        return $this;
    }

    public function setExecutor(string $executor): CommandExecutor
    {
        $this->executor = $executor;
        return $this;
    }

    public function setCommand(string $command): CommandExecutor
    {
        $this->command = $command;
        return $this;
    }

    public function setCommands(array $commands): CommandExecutor
    {
        $this->commands = $commands;
        return $this;
    }

    public function setArgs(array $args): CommandExecutor
    {
        $this->args = $args;
        return $this;
    }

    public function setAsync(bool $async = true): CommandExecutor
    {
        $this->async = $async;
        return $this;
    }

    public function setMessageOutput(string $messageOutput): CommandExecutor
    {
        $this->messageOutput = $messageOutput;
        return $this;
    }

    public function setErrorOutput(string $errorOutput): CommandExecutor
    {
        $this->errorOutput = $errorOutput;
        return $this;
    }

    public function createOutputFiles(bool $value = true): CommandExecutor
    {
        $this->createOutputFiles = $value;
        return $this;
    }

    public function setPassword(string $password): CommandExecutor
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return array|string|null
     */
    public function run()
    {
        if ($this->commands) {
            return $this->runCommands();
        }

        if ($this->command) {
            return $this->runCommand();
        }

        return shell_exec('echo Undefined command');
    }

    private function runCommand()
    {
        $command = [];
        if ($this->directory) {
            $command[] = 'cd ' . $this->directory . ' &&';
        }
        if ($this->executor) {
            $command[] = $this->executor;
        }
        $command[] = $this->command;
        if ($this->args) {
            $args = [];
            foreach ($this->args as $arg) {
                $args[] = '"' . addcslashes($arg, '"\\') . '"';
            }
            $command[] = implode(' ', $args);
        }
        $command = implode(' ', $command);

        if ($this->async) {
            $msgLogPath = $this->messageOutput;
            $errorLogPath = $this->errorOutput;

            if ($this->createOutputFiles) {
                if ($msgLogPath !== '/dev/null') {
                    $file = new File($msgLogPath);
                    $file->getParentDir()->make();
                    $msgLogPath = $file->getPath();
                }
                if ($errorLogPath !== '/dev/null') {
                    $file = new File($errorLogPath);
                    $file->getParentDir()->make();
                    $errorLogPath = $file->getPath();
                }
            }

            $command .= " > $msgLogPath 2>$errorLogPath &";
        }

        return shell_exec($command);
    }

    private function runCommands()
    {
        //TODO output

        $commands = $this->commands;
        foreach ($commands as &$command) {
            if (preg_match('/^sudo /', $command)) {
                if ($this->password === null) {
                    return 'Password required';
                }
                $command = preg_replace('/^sudo /', 'echo ' . $this->password . ' | sudo -S ', $command);
            }
        }
        unset($command);

        $file = lx::$conductor->getTempFile('sh');
        if ($this->directory) {
            $code = 'cd ' . $this->directory . PHP_EOL;
        }
        $code .= implode(PHP_EOL, $commands);
        $file->put($code);
        $callCommand = 'sh "' . $file->getPath() . '"';
        exec($callCommand, $out);
        $file->remove();
        return $out;
    }
}
