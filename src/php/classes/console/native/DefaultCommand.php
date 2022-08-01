<?php

namespace lx;

class DefaultCommand extends NativeCommand
{
    public function getName(): string
    {
        return 'default';
    }

    protected function run()
    {
        echo 'Command not found' . PHP_EOL;
    }
}
