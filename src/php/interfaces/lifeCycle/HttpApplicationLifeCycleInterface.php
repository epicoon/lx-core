<?php

namespace lx;

interface HttpApplicationLifeCycleInterface extends ApplicationLifeCycleInterface
{
    public function beforeHandleRequest(Event $event): void;
}
